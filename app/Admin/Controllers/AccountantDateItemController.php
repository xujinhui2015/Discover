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
use App\Admin\Repositories\AccountantDateItem;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Controllers\AdminController;

class AccountantDateItemController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new AccountantDateItem(), function (Grid $grid) {
            $columnConfig = [
                ['name' => 'id', 'label' => 'ID'],
                ['name' => 'accountant_date_id', 'label' => '会计年度ID'],
                ['name' => 'start_at', 'label' => '开始时间'],
                ['name' => 'end_at', 'label' => '结束时间'],
                ['name' => 'created_at', 'label' => '创建时间'],
                ['name' => 'updated_at', 'label' => '更新时间'],
            ];
            
            $grid->column('id')->setHeaderAttributes(['class' => 'column-id'])->sortable();
            $grid->column('accountant_date_id')->setHeaderAttributes(['class' => 'column-accountant_date_id']);
            $grid->column('start_at')->setHeaderAttributes(['class' => 'column-start_at']);
            $grid->column('end_at')->setHeaderAttributes(['class' => 'column-end_at']);
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
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(new AccountantDateItem(), function (Form $form) {
            $form->display('id');
            $form->text('accountant_date_id');
            $form->text('start_at');
            $form->text('end_at');

            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
