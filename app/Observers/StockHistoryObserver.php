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

use App\Models\SkuStockBatchModel;
use App\Models\StockHistoryModel;
use Illuminate\Support\Facades\DB;

class StockHistoryObserver
{
    public function saved(StockHistoryModel $stockHistoryModel): void
    {
        switch ($stockHistoryModel->type) {
            // 采购入库单
            case StockHistoryModel::IN_STOCK_PUCHASE:
            case StockHistoryModel::PRO_STOCK_TYPE:
            case StockHistoryModel::INIT_TYPE:
            case StockHistoryModel::CHECK_IN_TYPE:
            // 物料返仓单
            case StockHistoryModel::RETURN_TO_WAREHOUSE_TYPE:
                SkuStockBatchModel::updateOrCreate([
                    'position_id' => $stockHistoryModel->in_position_id,
                    'batch_no'    => $stockHistoryModel->batch_no,
                    'sku_id'      => $stockHistoryModel->sku_id,
//                    'percent'     => $stockHistoryModel->percent,
                    'standard'       => $stockHistoryModel->standard,
                ], [
                    'num'        => DB::raw("num + $stockHistoryModel->in_num"),
                    'cost_price' => $stockHistoryModel->cost_price,
                ]);
                break;
            case StockHistoryModel::INVENTORY_TYPE:
                $skuStockBatch = SkuStockBatchModel::updateOrCreate(
                    [
                        'position_id' => $stockHistoryModel->in_position_id,
                        'batch_no'    => $stockHistoryModel->batch_no,
                        'sku_id'      => $stockHistoryModel->sku_id,
//                        'percent'     => $stockHistoryModel->percent,
                        'standard'       => $stockHistoryModel->standard,
                    ]
                );
                $skuStockBatch->num = $stockHistoryModel->inventory_num;
                $skuStockBatch->saveOrFail();
                break;
            // 销售出库单
            case StockHistoryModel::OUT_STOCK_PUCHASE:
            case StockHistoryModel::CHECK_OUT_TYPE:
            case StockHistoryModel::COLLECTION_TYPE:
            case StockHistoryModel::STORE_OUT_TYPE:
            case StockHistoryModel::SCRAP_TYPE:
                SkuStockBatchModel::updateOrCreate([
                    'position_id' => $stockHistoryModel->out_position_id,
                    'batch_no'    => $stockHistoryModel->batch_no,
                    'sku_id'      => $stockHistoryModel->sku_id,
//                    'percent'     => $stockHistoryModel->percent,
                    'standard'       => $stockHistoryModel->standard,
                ], [
                    'num' => DB::raw("num - $stockHistoryModel->out_num"),
                ]);
                break;
            case StockHistoryModel::TRANSFER_TYPE:
                // 新调拨逻辑：分为出库/入库两条记录，按 flag 区分；
                // 兼容旧数据（flag 未设置或为 TRANSFER）时，仍执行双向更新。
                if ($stockHistoryModel->flag === StockHistoryModel::OUT) {
                    SkuStockBatchModel::updateOrCreate([
                        'position_id' => $stockHistoryModel->out_position_id,
                        'batch_no'    => $stockHistoryModel->batch_no,
                        'sku_id'      => $stockHistoryModel->sku_id,
//                    'percent'     => $stockHistoryModel->percent,
                        'standard'       => $stockHistoryModel->standard,
                    ], [
                        'num' => DB::raw("num - $stockHistoryModel->out_num"),
                    ]);
                    break;
                }

                if ($stockHistoryModel->flag === StockHistoryModel::IN) {
                    SkuStockBatchModel::updateOrCreate([
                        'position_id' => $stockHistoryModel->in_position_id,
                        'batch_no'    => $stockHistoryModel->batch_no,
                        'sku_id'      => $stockHistoryModel->sku_id,
//                    'percent'     => $stockHistoryModel->percent,
                        'standard'       => $stockHistoryModel->standard,
                    ], [
                        'num'        => DB::raw("num + $stockHistoryModel->in_num"),
                        'cost_price' => $stockHistoryModel->cost_price,
                    ]);
                    break;
                }

                // 兼容旧的单条调拨记录：同时扣减出库仓、增加入库仓
                SkuStockBatchModel::updateOrCreate([
                    'position_id' => $stockHistoryModel->out_position_id,
                    'batch_no'    => $stockHistoryModel->batch_no,
                    'sku_id'      => $stockHistoryModel->sku_id,
//                    'percent'     => $stockHistoryModel->percent,
                    'standard'       => $stockHistoryModel->standard,
                ], [
                    'num' => DB::raw("num - $stockHistoryModel->out_num"),
                ]);

                SkuStockBatchModel::updateOrCreate([
                    'position_id' => $stockHistoryModel->in_position_id,
                    'batch_no'    => $stockHistoryModel->batch_no,
                    'sku_id'      => $stockHistoryModel->sku_id,
//                    'percent'     => $stockHistoryModel->percent,
                    'standard'       => $stockHistoryModel->standard,
                ], [
                    'num'        => DB::raw("num + $stockHistoryModel->in_num"),
                    'cost_price' => $stockHistoryModel->cost_price,
                ]);
                break;
        }
    }
}
