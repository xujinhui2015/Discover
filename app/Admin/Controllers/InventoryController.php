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

use App\Admin\Actions\Grid\Delete;
use App\Admin\Actions\Grid\EditInventoryOrder;
use App\Admin\Extensions\Grid\ColumnSelector;
use App\Admin\Repositories\Inventory;
use App\Models\InventoryModel;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Controllers\AdminController;

class InventoryController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new Inventory('user'), function (Grid $grid) {
            // 定义列配置（用于列选择器）
            $columnConfig = [
                ['name' => 'id', 'label' => 'ID'],
                ['name' => 'order_no', 'label' => '任务单号'],
                ['name' => 'start_at', 'label' => '开始时间'],
                ['name' => 'end_at', 'label' => '结束时间'],
                ['name' => 'status', 'label' => '单据状态'],
                ['name' => 'user', 'label' => '创建人'],
                ['name' => 'other', 'label' => '备注'],
            ];
            
            $grid->column('id')->setHeaderAttributes(['class' => 'column-id'])->sortable();
            $grid->column('order_no')->setHeaderAttributes(['class' => 'column-order_no']);
            $grid->column('start_at')->setHeaderAttributes(['class' => 'column-start_at']);
            $grid->column('end_at')->setHeaderAttributes(['class' => 'column-end_at']);
            $grid->column('status', '单据状态')->setHeaderAttributes(['class' => 'column-status'])->using(InventoryModel::STATUS)->label(InventoryModel::STATUS_COLOR);
            $grid->column('user.name', "创建人")->setHeaderAttributes(['class' => 'column-user']);
            $grid->column('other')->setHeaderAttributes(['class' => 'column-other']);
            $grid->disableQuickEditButton();
            $grid->showBatchDelete();
            $grid->actions(function (\Dcat\Admin\Grid\Displayers\Actions $actions) {
                if ($this->status !== InventoryModel::STATUS_NOT_STARTED) {
                    $actions->append(new EditInventoryOrder());
                }
            });
            $grid->tools(function ($tools) use ($columnConfig) {
                $tools->append(Delete::make());
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
        return Form::make(new Inventory(), function (Form $form) {
            $form->text('order_no', "盘点任务号")->default('提交后自动生成')->readOnly();
            $form->datetimeRange('start_at', 'end_at', '盘点启止时间')->required();
            $form->text('other')->saveAsString();

            $form->saving(function (Form $form) {
                if ($form->isCreating()) {
                    $form->order_no = build_order_no('PDRW');
                }
            });
        });
    }
}
