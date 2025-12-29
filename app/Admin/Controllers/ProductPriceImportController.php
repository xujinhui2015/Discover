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

use App\Admin\Forms\ProductPriceImportForm;
use Dcat\Admin\Controllers\AdminController;
use Dcat\Admin\Layout\Content;
use Dcat\EasyExcel\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProductPriceImportController extends AdminController
{
    public function index(Content $content)
    {
        return $content
            ->title('价格导入')
            ->description('通过模板批量更新物料销售价和采购价')
            ->body(new ProductPriceImportForm());
    }

    public function template()
    {
        $headers = ['物料编号', '销售价', '采购价'];
        $sample = [
            ['ITEM001', '100.00', '80.00'],
            ['ITEM002', '200.00', '150.00'],
        ];

        return Excel::export($sample)
            ->headings($headers)
            ->download('价格导入模板.xlsx');
    }
}
