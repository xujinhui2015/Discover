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

use App\Models\InitStockItemModel;
use Dcat\Admin\Support\LazyRenderable;
use Dcat\Admin\Widgets\Table;

class InitStockOrderItemDetail extends LazyRenderable
{
    public function render()
    {
        $items = InitStockItemModel::query()
            ->where('order_id', $this->key)
            ->with(['sku.product', 'position'])
            ->get()
            ->map(function (InitStockItemModel $itemModel) {
                $product = data_get($itemModel, 'sku.product');
                $unitName = data_get($product, 'unit.name', '');
                $brandName = data_get($product, 'brand.name', '');
                $typeStr = data_get($product, 'type_str', '');
                $positionName = data_get($itemModel, 'position.name', '');

                return [
                    data_get($product, 'name', ''),
                    $unitName,
                    $typeStr,
                    $brandName,
                    data_get($itemModel, 'sku.attr_value_ids_str', ''),
                    $itemModel->standard_str,
                    $positionName,
                    $itemModel->actual_num,
                    $itemModel->cost_price,
                    $itemModel->batch_no,
                ];
            });

        $titles = [
            '物料名称',
            '单位',
            '分类',
            '品牌',
            '属性',
            '通用标准',
            '入库位置',
            '期初库存',
            '成本单价',
            '批次号',
        ];

        return Table::make($titles, $items->toArray());
    }
}
