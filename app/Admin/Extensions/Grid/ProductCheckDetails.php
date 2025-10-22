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

use App\Models\CheckProductModel;
use Dcat\Admin\Support\LazyRenderable;
use Dcat\Admin\Widgets\Table;

class ProductCheckDetails extends LazyRenderable
{
    public function render()
    {
        $id = $this->key;
        $checkProductDetails = CheckProductModel::query()->where('sku_stock_batch_id', $id)->orderBy('id', 'desc')->get();

        $checkProductDetails->transform(function (CheckProductModel $model, $key) {
            return [
                $key + 1,
                $model->standard_str,
//                $model->percent,
                $model->user->name,
//                $model->carbon_fiber,
                $model->raw_footage,
                $model->velvet,
                $model->magazine,
                $model->fluffy_silk,
                $model->terrestrial_feather,
                $model->feather_silk,
                $model->heterochromatic_hair,
                $model->flower_number,
                $model->blackhead,
                $model->cleanliness,
                $model->moisture,
                $model->bulkiness,
                $model->odor,
                $model->duck_ratio,
            ];
        });
        $titles = [
            '序号',
            '通用标准',
//            '含绒量（%）',
            '质检员',
//            '碳纤维',

//            '毛片',
            '香精含量',
//            '朵绒',
            '乙醇含量',
//            '杂志',
            '甲醇含量',
//            '绒丝',
            '单一受限香料（如麝香）',
//            '陆禽毛',
            '26 种致敏原总量',
//            '羽丝',
            '邻苯二甲酸盐（如 DEHP）',
//            '异色毛',
            '重金属（以铅为例）',
//            '朵数',
            '水分含量',
//            '黑头',
            '澄清度（浊度）',
//            '清洁度',
            'IFRA 合规认证核查',
//            '水份',
            '留香时间',
//            '蓬松度',
            '配方成分合规性',
//            '气味',
            '香气纯度',
//            '鸭比',
            '儿童用香水醛类含量',
        ];

        return Table::make($titles, $checkProductDetails->toArray());
    }
}
