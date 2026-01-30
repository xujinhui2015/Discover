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
use App\Admin\Repositories\InventoryItem;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Controllers\AdminController;

class InventoryItemController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new InventoryItem(), function (Grid $grid) {
            $columnConfig = [
                ['name' => 'id', 'label' => 'ID'],
                ['name' => 'order_id', 'label' => '订单ID'],
                ['name' => 'stock_batch_id', 'label' => '批次ID'],
                ['name' => 'should_num', 'label' => '账面数量'],
                ['name' => 'actual_num', 'label' => '实际数量'],
                ['name' => 'diff_num', 'label' => '盈亏数量'],
                ['name' => 'created_at', 'label' => '创建时间'],
                ['name' => 'updated_at', 'label' => '更新时间'],
            ];
            
            $grid->column('id')->setHeaderAttributes(['class' => 'column-id'])->sortable();
            $grid->column('order_id')->setHeaderAttributes(['class' => 'column-order_id']);
            $grid->column('stock_batch_id')->setHeaderAttributes(['class' => 'column-stock_batch_id']);
            $grid->column('should_num')->setHeaderAttributes(['class' => 'column-should_num']);
            $grid->column('actual_num')->setHeaderAttributes(['class' => 'column-actual_num']);
            $grid->column('diff_num')->setHeaderAttributes(['class' => 'column-diff_num']);
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
        return Show::make($id, new InventoryItem(), function (Show $show) {
            $show->field('id');
            $show->field('order_id');
            $show->field('stock_batch_id');
            $show->field('should_num');
            $show->field('actual_num');
            $show->field('diff_num');
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
        return Form::make(new InventoryItem(), function (Form $form) {
            $form->display('id');
            $form->text('order_id');
            $form->text('stock_batch_id');
            $form->text('should_num');
            $form->text('actual_num');
            $form->text('diff_num');

            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
