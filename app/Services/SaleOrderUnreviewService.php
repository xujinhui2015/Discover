<?php

namespace App\Services;

use App\Models\SaleOrderModel;
use App\Models\SaleOutOrderModel;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SaleOrderUnreviewService
{
    public function unreview(SaleOrderModel $order): void
    {
        if ((int) $order->review_status !== SaleOrderModel::REVIEW_STATUS_OK) {
            throw new RuntimeException('单据不是已审核状态，无法反审核');
        }

        if (SaleOutOrderModel::query()->where('with_id', $order->id)->exists()) {
            throw new RuntimeException('存在客户出货单，无法反审核');
        }

        DB::transaction(function () use ($order) {
            $order->review_status = SaleOrderModel::REVIEW_STATUS_REREVIEW;
            $order->save();
        });
    }
}
