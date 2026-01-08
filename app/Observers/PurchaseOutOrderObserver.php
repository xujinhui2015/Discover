<?php

namespace App\Observers;

use App\Models\PurchaseOutItemModel;
use App\Models\PurchaseOutOrderModel;
use App\Models\SkuStockBatchModel;
use App\Models\SkuStockModel;
use App\Models\StockHistoryModel;
use Dcat\Admin\Admin;

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
            $purchaseOutOrderModel->items->each(function (PurchaseOutItemModel $item) use ($purchaseOutOrderModel) {
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
