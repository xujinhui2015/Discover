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
            $grid->column('id')->sortable();
            $grid->column('item.sku.product.name', '物料名称');
            $grid->column('item.sku.product.unit.name', '单位');
            $grid->column('item.sku.product.type_str', '分类');
            $grid->column('item.sku.product.brand.name', '品牌');
            $grid->column('item.sku.attr_value_ids_str', '属性');
            $grid->column('scrap_type', '报废类型')->display(function () use ($order) {
                return $order->scrap_type_str;
            });
            $grid->column('stock_batch.standard_str', '通用标准');
            $grid->column('actual_num', '报废数量')->if(function () use ($order) {
                return $order->review_status !== ScrapOrderModel::REVIEW_STATUS_OK;
            })->edit();
            $grid->column('stock_batch.cost_price', '成本价格');
            $grid->column('stock_batch.batch_no', '批次');
            $grid->column('stock_batch.position.name', '库位');
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
        });
    }
}
