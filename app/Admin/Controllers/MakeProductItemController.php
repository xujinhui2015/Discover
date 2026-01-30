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
use App\Admin\Repositories\MakeProductItem;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Controllers\AdminController;

class MakeProductItemController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new MakeProductItem(), function (Grid $grid) {
            // 定义列配置（用于列选择器）
            $columnConfig = [
                ['name' => 'id', 'label' => 'ID'],
                ['name' => 'order_id', 'label' => '订单ID'],
                ['name' => 'sku_id', 'label' => 'SKU ID'],
                ['name' => 'should_num', 'label' => '应入数量'],
                ['name' => 'actual_num', 'label' => '实入数量'],
                ['name' => 'cost_price', 'label' => '成本价'],
                ['name' => 'position_id', 'label' => '仓位ID'],
                ['name' => 'batch_no', 'label' => '批次号'],
                ['name' => 'standard', 'label' => '标准'],
                ['name' => 'created_at', 'label' => '创建时间'],
                ['name' => 'updated_at', 'label' => '更新时间'],
            ];
            
            $grid->column('id')->setHeaderAttributes(['class' => 'column-id'])->sortable();
            $grid->column('order_id')->setHeaderAttributes(['class' => 'column-order_id']);
            $grid->column('sku_id')->setHeaderAttributes(['class' => 'column-sku_id']);
            $grid->column('should_num')->setHeaderAttributes(['class' => 'column-should_num']);
            $grid->column('actual_num')->setHeaderAttributes(['class' => 'column-actual_num']);
            $grid->column('cost_price')->setHeaderAttributes(['class' => 'column-cost_price']);
            $grid->column('position_id')->setHeaderAttributes(['class' => 'column-position_id']);
            $grid->column('batch_no')->setHeaderAttributes(['class' => 'column-batch_no']);
//            $grid->column('percent');
            $grid->column('standard')->setHeaderAttributes(['class' => 'column-standard']);
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
        return Show::make($id, new MakeProductItem(), function (Show $show) {
            $show->field('id');
            $show->field('order_id');
            $show->field('sku_id');
            $show->field('should_num');
            $show->field('actual_num');
            $show->field('cost_price');
            $show->field('position_id');
            $show->field('batch_no');
//            $show->field('percent');
            $show->field('standard');
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
        return Form::make(new MakeProductItem(), function (Form $form) {
            $form->display('id');
            $form->text('order_id');
            $form->text('sku_id');
            $form->text('should_num');
            $form->text('actual_num');
            $form->text('cost_price');
            $form->text('position_id');
            $form->text('batch_no');
//            $form->text('percent');
            $form->text('standard');

            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
