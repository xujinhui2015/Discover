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

namespace App\Admin\Actions\Grid;

use Dcat\Admin\Grid\Tools\AbstractTool;

class ImportProductPrice extends AbstractTool
{
    protected $title = '导入价格';

    public function render()
    {
        $url = route('products.price.import');

        return <<<HTML
<a {$this->formatHtmlAttributes()} href="{$url}">
    <button class="btn btn-outline-success btn-mini">
        <i class="feather icon-dollar-sign"></i> {$this->title()}
    </button>
</a>
HTML;
    }
}
