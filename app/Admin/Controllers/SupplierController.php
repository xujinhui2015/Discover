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

use App\Admin\Actions\Grid\Statement;
use App\Admin\Extensions\Grid\ColumnSelector;
use App\Admin\Repositories\Supplier;
use App\Models\SupplierModel;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Controllers\AdminController;

class SupplierController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new Supplier(), function (Grid $grid) {
            // 定义列配置（用于列选择器）
            $columnConfig = [
                ['name' => 'id', 'label' => 'ID'],
                ['name' => 'link', 'label' => '联系人'],
                ['name' => 'name', 'label' => '供应商名称'],
                ['name' => 'pay_method', 'label' => '付款方式'],
                ['name' => 'phone', 'label' => '电话'],
                ['name' => 'other', 'label' => '备注'],
                ['name' => 'created_at', 'label' => '创建时间'],
            ];
            
            $grid->column('id')->setHeaderAttributes(['class' => 'column-id'])->sortable();
            $grid->column('link')->setHeaderAttributes(['class' => 'column-link'])->emp();
            $grid->column('name')->setHeaderAttributes(['class' => 'column-name'])->emp();

            $grid->column('pay_method')->setHeaderAttributes(['class' => 'column-pay_method'])->using(SupplierModel::PAY_METHOD);
            $grid->column('phone')->setHeaderAttributes(['class' => 'column-phone'])->emp();
            $grid->column('other')->setHeaderAttributes(['class' => 'column-other'])->emp();
            $grid->column('created_at')->setHeaderAttributes(['class' => 'column-created_at']);

            $grid->tools(function ($tools) use ($columnConfig) {
                $tools->append(new ColumnSelector($columnConfig));
            });

            $grid->filter(function (Grid\Filter $filter) {
                $filter->like('name', '供应商名称')->width(4);
            });
        });
    }

    protected function iFrameGrid()
    {
        return Grid::make(new Supplier(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('link')->emp();
            $grid->column('name')->emp();

            $grid->column('pay_method')->using(SupplierModel::PAY_METHOD);
            $grid->column('phone')->emp();
            $grid->column('other')->emp();
            $grid->column('created_at');
            $grid->tools(Statement::make());

            $grid->disableCreateButton();
            $grid->disableActions();
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(new Supplier(), function (Form $form) {
            $form->text('link')->required();
            $form->text('name')->required();
            $form->select('pay_method')->options(SupplierModel::PAY_METHOD)->default(0)->required();
//            $form->text('phone')->rules('phone:CN,mobile')->required();
            $form->text('phone');
            $form->text('other')->saveAsString();
        });
    }
}
