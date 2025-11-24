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
        $style = 'display:block; padding:6px 10px; margin:4px 0; border-radius:4px; text-align:center; cursor:pointer; transition:all 0.2s ease; width:100%; box-sizing:border-box; white-space:nowrap;';
        return <<<HTML
<a style="{$style}" class="{$this->getElementClass()} dialog-create btn btn-sm btn-info grid-actions-btn" href="javascript:void(0)" data-url="$url">{$this->title()}</a>
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
