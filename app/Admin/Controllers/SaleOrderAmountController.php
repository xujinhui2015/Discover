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
use App\Admin\Repositories\SaleOrderAmount;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Controllers\AdminController;

class SaleOrderAmountController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new SaleOrderAmount(), function (Grid $grid) {
            // 定义列配置（用于列选择器）
            $columnConfig = [
                ['name' => 'id', 'label' => 'ID'],
                ['name' => 'order_id', 'label' => '订单ID'],
                ['name' => 'customer_id', 'label' => '客户ID'],
                ['name' => 'status', 'label' => '状态'],
                ['name' => 'should_amount', 'label' => '应收金额'],
                ['name' => 'actual_amount', 'label' => '实收金额'],
                ['name' => 'accountant_id', 'label' => '会计期ID'],
                ['name' => 'settlement_at', 'label' => '结算时间'],
                ['name' => 'created_at', 'label' => '创建时间'],
                ['name' => 'updated_at', 'label' => '更新时间'],
            ];
            
            $grid->column('id')->setHeaderAttributes(['class' => 'column-id'])->sortable();
            $grid->column('order_id')->setHeaderAttributes(['class' => 'column-order_id']);
            $grid->column('customer_id')->setHeaderAttributes(['class' => 'column-customer_id']);
            $grid->column('status')->setHeaderAttributes(['class' => 'column-status']);
            $grid->column('should_amount')->setHeaderAttributes(['class' => 'column-should_amount']);
            $grid->column('actual_amount')->setHeaderAttributes(['class' => 'column-actual_amount']);
            $grid->column('accountant_id')->setHeaderAttributes(['class' => 'column-accountant_id']);
            $grid->column('settlement_at')->setHeaderAttributes(['class' => 'column-settlement_at']);
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
        return Show::make($id, new SaleOrderAmount(), function (Show $show) {
            $show->field('id');
            $show->field('order_id');
            $show->field('customer_id');
            $show->field('status');
            $show->field('should_amount');
            $show->field('actual_amount');
            $show->field('accountant_id');
            $show->field('settlement_at');
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
        return Form::make(new SaleOrderAmount(), function (Form $form) {
            $form->display('id');
            $form->text('order_id');
            $form->text('customer_id');
            $form->text('status');
            $form->text('should_amount');
            $form->text('actual_amount');
            $form->text('accountant_id');
            $form->text('settlement_at');

            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
