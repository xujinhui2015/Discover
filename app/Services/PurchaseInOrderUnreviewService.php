<?php

namespace App\Services;

use App\Models\BaseModel;
use App\Models\PurchaseInOrderModel;
use App\Models\PurchaseOrderModel;
use App\Models\SkuStockBatchModel;
use App\Models\StockHistoryModel;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PurchaseInOrderUnreviewService
{
    public function unreview(PurchaseInOrderModel $order): void
    {
        $order->loadMissing(['items', 'with_order.items', 'amount']);

        if ((int) $order->review_status !== PurchaseInOrderModel::REVIEW_STATUS_OK) {
            throw new RuntimeException('单据不是已审核状态，无法反审核');
        }

        if (! $order->with_order) {
            throw new RuntimeException('未找到关联采购单据，无法反审核');
        }

        if ($order->amount && (int) $order->amount->status === BaseModel::STATUS_OK) {
            throw new RuntimeException('该入库单已月结，无法反审核');
        }

        $histories = StockHistoryModel::query()
            ->where('type', StockHistoryModel::IN_STOCK_PUCHASE)
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

            if ($order->amount) {
                $order->amount->delete();
            }

            $order->review_status = PurchaseInOrderModel::REVIEW_STATUS_REREVIEW;
            $order->apply_at = null;
            $order->save();

            $this->refreshPurchaseOrderStatus($order->with_order, $scale);
        });
    }

    private function refreshPurchaseOrderStatus(PurchaseOrderModel $purchaseOrder, int $scale): void
    {
        $reviewedOrders = PurchaseInOrderModel::query()
            ->where('with_id', $purchaseOrder->id)
            ->where('review_status', PurchaseInOrderModel::REVIEW_STATUS_OK)
            ->with('items')
            ->get();

        if ($reviewedOrders->isEmpty()) {
            $purchaseOrder->status = PurchaseOrderModel::STATUS_WAIT;
            $purchaseOrder->save();
            return;
        }

        $receivedMap = [];
        foreach ($reviewedOrders as $reviewedOrder) {
            foreach ($reviewedOrder->items as $item) {
                $key = $item->sku_id . '|' . (int) $item->standard;
                $receivedMap[$key] = bcadd($receivedMap[$key] ?? '0', (string) $item->actual_num, $scale);
            }
        }

        $allArrived = true;
        foreach ($purchaseOrder->items as $item) {
            $key = $item->sku_id . '|' . (int) $item->standard;
            $received = $receivedMap[$key] ?? '0';
            if (bccomp($received, (string) $item->should_num, $scale) < 0) {
                $allArrived = false;
                break;
            }
        }

        $purchaseOrder->status = $allArrived
            ? PurchaseOrderModel::STATUS_ARRIVE
            : PurchaseOrderModel::STATUS_PART_RETURNED;
        $purchaseOrder->save();
    }
}
