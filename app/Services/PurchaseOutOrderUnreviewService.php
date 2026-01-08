<?php

namespace App\Services;

use App\Models\PurchaseOutOrderModel;
use App\Models\SkuStockBatchModel;
use App\Models\StockHistoryModel;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PurchaseOutOrderUnreviewService
{
    public function unreview(PurchaseOutOrderModel $order): void
    {
        if ((int) $order->review_status !== PurchaseOutOrderModel::REVIEW_STATUS_OK) {
            throw new RuntimeException('单据不是已审核状态，无法反审核');
        }

        $histories = StockHistoryModel::query()
            ->where('type', StockHistoryModel::OUT_STOCK_PUCHASE)
            ->where('flag', StockHistoryModel::OUT)
            ->where('with_order_no', $order->order_no)
            ->get();

        if ($histories->isEmpty()) {
            throw new RuntimeException('未找到该单据对应的库存流水，无法反审核');
        }

        DB::transaction(function () use ($order, $histories) {
            foreach ($histories as $history) {
                $batch = SkuStockBatchModel::query()
                    ->where([
                        'position_id' => $history->out_position_id,
                        'batch_no' => $history->batch_no,
                        'sku_id' => $history->sku_id,
                        'standard' => (int) $history->standard,
                    ])
                    ->lockForUpdate()
                    ->first();

                if (! $batch) {
                    throw new RuntimeException('批次库存记录丢失，已终止反审核');
                }

                $batch->num = bcadd((string) $batch->num, (string) $history->out_num, 3);
                $batch->save();

                $history->delete();
            }

            $order->review_status = PurchaseOutOrderModel::REVIEW_STATUS_REREVIEW;
            $order->apply_at = null;
            $order->save();
        });
    }
}
