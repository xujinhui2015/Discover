<?php

namespace App\Observers;

use App\Models\SkuStockBatchModel;
use App\Models\StockHistoryModel;
use App\Models\TransferItemModel;
use App\Models\TransferOrderModel;
use Dcat\Admin\Admin;
use Exception;

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

            // 审核前校验批次库存是否足够
            foreach ($order->items as $index => $item) {
                $row = $index + 1;

                $batch = SkuStockBatchModel::query()
                    ->where('sku_id', $item->sku_id)
                    ->where('batch_no', $item->batch_no)
                    ->where('position_id', $order->out_position_id)
                    ->first();

                if (! $batch) {
                    throw new Exception("第{$row}行批次不存在或无库存，禁止审核");
                }

                if ($item->num - (float) $batch->num > 0.0001) {
                    throw new Exception("第{$row}行数量大于批次可用库存（可用 {$batch->num}），禁止审核");
                }
            }

            $order->items->each(function (TransferItemModel $item) use ($order) {
                // Get Cost Price from Source Batch (use order 出库仓位)
                $sourceBatch = SkuStockBatchModel::where([
                    'sku_id' => $item->sku_id,
                    'position_id' => $order->out_position_id,
                    'batch_no' => $item->batch_no,
                    'standard' => $item->standard,
                ])->first();

                $costPrice = $sourceBatch ? $sourceBatch->cost_price : 0;
                $outInitNum = $sourceBatch ? $sourceBatch->num : 0;

                // 计算调入仓当前库存（用于展示结余）
                $targetBatch = SkuStockBatchModel::where([
                    'sku_id' => $item->sku_id,
                    'position_id' => $order->in_position_id,
                    'batch_no' => $item->batch_no,
                    'standard' => $item->standard,
                ])->first();
                $inInitNum = $targetBatch ? $targetBatch->num : 0;

                // 出库记录
                StockHistoryModel::create([
                    'sku_id' => $item->sku_id,
                    // 仅展示出库信息，入库字段置 0 避免混淆
                    'in_position_id' => 0,
                    'out_position_id' => $order->out_position_id,
                    'cost_price' => $costPrice,
                    'type' => StockHistoryModel::TRANSFER_TYPE,
                    'flag' => StockHistoryModel::OUT,
                    'with_order_no' => $order->order_no,
                    'init_num' => $outInitNum,
                    'in_num' => 0,
                    'in_price' => 0,
                    'out_num' => $item->num,
                    'out_price' => $costPrice,
                    'balance_num' => $outInitNum - $item->num,
                    'user_id' => Admin::user()->id,
                    'batch_no' => $item->batch_no,
                    'standard' => $item->standard,
                    'percent' => $item->percent,
                ]);

                // 入库记录
                StockHistoryModel::create([
                    'sku_id' => $item->sku_id,
                    'in_position_id' => $order->in_position_id,
                    // 仅展示入库信息，出库字段置 0 避免混淆
                    'out_position_id' => 0,
                    'cost_price' => $costPrice,
                    'type' => StockHistoryModel::TRANSFER_TYPE,
                    'flag' => StockHistoryModel::IN,
                    'with_order_no' => $order->order_no,
                    'init_num' => $inInitNum,
                    'in_num' => $item->num,
                    'in_price' => $costPrice,
                    'out_num' => 0,
                    'out_price' => 0,
                    'balance_num' => $inInitNum + $item->num,
                    'user_id' => Admin::user()->id,
                    'batch_no' => $item->batch_no,
                    'standard' => $item->standard,
                    'percent' => $item->percent,
                ]);
            });
        }
    }
}
