<?php

/*
 * // +----------------------------------------------------------------------
 * // | erp
 * // +----------------------------------------------------------------------
 * // | Copyright (c) 2006~2020 erp All rights reserved.
 * // +----------------------------------------------------------------------
 * // | Licensed ( LICENSE-1.0.0 )
 * // +----------------------------------------------------------------------
 */

namespace App\Admin\Forms;

use App\Models\BaseModel;
use App\Models\MakeProductOrderModel;
use App\Models\SkuStockModel;
use App\Models\StockHistoryModel;
use App\Models\TaskModel;
use Dcat\Admin\Admin;
use Dcat\Admin\Contracts\LazyRenderable;
use Dcat\Admin\Form\Row;
use Dcat\Admin\Traits\LazyWidget;
use Dcat\Admin\Widgets\Form;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class MakeProductReplenishForm extends Form implements LazyRenderable
{
    use LazyWidget;

    /**
     * 处理二次生产补入库
     *
     * @param array $input
     *
     * @return Response
     */
    public function handle(array $input)
    {
        $replenishNum = $input['replenish_num'] ?? 0;
        if ($replenishNum <= 0) {
            return $this->error('补录库存数量必须大于0！');
        }

        try {
            DB::transaction(function () use ($input, $replenishNum) {
                // 加行锁，避免并发补入库时累计数量被覆盖
                $task = TaskModel::query()->lockForUpdate()->findOrFail($this->payload['id']);

                $order = MakeProductOrderModel::query()
                    ->where('with_id', $task->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ((int) $order->review_status !== BaseModel::REVIEW_STATUS_OK) {
                    throw new \RuntimeException('该单据未审核，无法补入库');
                }

                $item = $order->items()->lockForUpdate()->first();
                if (!$item) {
                    throw new \RuntimeException('生产入库明细不存在');
                }

                // 期初库存
                $initNum = SkuStockModel::query()
                    ->where([
                        'sku_id'   => $item->sku_id,
                        'standard' => $item->standard,
                    ])->value('num') ?? 0;

                // 生成入库流水，触发库存累加
                StockHistoryModel::create([
                    'sku_id'         => $item->sku_id,
                    'in_position_id' => $item->position_id,
                    'cost_price'     => $item->cost_price,
                    'type'           => StockHistoryModel::PRO_STOCK_TYPE,
                    'flag'           => StockHistoryModel::IN,
                    'with_order_no'  => $order->order_no,
                    'init_num'       => $initNum,
                    'in_num'         => $replenishNum,
                    'in_price'       => $item->cost_price,
                    'balance_num'    => bcadd($initNum, $replenishNum, 2),
                    'standard'       => $item->standard,
                    'user_id'        => Admin::user()->id,
                    'batch_no'       => $item->batch_no,
                ]);

                // 原单据库存信息累加
                $item->actual_num = bcadd($item->actual_num, $replenishNum, 2);
                $item->sum_cost_price = bcmul($item->actual_num, $item->cost_price, 2);
                $item->saveOrFail();

                // 任务完成数量累加
                $task->finish_num = bcadd($task->finish_num, $replenishNum, 2);
                $task->save();
            });
        } catch (\Throwable $exception) {
            return $this->error('补入库失败！' . $exception->getMessage());
        }

        return $this->success('补入库成功！', route('tasks.index'));
    }

    /**
     * 构建表单
     */
    public function form()
    {
        $task = TaskModel::query()->with('sku.product')->findOrFail($this->payload['id']);

        $this->row(function (Row $row) use ($task) {
            $row->width(4)->text('order_no', '任务单号')->value($task->order_no)->readOnly();
            $row->width(8)->text('info', '物料信息')
                ->value($task->sku['product']['name'] . '|' . $task->sku['attr_value_ids_str'] . '|' . $task->standard_str)
                ->readOnly();
        });

        $this->row(function (Row $row) use ($task) {
            $row->width(4)->text('plan_num_show', '计划数量')->value($task->plan_num)->readOnly();
            $row->width(4)->text('finish_num_show', '已完成数量')->value($task->finish_num)->readOnly();
        });

        $this->row(function (Row $row) {
            $row->width(4)->decimal('replenish_num', '补录库存')
                ->attribute('step', '0.01')
                ->default(0)
                ->required()
                ->help('填写本次二次生产补入库的数量，将累加到库存及原单据');
        });
    }
}
