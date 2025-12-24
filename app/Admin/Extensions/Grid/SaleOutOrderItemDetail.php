<?php

/*
 * // +----------------------------------------------------------------------
 * // | erp
 * // +----------------------------------------------------------------------
 * // | Copyright (c) 2006~2020 erp All rights reserved.
 * // +----------------------------------------------------------------------
 * // | Licensed ( LICENSE-1.0.0 )
 * // +----------------------------------------------------------------------
 */

namespace App\Admin\Extensions\Grid;

use App\Models\SaleOutItemModel;
use Dcat\Admin\Support\LazyRenderable;
use Dcat\Admin\Widgets\Table;

class SaleOutOrderItemDetail extends LazyRenderable
{
    public function render()
    {
        $items = SaleOutItemModel::query()
            ->where('order_id', $this->key)
            ->get()
            ->map(function (SaleOutItemModel $itemModel) {
                $product = data_get($itemModel, 'sku.product');
                $unitName = data_get($product, 'unit.name', '');
                $brandName = data_get($product, 'brand.name', '');
                $typeStr = data_get($product, 'type_str', '');

                return [
                    data_get($product, 'name', ''),
                    $unitName,
                    $typeStr,
                    $brandName,
                    data_get($itemModel, 'sku.attr_value_ids_str', ''),
                    $itemModel->standard_str,
                    $itemModel->sku_stock_num,
                    $itemModel->should_num,
                    $itemModel->actual_num,
                    $itemModel->price,
                    bcmul($itemModel->actual_num, $itemModel->price, 2),
                ];
            });

        $titles = [
            '物料名称',
            '单位',
            '分类',
            '品牌',
            '属性',
            '通用标准',
            '库存',
            '要货数量',
            '销售数量',
            '销售价格',
            '合计',
        ];

        return Table::make($titles, $items->toArray());
    }
}
