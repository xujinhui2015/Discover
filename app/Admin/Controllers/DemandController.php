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
use App\Admin\Repositories\Demand;
use App\Models\DemandModel;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Controllers\AdminController;

class DemandController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new Demand(), function (Grid $grid) {
            // 定义列配置（用于列选择器）
            $columnConfig = [
                ['name' => 'id', 'label' => 'ID'],
                ['name' => 'status', 'label' => '状态'],
                ['name' => 'type', 'label' => '类型'],
                ['name' => 'created_at', 'label' => '创建时间'],
            ];
            
            $grid->column('id')->setHeaderAttributes(['class' => 'column-id'])->sortable();
            $grid->column('status')->setHeaderAttributes(['class' => 'column-status'])->using(DemandModel::STATUS)->label(DemandModel::STATUS_COLOR);
            $grid->column('type')->setHeaderAttributes(['class' => 'column-type'])->using(DemandModel::TYPE)->label(DemandModel::TYPE_COLOR);
            $grid->column('created_at')->setHeaderAttributes(['class' => 'column-created_at']);

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
        return Form::make(new Demand(), function (Form $form) {
            $form->markdown('content');
            $form->radio('type')->options(DemandModel::TYPE)->default(0);
            $form->markdown('reply');
            $form->select('status')->options(DemandModel::STATUS)->default(0);
        });
    }
}
