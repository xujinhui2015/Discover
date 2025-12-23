<?php

namespace App\Services;

use App\Models\BaseModel;
use App\Models\SaleInOrderModel;
use App\Models\SaleOrderModel;
use App\Models\SaleOutOrderModel;
use App\Models\SkuStockBatchModel;
use App\Models\StockHistoryModel;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SaleOutOrderUnreviewService
{
    public function unreview(SaleOutOrderModel $order): void
    {
        $order->loadMissing(['with_order', 'amount']);

        if ((int) $order->review_status !== SaleOutOrderModel::REVIEW_STATUS_OK) {
            throw new RuntimeException('单据不是已审核状态，无法反审核');
        }

        if (SaleInOrderModel::query()->where('with_id', $order->id)->exists()) {
            throw new RuntimeException('存在客户退货单，无法反审核');
        }

        if ($order->amount && (int) $order->amount->status === BaseModel::STATUS_OK) {
            throw new RuntimeException('该出货单已月结，无法反审核');
        }

        $histories = StockHistoryModel::query()
            ->where('type', StockHistoryModel::STORE_OUT_TYPE)
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

            if ($order->amount) {
                $order->amount->delete();
            }

            $order->review_status = SaleOutOrderModel::REVIEW_STATUS_REREVIEW;
            $order->apply_at = null;
            $order->save();

            if ($order->with_order) {
                $hasReviewed = SaleOutOrderModel::query()
                    ->where('with_id', $order->with_order->id)
                    ->where('review_status', SaleOutOrderModel::REVIEW_STATUS_OK)
                    ->exists();

                $order->with_order->status = $hasReviewed
                    ? SaleOrderModel::STATUS_SEND
                    : SaleOrderModel::STATUS_DOING;
                $order->with_order->save();
            }
        });
    }
}
