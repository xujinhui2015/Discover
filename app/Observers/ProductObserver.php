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

use App\Models\AttrModel;
use App\Models\AttrValueModel;
use App\Models\ProductAttrModel;
use App\Models\ProductModel;
use App\Models\SystemConfigModel;
use Dcat\Admin\Admin;
use Illuminate\Support\Facades\DB;

class ProductObserver
{
    /**
     * Handle the product model "created" event.
     *
     * @param  \App\Models\ProductModel $productModel
     * @return void
     */
    public function created(ProductModel $productModel)
    {

    }

    /**
     * Handle the product model "updated" event.
     *
     * @param  \App\Models\ProductModel $productModel
     * @return void
     */
    public function updated(ProductModel $productModel)
    {
        //
    }

    /**
     * Handle the product model "deleted" event.
     *
     * @param  \App\Models\ProductModel $productModel
     * @return void
     */
    public function deleted(ProductModel $productModel)
    {
        //
    }

    /**
     * Handle the product model "restored" event.
     *
     * @param  \App\Models\ProductModel $productModel
     * @return void
     */
    public function restored(ProductModel $productModel)
    {
        //
    }

    /**
     * Handle the product model "force deleted" event.
     *
     * @param  \App\Models\ProductModel $productModel
     * @return void
     */
    public function forceDeleted(ProductModel $productModel)
    {
        //
    }

    /**
     * @param ProductModel $productModel
     */
    public function saving(ProductModel $productModel): void
    {
        // 拼音码
        $productModel->name && $productModel->py_code = up_pinyin_abbr($productModel->name);

        if ($productModel->barcode === null) {
            $productModel->barcode = '';
        }
    }

    /**
     * @param ProductModel $productModel
     */
    public function saved(ProductModel $productModel): void
    {
        if (ProductModel::$skipDefaultAttrBinding) {
            return;
        }

        // 若请求中已经显式传入了规格，跳过自动绑定
        $requestAttrs = request()->input('product_attr');
        if (is_array($requestAttrs)) {
            $hasCustomAttr = collect($requestAttrs)->filter(function (array $row) {
                return ! empty($row['attr_id']) && ! empty($row['attr_value_ids']);
            })->isNotEmpty();
            if ($hasCustomAttr) {
                return;
            }
        }

        // 若商品无规格,自动绑定一个基础规格
        if ($productModel->id && ProductAttrModel::query()
                ->where('product_id', $productModel->id)
                ->doesntExist()) {

            $defaultAttrName = SystemConfigModel::getValue(
                SystemConfigModel::KEY_DEFAULT_ATTR_NAME,
                SystemConfigModel::DEFAULT_ATTR_NAME
            );

            $attr = AttrModel::withoutGlobalScope('status')
                ->withTrashed()
                ->where('name', $defaultAttrName)
                ->first();

            if (! $attr) {
                $attr = AttrModel::create(['name' => $defaultAttrName, 'status' => 1]);
            } else {
                if ($attr->trashed()) {
                    $attr->restore();
                }

                if ((int) ($attr->status ?? 1) !== 1) {
                    $attr->status = 1;
                    $attr->save();
                }
            }

            $defaultAttrValueName = SystemConfigModel::getValue(
                SystemConfigModel::KEY_DEFAULT_ATTR_VALUE_NAME,
                SystemConfigModel::DEFAULT_ATTR_VALUE_NAME
            );

            $attrValues = AttrValueModel::withTrashed()
                ->where('attr_id', $attr->id)
                ->where('name', $defaultAttrValueName)
                ->get();
            if ($attrValues->isEmpty()) {
                $attrValues = collect([
                    AttrValueModel::create([
                        'attr_id'    => $attr->id,
                        'name'       => $defaultAttrValueName,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]),
                ]);
            } else {
                $attrValues->each(function (AttrValueModel $value) {
                    if ($value->trashed()) {
                        $value->restore();
                    }
                });
            }

            // 优先尝试恢复最后删除的 product_attr 记录（按 ID 降序取最新）
            $attrValueIdsArray = $attrValues->pluck('id')->values()->toArray();
            $trashedAttr = ProductAttrModel::onlyTrashed()
                ->where('product_id', $productModel->id)
                ->where('attr_id', $attr->id)
                ->latest('id')
                ->first();

            if ($trashedAttr) {
                $trashedAttr->restore();
                $trashedAttr->attr_value_ids = $attrValueIdsArray;
                $trashedAttr->save();
            } else {
                $productModel->product_attr()->create([
                    'attr_id'        => $attr->id,
                    'attr_value_ids' => $attrValueIdsArray,
                ]);
            }
        }
    }

    /**
     * @param ProductModel $productModel
     */
    public function creating(ProductModel $productModel): void
    {
//        $productModel->create_user_id = Admin::user()->id;
    }
}
