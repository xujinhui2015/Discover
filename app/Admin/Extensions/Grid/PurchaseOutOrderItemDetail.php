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

use App\Models\PurchaseOutItemModel;
use Dcat\Admin\Support\LazyRenderable;
use Dcat\Admin\Widgets\Table;

class PurchaseOutOrderItemDetail extends LazyRenderable
{
    public function render()
    {
        $items = PurchaseOutItemModel::query()
            ->where('order_id', $this->key)
            ->get()
            ->map(function (PurchaseOutItemModel $itemModel) {
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
                    $itemModel->batch_no,
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
            '出库位置',
            '批次号',
            '入库数量',
            '退货数量',
            '退货价格',
            '合计',
        ];

        return Table::make($titles, $items->toArray());
    }
}
