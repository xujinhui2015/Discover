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
use App\Admin\Repositories\Position;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Controllers\AdminController;

class PositionController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new Position(), function (Grid $grid) {
            // 定义列配置（用于列选择器）
            $columnConfig = [
                ['name' => 'id', 'label' => 'ID'],
                ['name' => 'name', 'label' => '库位名称'],
                ['name' => 'created_at', 'label' => '创建时间'],
                ['name' => 'updated_at', 'label' => '更新时间'],
            ];
            
            $grid->column('id')->setHeaderAttributes(['class' => 'column-id'])->sortable();
            $grid->column('name')->setHeaderAttributes(['class' => 'column-name']);
            $grid->column('created_at')->setHeaderAttributes(['class' => 'column-created_at']);
            $grid->column('updated_at')->setHeaderAttributes(['class' => 'column-updated_at'])->sortable();

            $grid->tools(function ($tools) use ($columnConfig) {
                $tools->append(new ColumnSelector($columnConfig));
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
        return Form::make(new Position(), function (Form $form) {
            $form->text('name');
        });
    }
}
