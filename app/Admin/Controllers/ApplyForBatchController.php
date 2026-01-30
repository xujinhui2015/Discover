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

use App\Admin\Actions\Grid\BatchStockSelect;
use App\Admin\Actions\Grid\Delete;
use App\Admin\Extensions\Grid\ColumnSelector;
use App\Admin\Repositories\ApplyForBatch;
use App\Models\ApplyForItemModel;
use App\Models\ApplyForOrderModel;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Controllers\AdminController;

class ApplyForBatchController extends AdminController
{
    protected function iFrameGrid()
    {
        $order = ApplyForItemModel::query()->findOrFail(request()->input('item_id'))->order;
        return Grid::make(new ApplyForBatch(['item.sku.product', 'stock_batch']), function (Grid $grid) use ($order) {
            $grid->model()->where('item_id', request()->input('item_id'))->orderBy('id', 'desc');
            
            // 定义列配置（用于列选择器）
            $columnConfig = [
                ['name' => 'id', 'label' => 'ID'],
                ['name' => 'name', 'label' => '物料名称'],
                ['name' => 'unit', 'label' => '单位'],
                ['name' => 'type', 'label' => '分类'],
                ['name' => 'attr', 'label' => '属性'],
                ['name' => 'standard', 'label' => '通用标准'],
                ['name' => 'actual_num', 'label' => '实领数量'],
                ['name' => 'cost_price', 'label' => '成本价格'],
                ['name' => 'batch_no', 'label' => '批次'],
                ['name' => 'position', 'label' => '库位'],
            ];
            
            $grid->column('id')->setHeaderAttributes(['class' => 'column-id'])->sortable();
            $grid->column('item.sku.product.name', '物料名称')->setHeaderAttributes(['class' => 'column-name']);
            $grid->column('item.sku.product.unit.name', '单位')->setHeaderAttributes(['class' => 'column-unit']);
            $grid->column('item.sku.product.type_str', '分类')->setHeaderAttributes(['class' => 'column-type']);
            $grid->column('item.sku.attr_value_ids_str', '属性')->setHeaderAttributes(['class' => 'column-attr']);
            $grid->column('stock_batch.standard_str', '通用标准')->setHeaderAttributes(['class' => 'column-standard']);
            $grid->column('actual_num', '实领数量')->setHeaderAttributes(['class' => 'column-actual_num'])->if(function () use ($order) {
                return $order->review_status !== ApplyForOrderModel::REVIEW_STATUS_OK;
            })->edit();

            $grid->column('stock_batch.cost_price', '成本价格')->setHeaderAttributes(['class' => 'column-cost_price']);
            $grid->column('stock_batch.batch_no', '批次')->setHeaderAttributes(['class' => 'column-batch_no']);
            $grid->column('stock_batch.position.name', '库位')->setHeaderAttributes(['class' => 'column-position']);
            $grid->disableCreateButton();
            $grid->disableActions();
            
            // 添加工具栏按钮
            $grid->tools(function ($tools) use ($columnConfig, $order) {
                $tools->append(Delete::make());
                if ($order->review_status !== ApplyForOrderModel::REVIEW_STATUS_OK) {
                    $tools->append(BatchStockSelect::make());
                }
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
        return Form::make(new ApplyForBatch(), function (Form $form) {
            $form->decimal('actual_num');
        });
    }
}
