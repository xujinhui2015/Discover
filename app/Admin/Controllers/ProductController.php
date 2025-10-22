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

namespace App\Admin\Controllers;

use App\Admin\Actions\Grid\BatchCreateProSave;
use App\Admin\Actions\Grid\BatchDeleteProduct;
use App\Admin\Repositories\Product;
use App\Models\AttrModel;
use App\Models\BrandModel;
use App\Models\ProductCategoryModel;
use App\Models\ProductModel;
use App\Repositories\BrandRepository;
use App\Repositories\ProductRepository;
use App\Repositories\UnitRepository;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Controllers\AdminController;
use Illuminate\Support\Collection;


class ProductController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new Product(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('item_no')->emp();
            $grid->column('name')->emp();
//            $grid->column('py_code')->emp();
            $grid->column('type', '分类')->using(ProductModel::TYPE);
            $grid->column('brand.name', '品牌')->emp();
            $grid->column('unit.name', '单位')->emp();
            $grid->column('warning_num')->emp();
//            $grid->column('created_at');
//            $grid->column('updated_at')->sortable();

            $grid->batchActions([
                new BatchDeleteProduct(),
            ]);

            $grid->filter(function (Grid\Filter $filter) {
            });
        });
    }

    /**
     * @return Grid
     */
    public function iFrameGrid()
    {
        return Grid::make(new Product(), function (Grid $grid) {
            $grid->model()->whereHas('sku');
            $grid->column('id')->sortable();
            $grid->column('item_no');
            $grid->column('name');
//            $grid->column('py_code');
            $grid->column('type', '分类')->using(ProductModel::TYPE);
            $grid->column('brand.name', '品牌')->emp();
            $grid->column('unit.name', '单位')->emp();
//            $grid->column('created_at');
//            $grid->column('updated_at')->sortable();
            $grid->disableCreateButton();
            $grid->disableActions();

            $grid->tools(BatchCreateProSave::make());

            $grid->filter(function (Grid\Filter $filter) {
            });
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(new Product(['product_attr']), function (Form $form) {
            $form->row(function (Form\Row $row) use ($form) {
                $row->width(6)->text('item_no')
                    ->default(ProductRepository::buildItemNo())
                    ->creationRules(['unique:product'])
                    ->updateRules(['unique:product,item_no,{{id}}'])
                    ->help('用于商家内部管理所使用的自定义编码')
                    ->required();
                $row->width(6)->text('name')->required();
            });

            $form->row(function (Form\Row $row) use ($form) {

                $brands = BrandRepository::pluck('name', 'id');;
                $row->width(6)->select('brand_id', '品牌')
                    ->options($brands)
                    ->default(head($brands->keys()->toArray()) ?? '')
                    ->required();

                $row->select('type', '分类')
                    ->options(ProductModel::TYPE)
                    ->default(ProductModel::TYPE_NOT_FINISH)
                    ->required();


            });

            $form->row(function (Form\Row $row) use ($form) {
//                $row->width(6)->select('product_category_id', '分类')
//                    ->options(ProductCategoryModel::selectOptions())
//                    ->default(0)
//                    ->required();

                $units = UnitRepository::pluck('name', 'id');
                $row->width(6)->select('unit_id', '单位')
                    ->options($units)
                    ->default(head($units->keys()->toArray()) ?? '')
                    ->required();

                $row->width(6)->number('warning_num')
                    ->default(0)
                    ->help('填0则不预警')
                    ->required();
            });

//            $form->row(function (Form\Row $row) use ($form) {
//                $row->width(6)->number('warning_num')
//                    ->default(0)
//                    ->help('填0则不预警')
//                    ->required();
//            });

            $form->row(function (Form\Row $row) use ($form) {
                $row->hasMany('product_attr', '', function (Form\NestedForm $table) {
                    $table->select('attr_id', '属性')->options(AttrModel::pluck('name', 'id'))->required()->load('attr_value_ids', route('api.attrvalue.find'));
                    $table->multipleSelect('attr_value_ids', '属性值')->options();
                })->width(12)->enableHorizontal()->useTable();
            });
            $form->saved(function (Form $form, $result) {
                $id = $form->getKey();
                $product = ProductModel::findOrFail($id);
                $attr = collect($product->attr_value_arr)->keys()->diff($product->sku->pluck('attr_value_ids'))->map(function (string $val) {
                    return ['attr_value_ids' => $val];
                })->values()->toArray();
                $attr && $product->sku()->createMany($attr);
            });
        });
    }
}
