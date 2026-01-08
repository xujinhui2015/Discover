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

use App\Models\ScrapItemModel;
use App\Models\ScrapOrderModel;
use Dcat\Admin\Support\LazyRenderable;
use Dcat\Admin\Widgets\Table;

class ScrapOrderItemDetail extends LazyRenderable
{
    public function render()
    {
        $order = ScrapOrderModel::query()->find($this->key);
        $scrapTypeStr = $order ? $order->scrap_type_str : '-';
        $items = ScrapItemModel::query()
            ->where('order_id', $this->key)
            ->get()
            ->map(function (ScrapItemModel $itemModel) {
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
                    $itemModel->should_num,
                    $itemModel->actual_num,
                    $itemModel->sku_stock_num,
                ];
            })
            ->map(function (array $row) use ($scrapTypeStr) {
                $row[] = $scrapTypeStr;
                return $row;
            });

        $titles = [
            '物料名称',
            '单位',
            '分类',
            '品牌',
            '属性',
            '通用标准',
            '报废数量',
            '实报数量',
            '库存',
            '报废类型',
        ];

        return Table::make($titles, $items->toArray());
    }
}
