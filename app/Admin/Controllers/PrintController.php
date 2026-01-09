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

use App\Http\Controllers\Controller;
use App\Models\SystemConfigModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PrintController extends Controller
{
    public function print(Request $request)
    {
        $orderIds = explode("-", $request->input('ids'));
        $model = $request->input('model');
        /** @var Model $modelClass */
        $modelClass = "\\App\Models\\" . $model;
        $orders = $modelClass::query()->findOrFail($orderIds);
        $orderSlug = $request->input('slug');
        $orderField = collect(admin_trans($orderSlug.".fields"))->chunk(2)->toArray();

        $itemSlug = Str::replaceFirst("order", "item", $orderSlug);
        $itemField = admin_trans($itemSlug.".fields");
        $orderName = head(admin_trans($orderSlug . ".labels"));
        $printPhone = SystemConfigModel::getValue(
            SystemConfigModel::KEY_PRINT_PHONE,
            SystemConfigModel::DEFAULT_PRINT_PHONE
        );
        $printFax = SystemConfigModel::getValue(
            SystemConfigModel::KEY_PRINT_FAX,
            SystemConfigModel::DEFAULT_PRINT_FAX
        );

        return view('print.print', compact(
            "orders",
            'orderField',
            'itemField',
            'orderName',
            'printPhone',
            'printFax'
        ));
    }
}
