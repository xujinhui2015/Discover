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
use App\Admin\Repositories\ScrapItem;
use Dcat\Admin\Controllers\AdminController;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;

class ScrapItemController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new ScrapItem(), function (Grid $grid) {
            $columnConfig = [
                ['name' => 'id', 'label' => 'ID'],
                ['name' => 'order_id', 'label' => '订单ID'],
                ['name' => 'sku_id', 'label' => 'SKU ID'],
                ['name' => 'standard', 'label' => '标准'],
                ['name' => 'actual_num', 'label' => '报废数量'],
                ['name' => 'created_at', 'label' => '创建时间'],
                ['name' => 'updated_at', 'label' => '更新时间'],
            ];
            
            $grid->column('id')->setHeaderAttributes(['class' => 'column-id'])->sortable();
            $grid->column('order_id')->setHeaderAttributes(['class' => 'column-order_id']);
            $grid->column('sku_id')->setHeaderAttributes(['class' => 'column-sku_id']);
            $grid->column('standard')->setHeaderAttributes(['class' => 'column-standard']);
            $grid->column('actual_num')->setHeaderAttributes(['class' => 'column-actual_num']);
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
        return Form::make(new ScrapItem(), function (Form $form) {
            $form->display('id');
            $form->text('order_id');
            $form->text('sku_id');
            $form->text('standard');
            $form->text('actual_num');
            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
