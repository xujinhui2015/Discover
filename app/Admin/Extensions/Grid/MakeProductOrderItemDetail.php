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

use App\Models\MakeProductItemModel;
use Dcat\Admin\Support\LazyRenderable;
use Dcat\Admin\Widgets\Table;

class MakeProductOrderItemDetail extends LazyRenderable
{
    public function render()
    {
        $items = MakeProductItemModel::query()
            ->where('order_id', $this->key)
            ->get()
            ->map(function (MakeProductItemModel $itemModel) {
                $product = data_get($itemModel, 'sku.product');
                $unitName = data_get($product, 'unit.name', '-');
                $brandName = data_get($product, 'brand.name', '-');
                $typeStr = data_get($product, 'type_str', '-');
                $positionName = data_get($itemModel, 'position.name', '-');

                return [
                    data_get($product, 'name', '-'),
                    $unitName,
                    $typeStr,
                    $brandName,
                    data_get($itemModel, 'sku.attr_value_ids_str', '-'),
                    $itemModel->standard_str,
                    $itemModel->cost_price,
                    $itemModel->should_num,
                    $itemModel->actual_num,
                    $positionName,
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
            '成本价格',
            '计划入库数',
            '实际入库数',
            '入库位置',
            '批次号',
        ];

        return Table::make($titles, $items->toArray());
    }
}
