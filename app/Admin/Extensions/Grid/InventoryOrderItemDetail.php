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

namespace App\Admin\Extensions\Grid;

use App\Models\InventoryItemModel;
use Dcat\Admin\Support\LazyRenderable;
use Dcat\Admin\Widgets\Table;

class InventoryOrderItemDetail extends LazyRenderable
{
    public function render()
    {
        $items = InventoryItemModel::query()
            ->where('order_id', $this->key)
            ->with(['stock_batch.sku.product'])
            ->get()
            ->map(function (InventoryItemModel $itemModel) {
                $product = data_get($itemModel, 'stock_batch.sku.product');

                return [
                    data_get($product, 'name', ''),
                    data_get($itemModel, 'stock_batch.sku.attr_value_ids_str', ''),
                    data_get($itemModel, 'stock_batch.batch_no', ''),
                    data_get($itemModel, 'stock_batch.standard_str', ''),
                    $itemModel->cost_price,
                    $itemModel->should_num,
                    $itemModel->actual_num,
                    $itemModel->diff_num,
                    bcmul($itemModel->diff_num, $itemModel->cost_price, 2),
                ];
            });

        $titles = [
            '物料名称',
            '属性',
            '批次号',
            '通用标准',
            '成本单价',
            '库存数量',
            '实盘数量',
            '盈亏数量',
            '盈亏金额',
        ];

        return Table::make($titles, $items->toArray());
    }
}
