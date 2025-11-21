<?php

namespace App\Observers;

use App\Models\SaleInItemModel;
use App\Models\SaleInOrderModel;
use App\Models\SkuStockModel;
use App\Models\StockHistoryModel;
use Dcat\Admin\Admin;

class SaleInOrderObserver
{
    public function creating(SaleInOrderModel $saleInOrderModel): void
    {
        $saleInOrderModel->user_id = Admin::user()->id;
    }

    public function saving(SaleInOrderModel $saleInOrderModel): void
    {
        if ($saleInOrderModel->isDirty('review_status')
            && (int)$saleInOrderModel->review_status === SaleInOrderModel::REVIEW_STATUS_OK
        ) {
            $saleInOrderModel->items->each(function (SaleInItemModel $item) use ($saleInOrderModel) {
                $init_num = SkuStockModel::where([
                    'sku_id' => $item->sku_id,
                    'standard' => $item->standard,
                ])->value('num');

                // 从客户出货单的对应明细中获取批次信息
                $outItem = $saleInOrderModel->with_order->items()
                    ->where('sku_id', $item->sku_id)
                    ->where('standard', $item->standard)
                    ->first();

                // 获取第一个批次的 position_id 和 batch_no（如果有多个批次，取第一个）
                $positionId = null;
                $batchNo = null;
                if ($outItem && $outItem->batchs->isNotEmpty()) {
                    $firstBatch = $outItem->batchs->first();
                    if ($firstBatch && $firstBatch->stock_batch) {
                        $positionId = $firstBatch->stock_batch->position_id;
                        $batchNo = $firstBatch->stock_batch->batch_no;
                    }
                }

                StockHistoryModel::create([
                    'sku_id' => $item->sku_id,
                    'in_position_id' => $positionId ?? $item->position_id,
                    'cost_price' => $item->price,
                    'type' => StockHistoryModel::STORE_IN_TYPE,
                    'flag' => StockHistoryModel::IN,
                    'with_order_no' => $saleInOrderModel->order_no,
                    'init_num' => $init_num ?? 0,
                    'in_num' => $item->return_num,
                    'in_price' => $item->price,
                    'balance_num' => ($init_num ?? 0) + $item->return_num,
                    'standard' => $item->standard,
                    'user_id' => Admin::user()->id,
                    'batch_no' => $batchNo ?? $item->batch_no,
                ]);
            });
        }
    }
}
