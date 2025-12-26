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

use App\Models\ApplyForReturnItemModel;
use Dcat\Admin\Support\LazyRenderable;
use Dcat\Admin\Widgets\Table;

class ApplyForReturnOrderItemDetail extends LazyRenderable
{
    public function render()
    {
        $items = ApplyForReturnItemModel::query()
            ->where('order_id', $this->key)
            ->get()
            ->map(function (ApplyForReturnItemModel $itemModel) {
                $product = data_get($itemModel, 'sku.product');
                $unitName = data_get($product, 'unit.name', '');
                $brandName = data_get($product, 'brand.name', '');
                $typeStr = data_get($product, 'type_str', '');

                return [
                    data_get($product, 'name', ''),
                    $itemModel->standard_str,
                    $unitName,
                    $typeStr,
                    $brandName,
                    data_get($itemModel, 'sku.attr_value_ids_str', ''),
                    $itemModel->should_num,
                ];
            });

        $titles = [
            '物料名称',
            '通用标准',
            '单位',
            '分类',
            '品牌',
            '属性',
            '退数',
        ];

        return Table::make($titles, $items->toArray());
    }
}
