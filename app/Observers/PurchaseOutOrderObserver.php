<?php

namespace App\Observers;

use App\Models\PurchaseOutItemModel;
use App\Models\PurchaseOutOrderModel;
use App\Models\SkuStockBatchModel;
use App\Models\SkuStockModel;
use App\Models\StockHistoryModel;
use Dcat\Admin\Admin;
use RuntimeException;

class PurchaseOutOrderObserver
{
    public function creating(PurchaseOutOrderModel $purchaseOutOrderModel): void
    {
        $purchaseOutOrderModel->user_id = Admin::user()->id;
    }

    public function saving(PurchaseOutOrderModel $purchaseOutOrderModel): void
    {
        if ($purchaseOutOrderModel->isDirty('review_status')
            && (int) $purchaseOutOrderModel->review_status === PurchaseOutOrderModel::REVIEW_STATUS_OK
        ) {
            $purchaseInOrderId = (int) $purchaseOutOrderModel->with_id;
            if (! $purchaseInOrderId) {
                throw new RuntimeException('未找到关联采购入库单，无法审核');
            }

            $purchaseOutOrderModel->items->each(function (PurchaseOutItemModel $item) use ($purchaseOutOrderModel) {
                $purchaseInItem = \App\Models\PurchaseInItemModel::query()
                    ->where('order_id', $purchaseOutOrderModel->with_id)
                    ->where('sku_id', $item->sku_id)
                    ->where('standard', $item->standard)
                    ->where('batch_no', $item->batch_no)
                    ->where('position_id', $item->position_id)
                    ->first();

                if (! $purchaseInItem) {
                    throw new RuntimeException('未找到对应的入库明细，无法审核');
                }

                $returnedTotal = \App\Models\PurchaseOutItemModel::query()
                    ->where('sku_id', $item->sku_id)
                    ->where('standard', $item->standard)
                    ->where('batch_no', $item->batch_no)
                    ->where('position_id', $item->position_id)
                    ->whereHas('order', function ($query) use ($purchaseOutOrderModel) {
                        $query->where('with_id', $purchaseOutOrderModel->with_id);
                        $query->where('review_status', PurchaseOutOrderModel::REVIEW_STATUS_OK);
                    })
                    ->sum('actual_num');

                if (bccomp(bcadd((string) $returnedTotal, (string) $item->actual_num, 3), (string) $purchaseInItem->actual_num, 3) > 0) {
                    throw new RuntimeException('总退货数量不能大于采购入库数量');
                }

                $init_num = SkuStockModel::where([
                    'sku_id' => $item->sku_id,
                    'standard' => $item->standard,
                ])->value('num');

                $batch = SkuStockBatchModel::query()
                    ->where([
                        'sku_id' => $item->sku_id,
                        'position_id' => $item->position_id,
                        'batch_no' => $item->batch_no,
                        'standard' => $item->standard,
                    ])
                    ->first();

                StockHistoryModel::create([
                    'sku_id' => $item->sku_id,
                    'out_position_id' => $item->position_id,
                    'cost_price' => $batch ? $batch->cost_price : $item->price,
                    'type' => StockHistoryModel::OUT_STOCK_PUCHASE,
                    'flag' => StockHistoryModel::OUT,
                    'with_order_no' => $purchaseOutOrderModel->order_no,
                    'init_num' => $init_num ?? 0,
                    'out_num' => $item->actual_num,
                    'out_price' => $item->price,
                    'balance_num' => ($init_num ?? 0) - $item->actual_num,
                    'standard' => $item->standard,
                    'user_id' => Admin::user()->id,
                    'batch_no' => $item->batch_no,
                ]);
            });

            $purchaseOutOrderModel->apply_at = now();
        }
    }
}
