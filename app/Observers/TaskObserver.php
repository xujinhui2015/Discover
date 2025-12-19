<?php

/*
 * // +----------------------------------------------------------------------
 * // | erp
 * // +----------------------------------------------------------------------
 * // | Copyright (c) 2006~2020 erp All rights reserved.
 * // +----------------------------------------------------------------------
 * // | Licensed ( LICENSE-1.0.0 )
 * // +----------------------------------------------------------------------
 * // | Author: yxx <1365831278@qq.com>
 * // +----------------------------------------------------------------------
 */

namespace App\Observers;

use App\Models\MakeProductItemModel;
use App\Models\MakeProductOrderModel;
use App\Models\PositionModel;
use App\Models\TaskModel;
use Dcat\Admin\Admin;

class TaskObserver
{
    public function creating(TaskModel $taskModel)
    {
        $taskModel->user_id = Admin::user()->id;
    }

    public function saving(TaskModel $taskModel)
    {
        if ($taskModel->isDirty('status') && $taskModel->status === TaskModel::STATUS_DRAW) {
            $makeProductOrder = $taskModel->make_product_order;
            $item = $makeProductOrder ? $makeProductOrder->items : null;
            if (!$item) {
                return;
            }

            $actualNum = (string) ($item->actual_num ?? '0');
            if (bccomp($actualNum, '0', 4) <= 0) {
                $item->cost_price = '0.00';
                $item->saveOrFail();
                return;
            }

            $item->cost_price = bcdiv((string) $taskModel->sum_cost_price, $actualNum, 2);
            $item->saveOrFail();
        }
    }

    public function saved(TaskModel $taskModel)
    {
        $makeProductOrder = MakeProductOrderModel::query()->firstOrCreate(
            ['with_id' => $taskModel->id],
            [
                'order_no' => build_order_no('SCRK'),
                'user_id' => Admin::user()->id,
                'apply_id' => Admin::user()->id,
                'other' => '',
            ]
        );
        MakeProductItemModel::query()->firstOrCreate(
            [
                'order_id' => $makeProductOrder->id,
            ],
            [
                'should_num' => $taskModel->plan_num,
                'actual_num' => $taskModel->plan_num,
                'batch_no' => "PC".date('Ymd'),
//                'percent' => $taskModel->percent,
                'standard' => $taskModel->standard,
                'sku_id' => $taskModel->sku_id,
                'position_id' => PositionModel::query()->value('id') ?? 0,
            ]
        );
    }
}
