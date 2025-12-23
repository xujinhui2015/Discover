<?php

namespace App\Console\Commands;

use App\Models\BaseModel;
use App\Models\PurchaseInOrderModel;
use App\Models\PurchaseOrderModel;
use App\Models\SkuStockBatchModel;
use App\Models\StockHistoryModel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UnreviewPurchaseInOrderCommand extends Command
{
    protected $signature = 'purchase-in:unreview {order_no : 采购入库单号}';

    protected $description = '采购入库单反审核';

    public function handle(): int
    {
        $orderNo = trim((string) $this->argument('order_no'));
        if ($orderNo === '') {
            $this->error('采购入库单号不能为空');
            return 1;
        }

        $order = PurchaseInOrderModel::query()
            ->with(['items', 'with_order.items', 'amount'])
            ->where('order_no', $orderNo)
            ->first();

        if (! $order) {
            $this->error("未找到采购入库单：{$orderNo}");
            return 1;
        }

        if ((int) $order->review_status !== PurchaseInOrderModel::REVIEW_STATUS_OK) {
            $this->error('单据不是已审核状态，无法反审核');
            return 1;
        }

        if (! $order->with_order) {
            $this->error('未找到关联采购单据，无法反审核');
            return 1;
        }

        if ($order->amount && (int) $order->amount->status === BaseModel::STATUS_OK) {
            $this->error('该入库单已月结，无法反审核');
            return 1;
        }

        $histories = StockHistoryModel::query()
            ->where('type', StockHistoryModel::IN_STOCK_PUCHASE)
            ->where('flag', StockHistoryModel::IN)
            ->where('with_order_no', $order->order_no)
            ->get();

        if ($histories->isEmpty()) {
            $this->error('未找到该单据对应的库存流水，无法反审核');
            return 1;
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
                $this->error("缺少批次库存记录：sku_id={$history->sku_id}，批次={$history->batch_no}");
                return 1;
            }

            if (bccomp((string) $batch->num, (string) $history->in_num, $scale) < 0) {
                $this->error("批次库存不足，无法反审核：sku_id={$history->sku_id}，批次={$history->batch_no}");
                return 1;
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
                    throw new \RuntimeException('批次库存记录丢失，已终止反审核');
                }

                if (bccomp((string) $batch->num, (string) $history->in_num, $scale) < 0) {
                    throw new \RuntimeException('批次库存不足，已终止反审核');
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

        $this->info("反审核完成：{$orderNo}");

        return 0;
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
