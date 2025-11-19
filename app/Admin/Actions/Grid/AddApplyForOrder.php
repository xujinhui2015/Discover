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

use Dcat\Admin\Grid\RowAction;

class AddApplyForOrder extends RowAction
{
    public $title = "生产领料";

    public function html()
    {
        $url = route('apply-for-orders.create', ['with_id' => $this->getKey()]);
        return <<<HTML
<a style="padding: 1px 4px; border-radius: 2px; display: inline-flex; align-items: center; cursor: pointer; transition: all 0.2s ease;" class="{$this->getElementClass()} dialog-create btn btn-xs btn-sm btn-info grid-actions-btn" href="javascript:void(0)" data-url="$url">{$this->title()}</a>
HTML;
    }

    public function script()
    {
        $class = $this->getElementClass();
        return <<<JS
        $(".{$class}").parent().parent().unbind('dblclick');
JS;
    }
}
