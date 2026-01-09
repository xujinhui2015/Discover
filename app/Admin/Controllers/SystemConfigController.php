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

use App\Admin\Forms\SystemConfigForm;
use Dcat\Admin\Admin;
use Dcat\Admin\Controllers\AdminController;
use Dcat\Admin\Layout\Content;

class SystemConfigController extends AdminController
{
    public function index(Content $content)
    {
        if (! Admin::user()?->isAdministrator()) {
            abort(403, '无权限');
        }

        return $content
            ->title('打印配置')
            ->description('维护打印电话和传真')
            ->body(new SystemConfigForm());
    }
}
