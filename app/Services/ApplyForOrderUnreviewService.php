<?php

namespace App\Services;

use App\Models\ApplyForOrderModel;
use App\Models\SkuStockBatchModel;
use App\Models\StockHistoryModel;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ApplyForOrderUnreviewService
{
    public function unreview(ApplyForOrderModel $order): void
    {
        $order->loadMissing(['items', 'with_order']);

        if ((int) $order->review_status !== ApplyForOrderModel::REVIEW_STATUS_OK) {
            throw new RuntimeException('单据不是已审核状态，无法反审核');
        }

        if (! $order->with_order) {
            throw new RuntimeException('未找到关联任务单据，无法反审核');
        }

        if ($order->apply_for_return_order()->exists()) {
            throw new RuntimeException('存在返仓单，无法反审核');
        }

        $histories = StockHistoryModel::query()
            ->where('type', StockHistoryModel::COLLECTION_TYPE)
            ->where('flag', StockHistoryModel::OUT)
            ->where('with_order_no', $order->order_no)
            ->get();

        if ($histories->isEmpty()) {
            throw new RuntimeException('未找到该单据对应的库存流水，无法反审核');
        }

        $scale = 3;
        foreach ($histories as $history) {
            $batch = SkuStockBatchModel::query()->where([
                'position_id' => $history->out_position_id,
                'batch_no' => $history->batch_no,
                'sku_id' => $history->sku_id,
                'standard' => (int) $history->standard,
            ])->first();

            if (! $batch) {
                throw new RuntimeException("缺少批次库存记录：sku_id={$history->sku_id}，批次={$history->batch_no}");
            }
        }

        DB::transaction(function () use ($order, $histories, $scale) {
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

                $batch->num = bcadd((string) $batch->num, (string) $history->out_num, $scale);
                $batch->save();

                $history->delete();
            }

            $order->review_status = ApplyForOrderModel::REVIEW_STATUS_REREVIEW;
            $order->save();
        });
    }
}
