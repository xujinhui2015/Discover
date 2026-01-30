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
use App\Admin\Repositories\PurchaseOrderAmount;
use App\Models\PurchaseOrderAmountModel;
use App\Models\SupplierModel;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Controllers\AdminController;

class PurchaseOrderAmountController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new PurchaseOrderAmount(['order', 'supplier', 'accountant']), function (Grid $grid) {
            // 定义列配置（用于列选择器）
            $columnConfig = [
                ['name' => 'id', 'label' => 'ID'],
                ['name' => 'order_no', 'label' => '采购订单'],
                ['name' => 'supplier', 'label' => '供应商名称'],
                ['name' => 'should_amount', 'label' => '费用金额'],
                ['name' => 'actual_amount', 'label' => '结算金额'],
                ['name' => 'status', 'label' => '单据状态'],
                ['name' => 'created_at', 'label' => '创建时间'],
            ];
            
            $grid->column('id')->setHeaderAttributes(['class' => 'column-id'])->sortable();
            $grid->column('order.order_no', "采购订单")->setHeaderAttributes(['class' => 'column-order_no']);
            $grid->column('supplier.name', "供应商名称")->setHeaderAttributes(['class' => 'column-supplier']);
            $grid->column('should_amount')->setHeaderAttributes(['class' => 'column-should_amount']);
            $grid->column('actual_amount')->setHeaderAttributes(['class' => 'column-actual_amount']);
            $grid->column('status', '单据状态')->setHeaderAttributes(['class' => 'column-status'])->using(PurchaseOrderAmountModel::STATUS)->label(PurchaseOrderAmountModel::STATUS_COLOR);
            $grid->column('created_at')->setHeaderAttributes(['class' => 'column-created_at']);
            
            $grid->tools(function ($tools) use ($columnConfig) {
                $tools->append(new ColumnSelector($columnConfig));
            });
            
            $grid->filter(function (Grid\Filter $filter) {
                $filter->in('supplier_id', "供应商名称")->width(4)->multipleSelect(SupplierModel::query()->pluck('name', 'id'));
            });
            $grid->disableCreateButton();
            $grid->export()->rows(function (array $rows) {
                return array_map(function ($row) {
                    return [
                        '采购订单' => $row['order']['order_no'],
                        '供应商名称' => $row['supplier']['name'],
                        '费用金额' => $row['should_amount'],
                        '结算金额' => $row['actual_amount'],
                        '单据状态' => PurchaseOrderAmountModel::STATUS[$row['status']]
                    ];
                }, $rows);
            })->extension("xlsx");
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
        return Show::make($id, new PurchaseOrderAmount(), function (Show $show) {
            $show->field('id');
            $show->field('order_id');
            $show->field('supplier_id');
            $show->field('status');
            $show->field('should_amount');
            $show->field('actual_amount');
            $show->field('accountant_id');
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
        return Form::make(new PurchaseOrderAmount(), function (Form $form) {
            $form->display('id');
            $form->text('order_id');
            $form->text('supplier_id');
            $form->text('status');
            $form->text('should_amount');
            $form->text('actual_amount');
            $form->text('accountant_id');

            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
