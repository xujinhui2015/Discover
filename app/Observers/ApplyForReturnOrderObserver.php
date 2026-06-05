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

use App\Models\ApplyForBatchModel;
use App\Models\ApplyForReturnItemModel;
use App\Models\ApplyForReturnOrderModel;
use App\Models\SaleOutOrderModel;
use App\Models\SkuStockModel;
use App\Models\StockHistoryModel;
use App\Models\TaskModel;
use Dcat\Admin\Admin;

class ApplyForReturnOrderObserver
{
    public function creating(ApplyForReturnOrderModel $applyForReturnOrderModel)
    {
        $applyForReturnOrderModel->user_id = Admin::user()->id;
    }

    public function saving(ApplyForReturnOrderModel $applyForReturnOrderModel): void
    {
        if ($applyForReturnOrderModel->isDirty('review_status')
            && (int)$applyForReturnOrderModel->review_status === SaleOutOrderModel::REVIEW_STATUS_OK
        ) {
            $applyForOrderItems = $applyForReturnOrderModel->apply_for_order->items;

            $applyForReturnOrderModel->items->each(function (ApplyForReturnItemModel $applyForReturnItemModel) use ($applyForReturnOrderModel, $applyForOrderItems) {

                $applyForOrderItemsRow = $applyForOrderItems->where('sku_id', $applyForReturnItemModel->sku_id)->first();

                $scale = 3;
                $batchs = $applyForOrderItemsRow->batchs->values();
                $lastIndex = $batchs->count() - 1;
                // 原出库各批次实领合计，用于按比例把退数分摊回对应批次
                $totalActual = $batchs->reduce(function ($carry, ApplyForBatchModel $applyForBatchModel) use ($scale) {
                    return bcadd($carry, (string) $applyForBatchModel->actual_num, $scale);
                }, '0');
                $shouldNum = (string) $applyForReturnItemModel->should_num;
                $allocated = '0';

                $batchs->each(function (ApplyForBatchModel $applyForBatchModel, $index) use (&$allocated, $lastIndex, $totalActual, $shouldNum, $scale, $applyForReturnItemModel, $applyForReturnOrderModel, $applyForOrderItemsRow) {
                    // 最后一个批次用余数补齐，保证各批次入库合计恰好等于退数
                    if ($index === $lastIndex) {
                        $allocNum = bcsub($shouldNum, $allocated, $scale);
                    } elseif (bccomp($totalActual, '0', $scale) === 0) {
                        $allocNum = '0';
                    } else {
                        $allocNum = bcdiv(bcmul($shouldNum, (string) $applyForBatchModel->actual_num, $scale + 2), $totalActual, $scale);
                        $allocated = bcadd($allocated, $allocNum, $scale);
                    }

                    $init_num = SkuStockModel::where([
                        'sku_id' => $applyForReturnItemModel->sku_id,
//                        'percent' => $applyForOrderItemsRow->percent,
                        'standard'       => $applyForOrderItemsRow->standard,
                    ])->value('num');

                    StockHistoryModel::create([
                        'sku_id'          => $applyForReturnItemModel->sku_id,
                        'in_position_id' => $applyForBatchModel->stock_batch->position_id,
                        'cost_price'      => $applyForBatchModel->stock_batch->cost_price,
                        'type'            => StockHistoryModel::RETURN_TO_WAREHOUSE_TYPE,
                        'flag'            => StockHistoryModel::IN,
                        'with_order_no'   => $applyForReturnOrderModel->order_no,
                        'init_num'        => $init_num,
                        'in_num'         => $allocNum,
                        'in_price'       => $applyForBatchModel->stock_batch->cost_price,
                        'balance_num'     => bcadd((string) $init_num, $allocNum, $scale),
//                        'percent'         => $applyForOrderItemsRow->percent,
                        'standard'        => $applyForOrderItemsRow->standard,
                        'user_id'         => Admin::user()->id,
                        'batch_no'        => $applyForBatchModel->stock_batch->batch_no,
                    ]);

                });
            });
        }
    }

    public function saved(ApplyForReturnOrderModel $applyForReturnOrderModel): void
    {
//        if ($applyForReturnOrderModel->with_order->status === TaskModel::STATUS_FINISH) {
//            return;
//        }
//        $notReviewCount = ApplyForReturnOrderModel::query()
//            ->where('with_id', $applyForReturnOrderModel->with_id)
//            ->where('review_status', "!=", ApplyForReturnOrderModel::REVIEW_STATUS_OK)->count();
//
//        if ($notReviewCount) {
//            $applyForReturnOrderModel->with_order->status = TaskModel::STATUS_WAIT;
//        } else {
//            $applyForReturnOrderModel->with_order->status = TaskModel::STATUS_DRAW;
//        }
//        $applyForReturnOrderModel->with_order->saveOrFail();
    }
}
