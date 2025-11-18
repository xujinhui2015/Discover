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

use App\Admin\Forms\ProductImportForm;
use App\Services\ProductImportService;
use Dcat\Admin\Controllers\AdminController;
use Dcat\Admin\Layout\Content;
use Dcat\EasyExcel\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProductImportController extends AdminController
{
    public function index(Content $content)
    {
        return $content
            ->title('物料导入')
            ->description('通过模板批量导入或更新物料')
            ->body(new ProductImportForm());
    }

    public function template()
    {
        return Excel::export(ProductImportService::TEMPLATE_SAMPLE)
            ->headings(ProductImportService::TEMPLATE_HEADERS)
            ->download('物料导入模板.xlsx');
    }
}

