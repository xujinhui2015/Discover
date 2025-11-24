<?php

/*
 * // +----------------------------------------------------------------------
 * // | erp
 * // +----------------------------------------------------------------------
 * // | Copyright (c) 2006~2020 erp All rights reserved.
 * // +----------------------------------------------------------------------
 * // | Licensed ( LICENSE-1.0.0 )
 * // +----------------------------------------------------------------------
 */

namespace App\Admin\Actions\Grid;

use Dcat\Admin\Grid\Displayers\Actions;

class TaskActions extends Actions
{
    /**
     * 重写渲染快捷编辑按钮的方法，将按钮文字修改为"查看任务"
     *
     * @return string
     */
    protected function renderQuickEdit()
    {
        if (!static::$resolvedDialog) {
            static::$resolvedDialog = true;

            [$width, $height] = $this->grid->option('dialog_form_area');

            \Dcat\Admin\Form::dialog(trans('admin.edit'))
                ->click(".{$this->grid->getRowName()}-edit")
                ->dimensions($width, $height)
                ->success('Dcat.reload()');
        }

        $label = trans('admin.quick_edit');

        return <<<EOF
<a title="{$label}" style="padding: 1px 4px; border-radius: 2px; display: inline-flex; align-items: center; cursor: pointer; transition: all 0.2s ease;" class="{$this->grid->getRowName()}-edit btn btn-xs btn-sm btn-primary" data-url="{$this->resource()}/{$this->getKey()}/edit" href="javascript:void(0);">
    查看任务
</a>&nbsp;
EOF;
    }
}
