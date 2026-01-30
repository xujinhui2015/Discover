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
use App\Admin\Repositories\SaleInItem;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Controllers\AdminController;

class SaleInItemController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new SaleInItem(), function (Grid $grid) {
            $columnConfig = [
                ['name' => 'id', 'label' => 'ID'],
                ['name' => 'order_id', 'label' => '订单ID'],
                ['name' => 'sku_id', 'label' => 'SKU ID'],
                ['name' => 'should_num', 'label' => '应退数量'],
                ['name' => 'actual_num', 'label' => '实退数量'],
                ['name' => 'return_num', 'label' => '退货数量'],
                ['name' => 'price', 'label' => '价格'],
                ['name' => 'batch_no', 'label' => '批次号'],
                ['name' => 'position_id', 'label' => '仓位ID'],
                ['name' => 'created_at', 'label' => '创建时间'],
                ['name' => 'updated_at', 'label' => '更新时间'],
            ];
            
            $grid->column('id')->setHeaderAttributes(['class' => 'column-id'])->sortable();
            $grid->column('order_id')->setHeaderAttributes(['class' => 'column-order_id']);
            $grid->column('sku_id')->setHeaderAttributes(['class' => 'column-sku_id']);
            $grid->column('should_num')->setHeaderAttributes(['class' => 'column-should_num']);
            $grid->column('actual_num')->setHeaderAttributes(['class' => 'column-actual_num']);
            $grid->column('return_num')->setHeaderAttributes(['class' => 'column-return_num']);
            $grid->column('price')->setHeaderAttributes(['class' => 'column-price']);
            $grid->column('batch_no')->setHeaderAttributes(['class' => 'column-batch_no']);
            $grid->column('position_id')->setHeaderAttributes(['class' => 'column-position_id']);
            $grid->column('created_at')->setHeaderAttributes(['class' => 'column-created_at']);
            $grid->column('updated_at')->setHeaderAttributes(['class' => 'column-updated_at'])->sortable();

            $grid->tools(function ($tools) use ($columnConfig) {
                $tools->append(new ColumnSelector($columnConfig));
            });

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
        return Form::make(new SaleInItem(), function (Form $form) {
            $form->display('id');
            $form->text('actual_num');
            $form->text('batch_no');
            $form->text('order_id');
            $form->text('position_id');
            $form->text('price');
            $form->text('return_num');
            $form->text('should_num');
            $form->text('sku_id');

            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
