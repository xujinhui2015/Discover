<?php

namespace App\Console\Commands;

use App\Models\ProductSkuModel;
use App\Models\SkuStockModel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * 一次性修正：物料新增规格后，库存列表搜不到该规格。
 *
 * 背景：sku_stock 记录此前只由 SkuStockBatchObserver 在实际批次入库时创建，
 * 而物料库存列表以 sku_stock 为主表查询，导致新增的 SKU 在首次入库前
 * 完全不出现在列表里，按属性值筛选也搜不到。
 *
 * 本命令为所有缺失 sku_stock 的未删除 SKU 补建一条 num=0、standard=暂无 的记录。
 * 新增 SKU 的实时同步已由 ProductSkuObserver 处理，本命令只处理历史数据。
 *
 * 安全策略：默认仅预览（dry-run），加 --execute 才真正落库；只新增不修改，
 * 不会影响任何已有库存数量。
 */
class FixMissingSkuStock extends Command
{
    protected $signature = 'stock:fix-missing-sku-stock
        {--product= : 只处理指定物料ID（product.id），多个用英文逗号分隔}
        {--execute : 真正执行补建（默认仅预览，不改动任何数据）}';

    protected $description = '为缺失库存记录的物料规格补建零库存记录，使其能在物料库存列表中显示';

    public function handle(): int
    {
        $execute = (bool) $this->option('execute');

        $query = ProductSkuModel::query()
            ->with('product')
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('sku_stock')
                    ->whereColumn('sku_stock.sku_id', 'product_sku.id');
            })
            ->orderBy('product_id')
            ->orderBy('id');

        if ($productOption = (string) $this->option('product')) {
            $productIds = array_filter(array_map('intval', explode(',', $productOption)));
            if (empty($productIds)) {
                $this->error('--product 参数无效，请传入物料ID，如 --product=2480,2481');

                return self::FAILURE;
            }
            $query->whereIn('product_id', $productIds);
        }

        $skus = $query->get();

        if ($skus->isEmpty()) {
            $this->info('没有缺失库存记录的规格，无需处理。');

            return self::SUCCESS;
        }

        $this->info($execute ? '开始补建库存记录...' : '预览模式：以下规格缺失库存记录（不会改动数据）');

        $rows = $skus->map(function (ProductSkuModel $sku) {
            return [
                $sku->product_id,
                $sku->product->item_no ?? '',
                $sku->product->name ?? '（物料已删除）',
                $sku->id,
                $sku->attr_value_ids_str,
            ];
        })->all();

        $this->table(['物料ID', '物料编号', '物料名称', 'SKU ID', '属性'], $rows);

        if (! $execute) {
            $this->line('');
            $this->info('💡 共 '.$skus->count().' 条待补建，确认无误后执行：');
            $this->comment('   php artisan stock:fix-missing-sku-stock --execute');

            return self::SUCCESS;
        }

        $created = 0;
        DB::transaction(function () use ($skus, &$created) {
            foreach ($skus as $sku) {
                $stock = SkuStockModel::query()->firstOrCreate(
                    [
                        'sku_id'   => $sku->id,
                        'standard' => SkuStockModel::STANDARD_NO_CHOICE,
                    ],
                    ['num' => 0]
                );

                if ($stock->wasRecentlyCreated) {
                    $created++;
                }
            }
        });

        $this->line('');
        $this->info("补建完成，共新增 {$created} 条库存记录。");

        return self::SUCCESS;
    }
}
