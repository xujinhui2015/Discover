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

use App\Admin\Extensions\Expand\AttrValue;
use App\Admin\Repositories\Attr;
use App\Models\AttrModel;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Controllers\AdminController;

class AttrController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(AttrModel::withoutGlobalScope('status'), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('name');
            $grid->column('value', '属性值')
                ->display('查看')
                ->expand(AttrValue::class);
            $grid->status('单据状态')->switch();
            $grid->column('created_at');

            $grid->showBatchDelete();
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(new Attr('values'), function (Form $form) {
            $form->text('name')->help('例如：颜色，尺寸')->required();
            $form->hasMany('values', '属性值', function (Form\NestedForm $table) {
                $table->text('name', '名称')->help('属性的值（例如颜色的值：黄色，蓝色）');
            })->useTable();

            $form->hidden('status')->default(1);

            $form->saving(function (Form $form) {
                $id = $form->getKey();
                if (!$id) {
                    return; // 新建时不处理
                }

                $inputs = request()->input();

                // 获取当前属性的所有属性值 ID
                $existingIds = \App\Models\AttrValueModel::where('attr_id', $id)
                    ->pluck('id')
                    ->toArray();

                // 获取提交的属性值 ID
                $submittedIds = [];
                if (isset($inputs['values']) && is_array($inputs['values'])) {
                    foreach ($inputs['values'] as $value) {
                        // 排除被标记为删除的项
                        if (isset($value['_remove_']) && $value['_remove_'] == 1) {
                            continue;
                        }
                        if (isset($value['id']) && $value['id']) {
                            $submittedIds[] = (int)$value['id'];
                        }
                    }
                }

                // 计算需要删除的 ID（存在于数据库但不在提交数据中）
                $toDeleteIds = array_diff($existingIds, $submittedIds);

                // 软删除这些记录
                if (!empty($toDeleteIds)) {
                    \App\Models\AttrValueModel::whereIn('id', $toDeleteIds)->delete();
                }
            });
        });
    }
}
