<?php

namespace App\Console\Commands;

use App\Models\SkuStockBatchModel;
use App\Models\StockHistoryModel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * 一次性修正：冲减「物料返仓单重复入库」造成的库存虚增。
 *
 * 背景：旧版 ApplyForReturnOrderObserver 在审核返仓单时，对原出库的每个批次都按全额退数入库，
 * 导致原出库跨多批次的返仓单库存被重复累加。本命令按「原出库各批次占比」精确还原应入库量，
 * 仅冲减「至今未被盘点重置、且当前批次库存足够」的虚增量，并写入留痕流水。
 *
 * 安全策略：
 *  1. 默认仅预览（dry-run），加 --execute 才真正落库；
 *  2. 仅处理「未平」批次（返仓后无盘点重置）；已被真实盘点平掉的不动；
 *  3. 同一物理批次被多个返仓单冲减时，按可用库存在各单之间递减分配，能冲多少冲多少，冲不动的残留单独列出；
 *  4. 默认：库存不足的（虚增已流向下游出库）只冲到 0、绝不为负，剩余残留交人工；加 --allow-negative 则全额冲减、库存可为负（负数=实物超发/亏空）。原出库关联缺失、无法核算的始终单列；
 *  5. 幂等：已冲正部分由 already_fixed 扣除，重复运行不会重复冲减；
 *  6. 事务 + 行锁；冲减通过 Eloquent 保存批次（自动联动同步 SKU 总库存），留痕流水用原生插入避免被观察者二次扣减。
 */
class FixReturnInflationStock extends Command
{
    protected $signature = 'stock:fix-return-inflation
        {--execute : 真正执行冲减（默认仅预览，不改动任何数据）}
        {--allow-negative : 对库存不足的批次也全额冲减，允许库存为负（负数=实物超发/亏空）}
        {--operator=0 : 写入留痕流水的操作人用户ID}';

    protected $description = '冲减物料返仓单重复入库造成的库存虚增（按批次精确还原，留痕可追溯）';

    public function handle(): int
    {
        $execute = (bool) $this->option('execute');
        $allowNegative = (bool) $this->option('allow-negative');
        $operator = (int) $this->option('operator');

        $this->info('正在从库存流水重新核算返仓虚增……');
        $rows = DB::select($this->detectionSql());

        // 收集候选；over_b 为 NULL 表示原出库关联缺失、无法核算，单列出来交人工，而不是当 0 静默漏掉
        $candidates = [];
        $unresolved = [];
        foreach ($rows as $r) {
            if ($r->over_b === null) {
                $unresolved[] = [
                    'odr' => $r->odr,
                    'pno' => $r->pno,
                    'pname' => mb_substr((string) $r->pname, 0, 24),
                    'batch_no' => $r->batch_no,
                    'cur_num' => round((float) $r->cur_num, 2),
                ];
                continue;
            }
            $overB = round((float) $r->over_b, 2);
            if ($overB <= 0.005) {
                continue;
            }
            $alreadyFixed = round((float) $r->already_fixed, 2);
            $remainder = round($overB - $alreadyFixed, 2);
            if ($remainder <= 0.005) {
                continue; // 已冲正
            }
            $candidates[] = [
                'odr' => $r->odr,
                'sku_id' => (int) $r->sku_id,
                'standard' => (int) $r->standard,
                'batch_no' => $r->batch_no,
                'pos' => (int) $r->pos,
                'pno' => $r->pno,
                'pname' => mb_substr((string) $r->pname, 0, 24),
                'cur_num' => round((float) $r->cur_num, 2),
                'remainder' => $remainder,
            ];
        }

        // 同一物理批次可能被多个返仓单冲减：先按待冲减额从大到小排序，逐单累计扣减同一批次的库存。
        usort($candidates, fn ($a, $b) => $b['remainder'] <=> $a['remainder']);

        $safe = [];
        $stuck = [];
        $runningByBatch = [];
        foreach ($candidates as $c) {
            $key = $c['sku_id'] . '|' . $c['standard'] . '|' . $c['batch_no'] . '|' . $c['pos'];
            $base = $runningByBatch[$key] ?? $c['cur_num']; // 该批次在本单之前的剩余库存

            if ($allowNegative) {
                // 全额冲减，库存可为负；负数代表该批次实物超发/亏空
                $deduct = $c['remainder'];
            } else {
                // 保守：在各单之间递减分配可用库存，最多扣到 0，绝不为负
                $deduct = round(min($c['remainder'], max($base, 0)), 2);
            }

            $after = round($base - $deduct, 2);
            $runningByBatch[$key] = $after;
            $residual = round($c['remainder'] - $deduct, 2);

            if ($deduct > 0.005) {
                $safe[] = $c + ['deduct' => $deduct, 'after' => $after];
            }
            if ($residual > 0.005) {
                $stuck[] = $c + ['residual' => $residual];
            }
        }

        $this->renderTables($safe, $stuck, $unresolved, $allowNegative);

        if (! $execute) {
            $this->newLine();
            $this->warn('★ 预览模式：未改动任何数据。确认无误后加 --execute 执行。');
            return self::SUCCESS;
        }

        if (empty($safe)) {
            $this->info('没有需要冲减的安全批次。');
            return self::SUCCESS;
        }

        $applied = 0;
        $appliedQty = 0.0;
        DB::transaction(function () use ($safe, $operator, $allowNegative, &$applied, &$appliedQty) {
            foreach ($safe as $t) {
                $batch = SkuStockBatchModel::query()
                    ->where([
                        'sku_id' => $t['sku_id'],
                        'standard' => $t['standard'],
                        'batch_no' => $t['batch_no'],
                        'position_id' => $t['pos'],
                    ])
                    ->lockForUpdate()
                    ->first();

                if (! $batch) {
                    $this->warn("跳过：批次不存在 sku={$t['sku_id']} 批次={$t['batch_no']}");
                    continue;
                }

                // 幂等：已冲正的单在预览阶段 remainder 归零即被剔除，不会进入 $safe，故此处直接扣预分配的 deduct。
                // 默认以实时库存封顶（最多扣到 0，绝不为负）；--allow-negative 时全额冲减，库存可为负。
                $before = round((float) $batch->num, 2);
                $deduct = $allowNegative ? round($t['deduct'], 2) : round(min($t['deduct'], $before), 2);
                if ($deduct <= 0.005) {
                    $this->warn("跳过：库存不足 sku={$t['sku_id']} 批次={$t['batch_no']} 现存{$before}");
                    continue;
                }

                $after = round($before - $deduct, 2);
                $batch->num = $after;
                $batch->save(); // 触发 SkuStockBatchObserver，自动同步 sku_stock 总库存

                DB::table('stock_history')->insert([
                    'sku_id' => $t['sku_id'],
                    'in_position_id' => 0,
                    'out_position_id' => $t['pos'],
                    'cost_price' => $batch->cost_price ?? 0,
                    'type' => StockHistoryModel::RETURN_INFLATION_FIX_TYPE,
                    'flag' => StockHistoryModel::OUT,
                    'with_order_no' => $t['odr'],
                    'init_num' => $before,
                    'in_num' => 0,
                    'in_price' => 0,
                    'out_num' => $deduct,
                    'out_price' => $batch->cost_price ?? 0,
                    'balance_num' => $after,
                    'user_id' => $operator,
                    'batch_no' => $t['batch_no'],
                    'standard' => $t['standard'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $applied++;
                $appliedQty += $deduct;
            }
        });

        $this->newLine();
        $this->info("✔ 已冲减 {$applied} 个批次，合计冲减数量 " . number_format($appliedQty, 2) . "。SKU 总库存已联动同步。");
        if (! empty($stuck)) {
            $this->warn('注意：' . count($stuck) . ' 个「库存不足」批次未处理，需人工核对（见上表）。');
        }
        return self::SUCCESS;
    }

    private function renderTables(array $safe, array $stuck, array $unresolved = [], bool $allowNegative = false): void
    {
        $this->newLine();
        $this->info(($allowNegative ? '【全额冲减·允许负库存】' : '【可安全冲减】当前批次库存充足，') . count($safe) . ' 个批次：');
        if ($safe) {
            $this->table(
                ['返仓单', '物料编号', '物料', '批次', '现库存', '本次冲减', '冲减后'],
                array_map(fn ($t) => [$t['odr'], $t['pno'], $t['pname'], $t['batch_no'], $t['cur_num'], $t['deduct'], $t['after']], $safe)
            );
            $this->line('  小计冲减：' . number_format(array_sum(array_column($safe, 'deduct')), 2));
            $neg = array_filter($safe, fn ($t) => $t['after'] < -0.005);
            if ($neg) {
                $this->warn('  其中 ' . count($neg) . ' 个批次冲减后为负库存（代表实物超发/亏空，建议后续盘点核实）。');
            }
        }

        if ($stuck) {
            $this->newLine();
            $this->warn('【需人工核对】虚增已流向下游出库、当前库存不足，' . count($stuck) . ' 个批次（命令不会改动）：');
            $this->table(
                ['返仓单', '物料编号', '物料', '批次', '现库存', '残留虚增'],
                array_map(fn ($t) => [$t['odr'], $t['pno'], $t['pname'], $t['batch_no'], $t['cur_num'], $t['residual']], $stuck)
            );
            $this->line('  残留合计：' . number_format(array_sum(array_column($stuck, 'residual')), 2));
        }

        if ($unresolved) {
            $this->newLine();
            $this->warn('【无法自动核算】原出库关联缺失、算不出应冲减量，' . count($unresolved) . ' 项（命令不会改动，需人工核对）：');
            $this->table(
                ['返仓单', '物料编号', '物料', '批次', '现库存'],
                array_map(fn ($t) => [$t['odr'], $t['pno'], $t['pname'], $t['batch_no'], $t['cur_num']], $unresolved)
            );
        }
    }

    /**
     * 逐批次核算：bug 实际入库 - 按原出库占比的正确入库 = 虚增量；
     * 仅取返仓后无盘点重置（pd_total=0，未平）的批次，并带出当前批次库存与已冲正量。
     */
    private function detectionSql(): string
    {
        $fixType = StockHistoryModel::RETURN_INFLATION_FIX_TYPE;

        return <<<SQL
SELECT pb.odr, pb.sku_id, pb.standard, pb.batch_no, pb.pos, pb.over_b,
       COALESCE(ssb.num,0) AS cur_num,
       p.item_no AS pno, p.name AS pname,
       (SELECT COALESCE(SUM(f.out_num),0) FROM stock_history f
          WHERE f.type={$fixType} AND f.with_order_no=pb.odr AND f.sku_id=pb.sku_id
            AND f.standard=pb.standard AND f.batch_no=pb.batch_no AND f.out_position_id=pb.pos) AS already_fixed
FROM (
  SELECT retb.odr, retb.sku_id, retb.standard, retb.batch_no, retb.pos,
    (retb.bug_in - retb.should_num * COALESCE(od.out_qty,0)/NULLIF(ot.total_out,0)) AS over_b,
    (SELECT COUNT(*) FROM stock_history pd WHERE pd.type=2 AND pd.sku_id=retb.sku_id AND pd.standard=retb.standard
        AND pd.batch_no=retb.batch_no AND pd.in_position_id=retb.pos AND pd.created_at > retb.ret_at) AS pd_total
  FROM (
    SELECT t.odr, t.sku_id, t.standard, t.batch_no, t.pos, t.bug_in, t.ret_at, a.should_num
    FROM (
      SELECT sh.with_order_no AS odr, sh.sku_id, sh.standard, sh.batch_no, sh.in_position_id AS pos,
             SUM(sh.in_num) AS bug_in, MIN(sh.created_at) AS ret_at
      FROM stock_history sh WHERE sh.type=11 AND sh.flag=1
      GROUP BY sh.with_order_no, sh.sku_id, sh.standard, sh.batch_no, sh.in_position_id
    ) t
    JOIN (
      SELECT la.odr, la.sku_id, la.standard, ia.total_should AS should_num
      FROM (SELECT with_order_no AS odr, sku_id, standard, SUM(in_num) AS actual_in FROM stock_history WHERE type=11 AND flag=1 GROUP BY with_order_no, sku_id, standard) la
      JOIN apply_for_return_order aro ON aro.order_no=la.odr
      JOIN (SELECT order_id, sku_id, standard, SUM(should_num) AS total_should FROM apply_for_return_item GROUP BY order_id, sku_id, standard) ia
        ON ia.order_id=aro.id AND ia.sku_id=la.sku_id AND ia.standard=la.standard
      WHERE la.actual_in > ia.total_should + 0.001
    ) a ON a.odr=t.odr AND a.sku_id=t.sku_id AND a.standard=t.standard
  ) retb
  LEFT JOIN (
    SELECT aro.order_no AS odr, sh.sku_id, sh.standard, sh.batch_no, SUM(sh.out_num) AS out_qty
    FROM stock_history sh JOIN apply_for_order afo ON afo.order_no=sh.with_order_no JOIN apply_for_return_order aro ON aro.apply_for_order_id=afo.id
    WHERE sh.type=4 AND sh.flag=0 GROUP BY aro.order_no, sh.sku_id, sh.standard, sh.batch_no
  ) od ON od.odr=retb.odr AND od.sku_id=retb.sku_id AND od.standard=retb.standard AND od.batch_no=retb.batch_no
  LEFT JOIN (
    SELECT aro.order_no AS odr, sh.sku_id, sh.standard, SUM(sh.out_num) AS total_out
    FROM stock_history sh JOIN apply_for_order afo ON afo.order_no=sh.with_order_no JOIN apply_for_return_order aro ON aro.apply_for_order_id=afo.id
    WHERE sh.type=4 AND sh.flag=0 GROUP BY aro.order_no, sh.sku_id, sh.standard
  ) ot ON ot.odr=retb.odr AND ot.sku_id=retb.sku_id AND ot.standard=retb.standard
) pb
LEFT JOIN sku_stock_batch ssb ON ssb.sku_id=pb.sku_id AND ssb.standard=pb.standard AND ssb.batch_no=pb.batch_no AND ssb.position_id=pb.pos
LEFT JOIN product_sku ps ON ps.id=pb.sku_id
LEFT JOIN product p ON p.id=ps.product_id
WHERE pb.pd_total=0 AND (pb.over_b > 0.005 OR pb.over_b IS NULL)
ORDER BY pb.over_b DESC, pb.odr
SQL;
    }
}
