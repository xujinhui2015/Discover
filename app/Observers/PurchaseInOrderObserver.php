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

use App\Models\PurchaseInItemModel;
use App\Models\PurchaseInOrderModel;
use App\Models\PurchaseOrderModel;
use App\Models\SkuStockModel;
use App\Models\StockHistoryModel;
use Dcat\Admin\Admin;

class PurchaseInOrderObserver
{
    /**
     * Handle the purchase in order model "created" event.
     *
     * @param  \App\Models\PurchaseInOrderModel $purchaseInOrderModel
     * @return void
     */
    public function created(PurchaseInOrderModel $purchaseInOrderModel)
    {
        //
    }

    /**
     * Handle the purchase in order model "updated" event.
     *
     * @param  \App\Models\PurchaseInOrderModel $purchaseInOrderModel
     * @return void
     */
    public function updated(PurchaseInOrderModel $purchaseInOrderModel)
    {
        //
    }

    /**
     * Handle the purchase in order model "deleted" event.
     *
     * @param  \App\Models\PurchaseInOrderModel $purchaseInOrderModel
     * @return void
     */
    public function deleted(PurchaseInOrderModel $purchaseInOrderModel)
    {
        //
    }

    /**
     * Handle the purchase in order model "restored" event.
     *
     * @param  \App\Models\PurchaseInOrderModel $purchaseInOrderModel
     * @return void
     */
    public function restored(PurchaseInOrderModel $purchaseInOrderModel)
    {
        //
    }

    /**
     * Handle the purchase in order model "force deleted" event.
     *
     * @param  \App\Models\PurchaseInOrderModel $purchaseInOrderModel
     * @return void
     */
    public function forceDeleted(PurchaseInOrderModel $purchaseInOrderModel): void
    {
        //
    }

    /**
     * @param PurchaseInOrderModel $purchaseInOrderModel
     */
    public function saving(PurchaseInOrderModel $purchaseInOrderModel): void
    {

        if ($purchaseInOrderModel->isDirty('review_status')
            && (int)$purchaseInOrderModel->review_status === PurchaseInOrderModel::REVIEW_STATUS_OK
            && (int)$purchaseInOrderModel->status === PurchaseInOrderModel::STATUS_ARRIVE
        ) {
            // 创建库存流水记录
            $purchaseInOrderModel->items->each(function (PurchaseInItemModel $purchaseInItemModel) use ($purchaseInOrderModel) {
                $init_num = SkuStockModel::query()
                    ->where([
                        'sku_id' => $purchaseInItemModel->sku_id,
//                        'percent' => $purchaseInItemModel->percent,
                        'standard'       => $purchaseInItemModel->standard,
                    ])->value('num');

                StockHistoryModel::create([
                    'sku_id'         => $purchaseInItemModel->sku_id,
                    'in_position_id' => $purchaseInItemModel->position_id,
                    'cost_price'     => $purchaseInItemModel->price,
                    'type'           => StockHistoryModel::IN_STOCK_PUCHASE,
                    'flag'           => StockHistoryModel::IN,
                    'with_order_no'  => $purchaseInOrderModel->order_no,
                    'init_num'       => $init_num ?? 0,
                    'in_num'         => $purchaseInItemModel->actual_num,
                    'in_price'       => $purchaseInItemModel->price,
                    'balance_num'    => $init_num + $purchaseInItemModel->actual_num,
                    'standard'       => $purchaseInItemModel->standard,
                    'user_id'        => Admin::user()->id,
//                    'percent'        => $purchaseInItemModel->percent,
                    'batch_no'       => $purchaseInItemModel->batch_no,
                ]);
            });

            // 检查采购订单是否已经全部收货
            // 需要遍历采购单的所有明细项，而不是入库单的明细项
            // 只有当采购单的每个SKU都已全部入库时，才标记为"已收货"
            $allArrived = true;
            $purchaseOrderItems = $purchaseInOrderModel->with_order->items;
            foreach ($purchaseOrderItems as $purchaseItem) {
                // 计算该SKU在所有已审核入库单中的累计实际入库数量（包含当前正在审核的入库单）
                $sumActualNum = PurchaseInItemModel::query()
                    ->where('sku_id', $purchaseItem->sku_id)
                    ->where('standard', $purchaseItem->standard)
                    ->whereHas('order', function ($query) use ($purchaseInOrderModel) {
                        $query->where('with_id', $purchaseInOrderModel->with_id);
                        $query->where('review_status', PurchaseInOrderModel::REVIEW_STATUS_OK);
                    })
                    ->sum('actual_num');

                // 加上当前入库单中该SKU的入库数量（因为当前单据还未保存，数据库查不到）
                $currentInItem = $purchaseInOrderModel->items
                    ->where('sku_id', $purchaseItem->sku_id)
                    ->where('standard', $purchaseItem->standard)
                    ->first();
                $currentActualNum = $currentInItem ? $currentInItem->actual_num : 0;

                // 如果任一SKU的累计入库数量小于应采购数量，则为部分收货
                if (bccomp((string)($sumActualNum + $currentActualNum), (string)$purchaseItem->should_num, 2) < 0) {
                    $allArrived = false;
                    break;
                }
            }

            $purchaseInOrderModel->with_order->status = $allArrived ? PurchaseOrderModel::STATUS_ARRIVE : PurchaseOrderModel::STATUS_PART_RETURNED;
            $purchaseInOrderModel->with_order->save();

            $purchaseInOrderModel->apply_at = now();
            $purchaseInOrderModel->amount()->create([
                'supplier_id' => $purchaseInOrderModel->supplier_id,
                'should_amount' => $purchaseInOrderModel->items->reduce(function (float $amount, PurchaseInItemModel $itemModel) {
                    $sumPrice = bcmul($itemModel->price, $itemModel->actual_num, 5);
                    return bcadd($sumPrice, $amount, 5);
                }, 0),
            ]);
        }
    }

    /**
     * @param PurchaseInOrderModel $purchaseInOrderModel
     */
    public function creating(PurchaseInOrderModel $purchaseInOrderModel): void
    {
        $purchaseInOrderModel->user_id = Admin::user()->id;
    }
}
