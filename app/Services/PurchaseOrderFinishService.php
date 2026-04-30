<?php

namespace App\Services;

use App\Models\PurchaseOrderModel;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PurchaseOrderFinishService
{
    public function finish(PurchaseOrderModel $order): void
    {
        if ((int) $order->review_status !== PurchaseOrderModel::REVIEW_STATUS_OK) {
            throw new RuntimeException('单据未审核，无法提前完结');
        }

        if ((int) $order->status !== PurchaseOrderModel::STATUS_PART_RETURNED) {
            throw new RuntimeException('仅部分收货的单据可提前完结');
        }

        DB::transaction(function () use ($order) {
            $order->status = PurchaseOrderModel::STATUS_ARRIVE;
            $order->finished_at = now();
            $order->save();
        });
    }
}
