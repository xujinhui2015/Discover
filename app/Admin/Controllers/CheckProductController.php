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

use App\Admin\Extensions\Grid\ColumnSelector;
use App\Admin\Repositories\CheckProduct;
use App\Models\ProductModel;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Controllers\AdminController;

class CheckProductController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new CheckProduct(), function (Grid $grid) {
            // 定义列配置（用于列选择器）
            $columnConfig = [
                ['name' => 'id', 'label' => 'ID'],
                ['name' => 'check_no', 'label' => '检测编号'],
                ['name' => 'num', 'label' => '数量'],
                ['name' => 'carbon_fiber', 'label' => '含碳纤维'],
                ['name' => 'cashmere_content', 'label' => '含羊绒'],
                ['name' => 'raw_footage', 'label' => '原毛片'],
                ['name' => 'velvet', 'label' => '绒子'],
                ['name' => 'magazine', 'label' => '杂质'],
                ['name' => 'fluffy_silk', 'label' => '蓬松丝'],
                ['name' => 'terrestrial_feather', 'label' => '陆禽羽'],
                ['name' => 'feather_silk', 'label' => '羽丝'],
                ['name' => 'heterochromatic_hair', 'label' => '异色毛'],
                ['name' => 'flower_number', 'label' => '花号'],
                ['name' => 'blackhead', 'label' => '黑头'],
                ['name' => 'cleanliness', 'label' => '清洁度'],
                ['name' => 'moisture', 'label' => '水分'],
                ['name' => 'bulkiness', 'label' => '蓬松度'],
                ['name' => 'odor', 'label' => '气味'],
                ['name' => 'duck_ratio', 'label' => '鸭绒比例'],
                ['name' => 'user_id', 'label' => '用户ID'],
                ['name' => 'created_at', 'label' => '创建时间'],
                ['name' => 'updated_at', 'label' => '更新时间'],
            ];
            
            $grid->column('id')->setHeaderAttributes(['class' => 'column-id'])->sortable();
            $grid->column('check_no')->setHeaderAttributes(['class' => 'column-check_no']);
            $grid->column('num')->setHeaderAttributes(['class' => 'column-num']);
            $grid->column('carbon_fiber')->setHeaderAttributes(['class' => 'column-carbon_fiber']);
            $grid->column('cashmere_content')->setHeaderAttributes(['class' => 'column-cashmere_content']);
            $grid->column('raw_footage')->setHeaderAttributes(['class' => 'column-raw_footage']);
            $grid->column('velvet')->setHeaderAttributes(['class' => 'column-velvet']);
            $grid->column('magazine')->setHeaderAttributes(['class' => 'column-magazine']);
            $grid->column('fluffy_silk')->setHeaderAttributes(['class' => 'column-fluffy_silk']);
            $grid->column('terrestrial_feather')->setHeaderAttributes(['class' => 'column-terrestrial_feather']);
            $grid->column('feather_silk')->setHeaderAttributes(['class' => 'column-feather_silk']);
            $grid->column('heterochromatic_hair')->setHeaderAttributes(['class' => 'column-heterochromatic_hair']);
            $grid->column('flower_number')->setHeaderAttributes(['class' => 'column-flower_number']);
            $grid->column('blackhead')->setHeaderAttributes(['class' => 'column-blackhead']);
            $grid->column('cleanliness')->setHeaderAttributes(['class' => 'column-cleanliness']);
            $grid->column('moisture')->setHeaderAttributes(['class' => 'column-moisture']);
            $grid->column('bulkiness')->setHeaderAttributes(['class' => 'column-bulkiness']);
            $grid->column('odor')->setHeaderAttributes(['class' => 'column-odor']);
            $grid->column('duck_ratio')->setHeaderAttributes(['class' => 'column-duck_ratio']);
            $grid->column('user_id')->setHeaderAttributes(['class' => 'column-user_id']);
            $grid->column('created_at')->setHeaderAttributes(['class' => 'column-created_at']);
            $grid->column('updated_at')->setHeaderAttributes(['class' => 'column-updated_at'])->sortable();
            
            $grid->tools(function ($tools) use ($columnConfig) {
                $tools->append(new ColumnSelector($columnConfig));
            });

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');
            });
        });
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     *
     * @return Show
     */
    protected function detail($id)
    {
        return Show::make($id, new CheckProduct(), function (Show $show) {
            $show->field('id');
            $show->field('check_no');
            $show->field('num');
            $show->field('carbon_fiber');
            $show->field('cashmere_content');
            $show->field('raw_footage');
            $show->field('velvet');
            $show->field('magazine');
            $show->field('fluffy_silk');
            $show->field('terrestrial_feather');
            $show->field('feather_silk');
            $show->field('heterochromatic_hair');
            $show->field('flower_number');
            $show->field('blackhead');
            $show->field('cleanliness');
            $show->field('moisture');
            $show->field('bulkiness');
            $show->field('odor');
            $show->field('duck_ratio');
            $show->field('user_id');
            $show->field('created_at');
            $show->field('updated_at');
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(new CheckProduct(), function (Form $form) {
            $form->row(function (Form\Row $row) {
                $row->width(12)->html('<h1 align="center">物料检验单</h1>');
            });

            $form->row(function (Form\Row $row) {
                $row->width(3)->text('check_no')->default('提交后自动生成')->readOnly();
                $row->width(3)->select('product_id', '物料')
                    ->ajax(route('api.product.search'))
                    ->config('ajax.delay', 100)
                    ->options(function ($id) {
                        if ($id) {
                            return ProductModel::where('id', $id)->pluck('name', 'id');
                        }
                        return ProductModel::orderBy('id', 'desc')->limit(10)->pluck('name', 'id');
                    })
                    ->loadpku(route('api.product.find'))
                    ->required();
                $row->width(3)->select('sku_id', '属性选择')->options()->required();
                $row->width(3)->number('num')->default(0)->required();
            });
            $form->row(function (Form\Row $row) {
                $row->width(12)->html('<hr/><h3>数据明细</h3>');
            });
            $form->row(function (Form\Row $row) {
                $row->width(3)->rate('carbon_fiber')->default(0);
                $row->width(3)->rate('cashmere_content')->default(0)->required();
                $row->width(3)->rate('raw_footage')->default(0);
                $row->width(3)->rate('velvet')->default(0);
            });

            $form->row(function (Form\Row $row) {
                $row->width(3)->rate('magazine')->default(0);
                $row->width(3)->rate('fluffy_silk')->default(0);
                $row->width(3)->rate('terrestrial_feather')->default(0);
                $row->width(3)->rate('feather_silk')->default(0);
            });
            $form->row(function (Form\Row $row) {
                $row->width(3)->rate('heterochromatic_hair')->default(0);
                $row->width(3)->rate('flower_number')->default(0);
                $row->width(3)->rate('blackhead')->default(0);
                $row->width(3)->rate('cleanliness')->default(0);
            });
            $form->row(function (Form\Row $row) {
                $row->width(3)->rate('moisture')->default(0);
                $row->width(3)->rate('bulkiness')->default(0);
                $row->width(3)->rate('odor')->default(0);
                $row->width(3)->rate('duck_ratio')->default(0);
            });

            $form->saving(function (Form $form) {
                if ($form->isCreating()) {
                    $form->check_no = build_order_no('JY');
                }
            });
        });
    }
}
