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

namespace App\Admin\Controllers;

use App\Admin\Forms\PersonalConfigForm;
use Dcat\Admin\Controllers\AdminController;
use Dcat\Admin\Layout\Content;

class PersonalConfigController extends AdminController
{
    public function index(Content $content)
    {
        return $content
            ->title('个性化配置')
            ->description('管理个人偏好设置')
            ->body(new PersonalConfigForm());
    }
}
