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
            $grid->column('id')->sortable();
            $grid->column('order_id');
            $grid->column('sku_id');
            $grid->column('standard');
            $grid->column('actual_num');
            $grid->column('created_at');
            $grid->column('updated_at')->sortable();
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
