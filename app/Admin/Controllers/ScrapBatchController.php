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
use App\Admin\Repositories\ScrapBatch;
use App\Models\ScrapItemModel;
use App\Models\ScrapOrderModel;
use Dcat\Admin\Controllers\AdminController;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;

class ScrapBatchController extends AdminController
{
    protected function iFrameGrid()
    {
        $order = ScrapItemModel::query()->findOrFail(request()->input('item_id'))->order;

        return Grid::make(new ScrapBatch(['item.sku.product', 'stock_batch']), function (Grid $grid) use ($order) {
            $grid->model()->where('item_id', request()->input('item_id'))->orderBy('id', 'desc');
            
            // 定义列配置（用于列选择器）
            $columnConfig = [
                ['name' => 'id', 'label' => 'ID'],
                ['name' => 'name', 'label' => '物料名称'],
                ['name' => 'unit', 'label' => '单位'],
                ['name' => 'type', 'label' => '分类'],
                ['name' => 'brand', 'label' => '品牌'],
                ['name' => 'attr', 'label' => '属性'],
                ['name' => 'scrap_type', 'label' => '报废类型'],
                ['name' => 'standard', 'label' => '通用标准'],
                ['name' => 'actual_num', 'label' => '报废数量'],
                ['name' => 'cost_price', 'label' => '成本价格'],
                ['name' => 'batch_no', 'label' => '批次'],
                ['name' => 'position', 'label' => '库位'],
            ];
            
            // 添加列选择器到工具栏
            $grid->tools(function ($tools) use ($columnConfig) {
                $tools->append(new ColumnSelector($columnConfig));
            });
            
            $grid->column('id')->setHeaderAttributes(['class' => 'column-id'])->sortable();
            $grid->column('item.sku.product.name', '物料名称')->setHeaderAttributes(['class' => 'column-name']);
            $grid->column('item.sku.product.unit.name', '单位')->setHeaderAttributes(['class' => 'column-unit']);
            $grid->column('item.sku.product.type_str', '分类')->setHeaderAttributes(['class' => 'column-type']);
            $grid->column('item.sku.product.brand.name', '品牌')->setHeaderAttributes(['class' => 'column-brand']);
            $grid->column('item.sku.attr_value_ids_str', '属性')->setHeaderAttributes(['class' => 'column-attr']);
            $grid->column('scrap_type', '报废类型')->setHeaderAttributes(['class' => 'column-scrap_type'])->display(function () use ($order) {
                return $order->scrap_type_str;
            });
            $grid->column('stock_batch.standard_str', '通用标准')->setHeaderAttributes(['class' => 'column-standard']);
            $grid->column('actual_num', '报废数量')->setHeaderAttributes(['class' => 'column-actual_num'])->if(function () use ($order) {
                return $order->review_status !== ScrapOrderModel::REVIEW_STATUS_OK;
            })->edit();
            $grid->column('stock_batch.cost_price', '成本价格')->setHeaderAttributes(['class' => 'column-cost_price']);
            $grid->column('stock_batch.batch_no', '批次')->setHeaderAttributes(['class' => 'column-batch_no']);
            $grid->column('stock_batch.position.name', '库位')->setHeaderAttributes(['class' => 'column-position']);
            $grid->disableCreateButton();
            $grid->disableActions();

            if ($order->review_status !== ScrapOrderModel::REVIEW_STATUS_OK) {
                $grid->tools(Delete::make());
                $grid->tools(BatchStockSelect::make());
            }
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
        return Form::make(new ScrapBatch(), function (Form $form) {
            $form->decimal('actual_num');

            $form->saving(function (Form $form) {
                if ($form->actual_num) {
                    $form->actual_num = strip_tags($form->actual_num);
                }
            });
        });
    }
}
