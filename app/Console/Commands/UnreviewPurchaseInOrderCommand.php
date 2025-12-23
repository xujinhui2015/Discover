<?php

namespace App\Console\Commands;

use App\Models\PurchaseInOrderModel;
use App\Services\PurchaseInOrderUnreviewService;
use Illuminate\Console\Command;

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
            ->where('order_no', $orderNo)
            ->first();

        if (! $order) {
            $this->error("未找到采购入库单：{$orderNo}");
            return 1;
        }

        try {
            app(PurchaseInOrderUnreviewService::class)->unreview($order);
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());
            return 1;
        }

        $this->info("反审核完成：{$orderNo}");

        return 0;
    }
}
