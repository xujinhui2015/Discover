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

class ImportCustomer extends AbstractTool
{
    protected $title = '导入客户';

    public function render()
    {
        $url = route('customers.import');

        return <<<HTML
<a {$this->formatHtmlAttributes()} href="{$url}">
    <button class="btn btn-outline-primary btn-mini">
        <i class="feather icon-upload"></i> {$this->title()}
    </button>
</a>
HTML;
    }
}
