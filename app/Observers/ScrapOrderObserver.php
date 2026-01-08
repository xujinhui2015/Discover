<?php

/*
 * // +----------------------------------------------------------------------
 * // | erp
 * // +----------------------------------------------------------------------
 * // | Copyright (c) 2006~2020 erp All rights reserved.
 * // +----------------------------------------------------------------------
 * // | Licensed ( LICENSE-1.0.0 )
 * // +----------------------------------------------------------------------
 * // | Author: yxx <1365831278@qq.com>
 * // +----------------------------------------------------------------------
 */

namespace App\Observers;

use App\Models\ScrapBatchModel;
use App\Models\ScrapItemModel;
use App\Models\ScrapOrderModel;
use App\Models\SkuStockModel;
use App\Models\StockHistoryModel;
use Dcat\Admin\Admin;

class ScrapOrderObserver
{
    public function creating(ScrapOrderModel $scrapOrderModel): void
    {
        $scrapOrderModel->user_id = Admin::user()->id;
    }

    public function saving(ScrapOrderModel $scrapOrderModel): void
    {
        if ($scrapOrderModel->isDirty('review_status')
            && (int) $scrapOrderModel->review_status === ScrapOrderModel::REVIEW_STATUS_OK
        ) {
            $scrapOrderModel->items->each(function (ScrapItemModel $scrapItemModel) use ($scrapOrderModel) {
                $scrapItemModel->batchs->each(function (ScrapBatchModel $scrapBatchModel) use ($scrapItemModel, $scrapOrderModel) {
                    $initNum = SkuStockModel::query()
                        ->where([
                            'sku_id' => $scrapItemModel->sku_id,
                            'standard' => $scrapItemModel->standard,
                        ])
                        ->value('num');

                    StockHistoryModel::create([
                        'sku_id' => $scrapItemModel->sku_id,
                        'out_position_id' => $scrapBatchModel->stock_batch->position_id,
                        'cost_price' => $scrapBatchModel->stock_batch->cost_price,
                        'type' => StockHistoryModel::SCRAP_TYPE,
                        'flag' => StockHistoryModel::OUT,
                        'with_order_no' => $scrapOrderModel->order_no,
                        'init_num' => $initNum ?? 0,
                        'out_num' => $scrapBatchModel->actual_num,
                        'out_price' => $scrapBatchModel->stock_batch->cost_price,
                        'balance_num' => ($initNum ?? 0) - $scrapBatchModel->actual_num,
                        'standard' => $scrapItemModel->standard,
                        'user_id' => Admin::user()->id,
                        'batch_no' => $scrapBatchModel->stock_batch->batch_no,
                    ]);
                });
            });
        }
    }
}
