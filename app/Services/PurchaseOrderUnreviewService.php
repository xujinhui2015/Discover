<?php

namespace App\Services;

use App\Models\PurchaseInOrderModel;
use App\Models\PurchaseOrderModel;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PurchaseOrderUnreviewService
{
    public function unreview(PurchaseOrderModel $order): void
    {
        if ((int) $order->review_status !== PurchaseOrderModel::REVIEW_STATUS_OK) {
            throw new RuntimeException('单据不是已审核状态，无法反审核');
        }

        if (PurchaseInOrderModel::query()->where('with_id', $order->id)->exists()) {
            throw new RuntimeException('存在采购入库单，无法反审核');
        }

        DB::transaction(function () use ($order) {
            $order->review_status = PurchaseOrderModel::REVIEW_STATUS_REREVIEW;
            $order->save();
        });
    }
}
