<?php

namespace App\Services;

use App\Models\MakeProductOrderModel;
use App\Models\SkuStockBatchModel;
use App\Models\StockHistoryModel;
use App\Models\TaskModel;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MakeProductOrderUnreviewService
{
    public function unreview(MakeProductOrderModel $order): void
    {
        $order->loadMissing(['items', 'with_order']);

        if ((int) $order->review_status !== MakeProductOrderModel::REVIEW_STATUS_OK) {
            throw new RuntimeException('单据不是已审核状态，无法反审核');
        }

        if (! $order->with_order) {
            throw new RuntimeException('未找到关联任务单据，无法反审核');
        }

        if (! $order->items) {
            throw new RuntimeException('订单明细不能为空');
        }

        $histories = StockHistoryModel::query()
            ->where('type', StockHistoryModel::PRO_STOCK_TYPE)
            ->where('flag', StockHistoryModel::IN)
            ->where('with_order_no', $order->order_no)
            ->get();

        if ($histories->isEmpty()) {
            throw new RuntimeException('未找到该单据对应的库存流水，无法反审核');
        }

        $scale = 3;
        foreach ($histories as $history) {
            $batch = SkuStockBatchModel::query()->where([
                'position_id' => $history->in_position_id,
                'batch_no' => $history->batch_no,
                'sku_id' => $history->sku_id,
                'standard' => (int) $history->standard,
            ])->first();

            if (! $batch) {
                throw new RuntimeException("缺少批次库存记录：sku_id={$history->sku_id}，批次={$history->batch_no}");
            }

            if (bccomp((string) $batch->num, (string) $history->in_num, $scale) < 0) {
                throw new RuntimeException("批次库存不足，无法反审核：sku_id={$history->sku_id}，批次={$history->batch_no}");
            }
        }

        DB::transaction(function () use ($order, $histories, $scale) {
            foreach ($histories as $history) {
                $batch = SkuStockBatchModel::query()
                    ->where([
                        'position_id' => $history->in_position_id,
                        'batch_no' => $history->batch_no,
                        'sku_id' => $history->sku_id,
                        'standard' => (int) $history->standard,
                    ])
                    ->lockForUpdate()
                    ->first();

                if (! $batch) {
                    throw new RuntimeException('批次库存记录丢失，已终止反审核');
                }

                if (bccomp((string) $batch->num, (string) $history->in_num, $scale) < 0) {
                    throw new RuntimeException('批次库存不足，已终止反审核');
                }

                $batch->num = bcsub((string) $batch->num, (string) $history->in_num, $scale);
                $batch->save();

                $history->delete();
            }

            $order->review_status = MakeProductOrderModel::REVIEW_STATUS_REREVIEW;
            $order->save();

            $order->with_order->status = TaskModel::STATUS_DRAW;
            $order->with_order->finish_num = 0;
            $order->with_order->save();
        });
    }
}
