<?php

namespace App\Console\Commands;

use App\Models\SaleInOrderModel;
use App\Models\SkuStockBatchModel;
use App\Models\SkuStockModel;
use App\Models\StockHistoryModel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * 一次性修正：销售退货单审核后库存未入账。
 *
 * 背景：旧版 StockHistoryObserver 的 switch 缺少 STORE_IN_TYPE(12) 分支，
 * 销售退货单审核时只写了库存流水，未把退货数量加回 sku_stock_batch / sku_stock。
 * 本命令按该单的 type=12 入库流水，把 in_num 补回对应批次（通过 Eloquent 保存，
 * 自动联动同步 SKU 总库存），不新写流水——原有流水本身就是这笔入库的凭证。
 *
 * 安全策略：
 *  1. 默认仅预览（dry-run），加 --execute 才真正落库；
 *  2. 幂等校验：仅当该流水是其 SKU 的最新一条流水时，用「流水结余 - 当前总库存」核对——
 *     差额等于 in_num 说明未补过，差额为 0 说明已补过（自动跳过）；
 *     该 SKU 在退货之后另有流水、或差额对不上的，一律不动，单列出来交人工核对；
 *  3. 事务 + 行锁，防止并发出入库穿插。
 */
class FixSaleInMissingStock extends Command
{
    protected $signature = 'stock:fix-sale-in-missing
        {order_no=TH202607170001 : 要修复的销售退货单号}
        {--execute : 真正执行补库（默认仅预览，不改动任何数据）}';

    protected $description = '补回销售退货单审核后未入账的库存（StockHistoryObserver 缺 STORE_IN_TYPE 分支的历史单据）';

    public function handle(): int
    {
        $orderNo = (string) $this->argument('order_no');
        $execute = (bool) $this->option('execute');
        $scale = 2;

        $order = SaleInOrderModel::query()->where('order_no', $orderNo)->first();
        if (! $order) {
            $this->error("未找到销售退货单：{$orderNo}");
            return self::FAILURE;
        }
        if ((int) $order->review_status !== SaleInOrderModel::REVIEW_STATUS_OK) {
            $this->error("单据 {$orderNo} 不是已审核状态，审核时才会产生入库流水，无需修复。");
            return self::FAILURE;
        }

        $histories = StockHistoryModel::query()
            ->where('type', StockHistoryModel::STORE_IN_TYPE)
            ->where('flag', StockHistoryModel::IN)
            ->where('with_order_no', $orderNo)
            ->orderBy('id')
            ->get();

        if ($histories->isEmpty()) {
            $this->error("单据 {$orderNo} 没有 type=12 的入库流水，无法核算。");
            return self::FAILURE;
        }

        $pending = [];
        $skipped = [];
        $manual = [];
        foreach ($histories as $h) {
            $row = [
                'history_id' => $h->id,
                'sku_id' => $h->sku_id,
                'standard' => (int) $h->standard,
                'batch_no' => $h->batch_no,
                'position_id' => $h->in_position_id,
                'in_num' => (string) $h->in_num,
                'balance_num' => (string) $h->balance_num,
            ];

            // 幂等校验仅在该流水仍是此 SKU 最新一条时可靠：
            // 之后再有出入库，总库存与该流水结余的差额就不再等于未入账的 in_num
            $hasLater = StockHistoryModel::query()
                ->where('sku_id', $h->sku_id)
                ->where('standard', (int) $h->standard)
                ->where('id', '>', $h->id)
                ->exists();

            $stockNum = (string) (SkuStockModel::query()
                ->where(['sku_id' => $h->sku_id, 'standard' => (int) $h->standard])
                ->value('num') ?? 0);
            $diff = bcsub((string) $h->balance_num, $stockNum, $scale);
            $row['stock_num'] = $stockNum;
            $row['diff'] = $diff;

            if ($hasLater) {
                $row['reason'] = '退货之后该 SKU 另有流水，差额无法自动核算';
                $manual[] = $row;
            } elseif (bccomp($diff, '0', $scale) === 0) {
                $row['reason'] = '总库存与流水结余一致，已补过';
                $skipped[] = $row;
            } elseif (bccomp($diff, $row['in_num'], $scale) === 0) {
                $pending[] = $row;
            } else {
                $row['reason'] = "差额 {$diff} 与退货数 {$row['in_num']} 不符";
                $manual[] = $row;
            }
        }

        $this->renderTables($pending, $skipped, $manual);

        if ($manual) {
            $this->warn('存在无法自动核算的流水，命令不会改动它们，请人工核对。');
        }
        if (! $pending) {
            $this->info('没有待补库的流水，未改动任何数据。');
            return self::SUCCESS;
        }
        if (! $execute) {
            $this->newLine();
            $this->warn('★ 预览模式：未改动任何数据。确认无误后加 --execute 执行。');
            return self::SUCCESS;
        }

        DB::transaction(function () use ($pending, $scale) {
            foreach ($pending as $t) {
                $batch = SkuStockBatchModel::query()
                    ->where([
                        'sku_id' => $t['sku_id'],
                        'standard' => $t['standard'],
                        'batch_no' => $t['batch_no'],
                        'position_id' => $t['position_id'],
                    ])
                    ->lockForUpdate()
                    ->firstOrFail();

                // 锁内复核幂等：预检在事务外，并发执行时第二个进程等到锁后数据已变，
                // 差额不再等于退货数则说明已被补过，跳过防止重复入账
                $freshStockNum = (string) (SkuStockModel::query()
                    ->where(['sku_id' => $t['sku_id'], 'standard' => $t['standard']])
                    ->lockForUpdate()
                    ->value('num') ?? 0);
                if (bccomp(bcsub($t['balance_num'], $freshStockNum, $scale), $t['in_num'], $scale) !== 0) {
                    $this->warn("跳过：sku={$t['sku_id']} 批次={$t['batch_no']} 锁内复核差额已变化（当前总库存 {$freshStockNum}），疑似已被并发补过。");
                    continue;
                }

                $batch->num = bcadd((string) $batch->num, $t['in_num'], $scale);
                $batch->save(); // 触发 SkuStockBatchObserver，自动同步 sku_stock 总库存

                $stockNum = SkuStockModel::query()
                    ->where(['sku_id' => $t['sku_id'], 'standard' => $t['standard']])
                    ->value('num');
                $this->info("✔ sku={$t['sku_id']} 批次={$t['batch_no']} 补回 {$t['in_num']}，批次现存 {$batch->num}，SKU 总库存 {$stockNum}");
            }
        });

        $this->newLine();
        $this->info('补库完成，SKU 总库存已联动同步。');
        return self::SUCCESS;
    }

    private function renderTables(array $pending, array $skipped, array $manual): void
    {
        $headers = ['流水ID', 'SKU', '批次', '库位', '退货数', '当前总库存', '流水结余差额'];
        $toRow = fn ($t) => [$t['history_id'], $t['sku_id'], $t['batch_no'], $t['position_id'], $t['in_num'], $t['stock_num'], $t['diff']];

        $this->newLine();
        $this->info('【待补库】差额与退货数一致，' . count($pending) . ' 条：');
        if ($pending) {
            $this->table($headers, array_map($toRow, $pending));
        }

        if ($skipped) {
            $this->newLine();
            $this->info('【已补过·跳过】' . count($skipped) . ' 条：');
            $this->table($headers, array_map($toRow, $skipped));
        }

        if ($manual) {
            $this->newLine();
            $this->warn('【需人工核对】' . count($manual) . ' 条（命令不会改动）：');
            $this->table(
                array_merge($headers, ['原因']),
                array_map(fn ($t) => array_merge($toRow($t), [$t['reason']]), $manual)
            );
        }
    }
}
