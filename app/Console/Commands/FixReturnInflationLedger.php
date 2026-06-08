<?php

namespace App\Console\Commands;

use App\Models\SkuStockModel;
use App\Models\StockHistoryModel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * 一次性订正：修复「返仓虚增冲正」(type=13) 流水的 init_num / balance_num 口径。
 *
 * 背景：旧版 stock:fix-return-inflation 写流水时，把 init_num/balance_num 写成了
 * 「批次结余」，而全表惯例是「该 SKU 的总结余」。导致这些 SKU 的流水结余列断层、
 * 与实际总库存对不上。注意：实际库存（sku_stock / sku_stock_batch）本身是正确的，
 * 只有这两个历史台账字段写错，故本命令纯粹订正台账，不触碰任何实际库存。
 *
 * 算法：按 (sku_id, standard) 顺序走一遍该 SKU 的全部流水，维护「该时点 SKU 总结余」running：
 *   - 正常流水：信任其 balance_num，作为该时点 SKU 总结余；
 *   - type=13 流水：init_num = 上一时点总结余；balance_num = init_num + in_num - out_num。
 *     连续多条 type=13 会基于已订正的 running 逐条递推，避免「读到上一条错值」的级联错误。
 *   只改 type=13 行的 init_num/balance_num，其它流水一概不动。
 *
 * 安全：默认仅预览（dry-run），加 --execute 才落库；事务 + 行锁；幂等（已正确的行不再改）。
 */
class FixReturnInflationLedger extends Command
{
    protected $signature = 'stock:fix-return-inflation-ledger
        {--execute : 真正写库（默认仅预览，不改动任何数据）}';

    protected $description = '订正返仓虚增冲正流水(type=13)的 init_num/balance_num 为 SKU 总结余口径';

    public function handle(): int
    {
        $execute = (bool) $this->option('execute');
        $fixType = StockHistoryModel::RETURN_INFLATION_FIX_TYPE;

        $groups = StockHistoryModel::query()
            ->where('type', $fixType)
            ->select('sku_id', 'standard')
            ->distinct()
            ->get();

        $this->info("正在核算 {$groups->count()} 个 SKU 分组的返仓冲正流水台账……");

        $fixes = [];
        $unresolved = []; // 走链后无法收口到真实库存的 SKU，整组跳过交人工
        foreach ($groups as $g) {
            $rows = StockHistoryModel::query()
                ->where('sku_id', $g->sku_id)
                ->where('standard', $g->standard)
                ->orderBy('id')
                ->get(['id', 'type', 'init_num', 'in_num', 'out_num', 'balance_num']);

            $running = null;   // 该时点 SKU 总结余
            $groupFixes = [];
            $broken = false;
            foreach ($rows as $row) {
                if ((int) $row->type !== $fixType) {
                    // 正常流水：其 balance_num 即该时点 SKU 总结余，直接信任并作为基准
                    $running = (string) $row->balance_num;
                    continue;
                }

                if ($running === null) {
                    // 已用 SQL 校验过不存在「首行即 type=13」，此处仅作兜底
                    $broken = true;
                    break;
                }

                $newInit = $running;
                $newBalance = bcsub(bcadd($running, (string) $row->in_num, 2), (string) $row->out_num, 2);

                $initWrong = bccomp((string) $row->init_num, $newInit, 2) !== 0;
                $balWrong = bccomp((string) $row->balance_num, $newBalance, 2) !== 0;
                if ($initWrong || $balWrong) {
                    $groupFixes[] = [
                        'id' => (int) $row->id,
                        'sku_id' => (int) $g->sku_id,
                        'standard' => (int) $g->standard,
                        'old_init' => (string) $row->init_num,
                        'new_init' => $newInit,
                        'old_balance' => (string) $row->balance_num,
                        'new_balance' => $newBalance,
                    ];
                }

                $running = $newBalance; // 递推，供同 SKU 后续 type=13 使用
            }

            // 收口校验：走完整条链后的总结余必须等于真实库存，否则该 SKU 台账另有损坏，整组不动
            $realNum = (string) (SkuStockModel::query()
                ->where(['sku_id' => $g->sku_id, 'standard' => $g->standard])
                ->value('num') ?? '0');
            if ($broken || $running === null || bccomp($running, $realNum, 2) !== 0) {
                $unresolved[] = [
                    'sku_id' => (int) $g->sku_id,
                    'standard' => (int) $g->standard,
                    'walked_balance' => $running ?? '—',
                    'real_num' => $realNum,
                ];
                continue;
            }

            $fixes = array_merge($fixes, $groupFixes);
        }

        if ($unresolved) {
            $this->newLine();
            $this->warn('【无法自动订正】以下 ' . count($unresolved) . ' 个 SKU 台账另有损坏（走链结余 ≠ 真实库存），整组跳过，需人工核对：');
            $this->table(
                ['sku_id', 'std', '走链结余', '真实库存'],
                array_map(fn ($u) => [$u['sku_id'], $u['standard'], $u['walked_balance'], $u['real_num']], $unresolved)
            );
        }

        if (empty($fixes)) {
            $this->info('没有可自动订正的流水。');
            return self::SUCCESS;
        }

        $this->newLine();
        $this->info('【待订正】' . count($fixes) . ' 条 type=13 流水（仅改 init_num/balance_num，不动库存）：');
        $this->table(
            ['流水ID', 'sku_id', 'std', '原init', '新init', '原结余', '新结余'],
            array_map(fn ($f) => [
                $f['id'], $f['sku_id'], $f['standard'],
                $f['old_init'], $f['new_init'], $f['old_balance'], $f['new_balance'],
            ], $fixes)
        );

        if (! $execute) {
            $this->newLine();
            $this->warn('★ 预览模式：未改动任何数据。确认无误后加 --execute 执行。');
            return self::SUCCESS;
        }

        $applied = 0;
        DB::transaction(function () use ($fixes, &$applied) {
            foreach ($fixes as $f) {
                $affected = DB::table('stock_history')
                    ->where('id', $f['id'])
                    ->lockForUpdate()
                    ->update([
                        'init_num' => $f['new_init'],
                        'balance_num' => $f['new_balance'],
                    ]);
                $applied += $affected;
            }
        });

        $this->newLine();
        $this->info("✔ 已订正 {$applied} 条流水台账。实际库存未受影响。");
        return self::SUCCESS;
    }
}
