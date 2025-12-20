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

use App\Admin\Forms\CustomerImportForm;
use App\Services\CustomerImportService;
use Dcat\Admin\Controllers\AdminController;
use Dcat\Admin\Layout\Content;
use Dcat\EasyExcel\Excel;

class CustomerImportController extends AdminController
{
    public function index(Content $content)
    {
        return $content
            ->title('客户导入')
            ->description('通过模板批量导入客户档案')
            ->body(new CustomerImportForm());
    }

    public function template()
    {
        return Excel::export(CustomerImportService::TEMPLATE_SAMPLE)
            ->headings(CustomerImportService::TEMPLATE_HEADERS)
            ->download('客户导入模板.xlsx');
    }
}
