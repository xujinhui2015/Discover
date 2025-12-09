<?php

namespace App\Observers;

use App\Models\SkuStockBatchModel;
use App\Models\StockHistoryModel;
use App\Models\TransferItemModel;
use App\Models\TransferOrderModel;
use Dcat\Admin\Admin;

class TransferOrderObserver
{
    public function creating(TransferOrderModel $order)
    {
        if (!$order->user_id) {
            $order->user_id = Admin::user()->id;
        }
    }

    public function saving(TransferOrderModel $order)
    {
        if ($order->isDirty('review_status') && $order->review_status == TransferOrderModel::REVIEW_STATUS_OK) {
            $order->audit_user_id = Admin::user()->id;
            $order->finished_at = now();
            $order->apply_at = $order->apply_at ?: now();

            $order->items->each(function (TransferItemModel $item) use ($order) {
                // Get Cost Price from Source Batch
                $sourceBatch = SkuStockBatchModel::where([
                    'sku_id' => $item->sku_id,
                    'position_id' => $item->out_position_id,
                    'batch_no' => $item->batch_no,
                    'standard' => $item->standard,
                ])->first();

                $costPrice = $sourceBatch ? $sourceBatch->cost_price : 0;
                $initNum = $sourceBatch ? $sourceBatch->num : 0;

                StockHistoryModel::create([
                    'sku_id' => $item->sku_id,
                    'in_position_id' => $item->in_position_id,
                    'out_position_id' => $item->out_position_id,
                    'cost_price' => $costPrice,
                    'type' => StockHistoryModel::TRANSFER_TYPE,
                    'flag' => StockHistoryModel::TRANSFER,
                    'with_order_no' => $order->order_no,
                    'init_num' => $initNum,
                    'in_num' => $item->num,
                    'in_price' => $costPrice,
                    'out_num' => $item->num,
                    'out_price' => $costPrice,
                    'balance_num' => 0, // Difficult to define for transfer (affects two balances)
                    'user_id' => Admin::user()->id,
                    'batch_no' => $item->batch_no,
                    'standard' => $item->standard,
                    'percent' => $item->percent,
                ]);
            });
        }
    }
}
