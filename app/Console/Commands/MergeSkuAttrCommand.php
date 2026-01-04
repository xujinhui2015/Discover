<?php

namespace App\Console\Commands;

use App\Models\ProductModel;
use App\Models\ProductSkuModel;
use App\Models\SkuStockModel;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MergeSkuAttrCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'product:merge-sku-attr {--dry-run : 仅预览不执行} {--force : 跳过确认}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '合并产品 SKU 属性到唯一有库存的 SKU，并删除无库存行';

    private const ITEM_NOS = [
        '◎8',
        'ADY-001-G',
        'CD-8001-G',
        'CD-8007-LT',
        'CD-8010-G',
        'CD-8016-G',
        'CD-8020-G',
        'CD-8018-ZT',
        'DKL-2804A/2804B-G(哑银）',
        'EA-2089A-G',
        'EA-2089A-ZT',
        'EB-1623-G',
        'EB-1623-ZT',
        'EB-21311/21312-G',
        'EB-21311/21312-ZT',
        'EB-2905A/2905B-G',
        'EB-2905C/2905D-G',
        'FS-21905(A/B）-ZT',
        'MLS-21302-G',
        'MLS-2907-G',
        'MLS-2919-G',
        'MSD05-N',
        'OMJ-◎6.1',
        'OMJ-◎6.2',
        'OMJ-◎6.3',
        'OMJ-◎6.4',
        'OMJ-21307ABCD-G',
        'OMJ-21307ABCD-ZT',
        'OMJ-21701ABCD-LT',
        'OMJ-21701ABCD-G（A5）',
        'OMJ-21701ABCD-ZT（A5）',
        'OMJ-21704BC-G',
        'FS2301-02-W',
        'FS2301-02-Z',

    ];

    public function handle(): int
    {
        $itemNos = $this->normalizeItemNos(self::ITEM_NOS);
        if (!$itemNos) {
            $this->error('ITEM_NOS 未配置任何物料编码。');
            return 1;
        }

        $isDryRun = (bool)$this->option('dry-run');
        $force = (bool)$this->option('force');

        $products = ProductModel::query()
            ->whereIn('item_no', $itemNos)
            ->get(['id', 'item_no', 'name']);

        $foundItemNos = $products->pluck('item_no')->all();
        $missingItemNos = array_values(array_diff($itemNos, $foundItemNos));
        if ($missingItemNos) {
            $this->warn('未找到的物料编码: ' . implode(', ', $missingItemNos));
        }

        if (!$isDryRun && !$force) {
            if (!$this->confirm('确认执行合并与删除操作吗？')) {
                $this->info('已取消操作。');
                return 0;
            }
        }

        $summary = [
            'products' => 0,
            'stock_deleted' => 0,
            'sku_deleted' => 0,
            'sku_updated' => 0,
            'skipped' => 0,
        ];

        foreach ($products as $product) {
            $summary['products']++;

            $productSkus = ProductSkuModel::query()
                ->where('product_id', $product->id)
                ->get(['id', 'attr_value_ids']);

            if ($productSkus->isEmpty()) {
                $this->warn("物料 {$product->item_no}: 未找到 product_sku 记录，已跳过。");
                $summary['skipped']++;
                continue;
            }

            $skuIds = $productSkus->pluck('id')->map('intval')->all();
            $inStockSkuIds = SkuStockModel::query()
                ->whereIn('sku_id', $skuIds)
                ->where('num', '>', 0)
                ->distinct()
                ->pluck('sku_id')
                ->map('intval')
                ->all();

            if (!$inStockSkuIds) {
                $this->warn("物料 {$product->item_no}: 没有库存大于 0 的 SKU，已跳过。");
                $summary['skipped']++;
                continue;
            }

            if (count($inStockSkuIds) > 1) {
                $this->warn("物料 {$product->item_no}: 存在多个有库存 SKU，已跳过。");
                $summary['skipped']++;
                continue;
            }

            $keepSkuId = (int)SkuStockModel::query()
                ->whereIn('sku_id', $skuIds)
                ->where('num', '>', 0)
                ->orderByDesc('num')
                ->orderBy('sku_id')
                ->value('sku_id');

            if ($keepSkuId <= 0) {
                $this->warn("物料 {$product->item_no}: 无法定位有库存 SKU，已跳过。");
                $summary['skipped']++;
                continue;
            }

            $mergedAttrValueIds = $this->mergeAttrValueIds($productSkus);
            $otherSkuIds = array_values(array_diff($skuIds, [$keepSkuId]));
            $stockDeleteCount = SkuStockModel::query()
                ->whereIn('sku_id', $skuIds)
                ->where('num', '<=', 0)
                ->count();

            $keepSku = $productSkus->firstWhere('id', $keepSkuId)
                ?: ProductSkuModel::query()->find($keepSkuId, ['id', 'attr_value_ids']);
            $needsSkuUpdate = $keepSku && (string)$keepSku->attr_value_ids !== $mergedAttrValueIds;

            $this->line("物料 {$product->item_no}: 保留 SKU {$keepSkuId}，合并 attr_value_ids -> '{$mergedAttrValueIds}'");
            $this->line("  - 需要删除的 sku_stock 行 (num <= 0): {$stockDeleteCount}");
            $this->line('  - 需要删除的 product_sku 行: ' . count($otherSkuIds));
            $this->line('  - 是否需要更新 product_sku: ' . ($needsSkuUpdate ? '是' : '否'));

            if ($isDryRun) {
                continue;
            }

            DB::transaction(function () use (
                $skuIds,
                $keepSkuId,
                $mergedAttrValueIds,
                $needsSkuUpdate,
                $otherSkuIds,
                &$summary
            ) {
                $deletedStock = SkuStockModel::query()
                    ->whereIn('sku_id', $skuIds)
                    ->where('num', '<=', 0)
                    ->delete();
                $summary['stock_deleted'] += $deletedStock;

                if ($needsSkuUpdate) {
                    ProductSkuModel::query()
                        ->whereKey($keepSkuId)
                        ->update(['attr_value_ids' => $mergedAttrValueIds]);
                    $summary['sku_updated']++;
                }

                if ($otherSkuIds) {
                    $deletedSkus = ProductSkuModel::query()
                        ->whereIn('id', $otherSkuIds)
                        ->delete();
                    $summary['sku_deleted'] += $deletedSkus;
                }
            });
        }

        $this->line('');
        $this->info('处理完成。');
        $this->table(
            ['统计项', '数量'],
            [
                ['处理的物料数', $summary['products']],
                ['跳过的物料数', $summary['skipped']],
                ['删除的 sku_stock 行数', $summary['stock_deleted']],
                ['删除的 product_sku 行数', $summary['sku_deleted']],
                ['更新的 product_sku 行数', $summary['sku_updated']],
            ]
        );

        if ($isDryRun) {
            $this->line('');
            $this->info('当前为预览模式，请去掉 --dry-run 后再执行。');
        }

        return 0;
    }

    /**
     * @param array<int, string|int> $itemNos
     * @return array<int, string>
     */
    private function normalizeItemNos(array $itemNos): array
    {
        $result = [];
        foreach ($itemNos as $itemNo) {
            $itemNo = trim((string)$itemNo);
            if ($itemNo === '') {
                continue;
            }
            if (!in_array($itemNo, $result, true)) {
                $result[] = $itemNo;
            }
        }

        return $result;
    }

    private function mergeAttrValueIds(Collection $productSkus): string
    {
        $merged = [];
        foreach ($productSkus as $sku) {
            $raw = (string)$sku->attr_value_ids;
            if ($raw === '') {
                continue;
            }
            foreach (explode(',', $raw) as $value) {
                $value = trim($value);
                if ($value === '') {
                    continue;
                }
                $id = (int)$value;
                if ($id <= 0) {
                    continue;
                }
                if (!in_array($id, $merged, true)) {
                    $merged[] = $id;
                }
            }
        }

        sort($merged, SORT_NUMERIC);

        return implode(',', $merged);
    }
}
