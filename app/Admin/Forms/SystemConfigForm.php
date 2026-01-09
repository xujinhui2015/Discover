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

namespace App\Admin\Forms;

use App\Models\SystemConfigModel;
use Dcat\Admin\Widgets\Form;

class SystemConfigForm extends Form
{
    public function handle(array $input)
    {
        $phone = trim((string) ($input[SystemConfigModel::KEY_PRINT_PHONE] ?? ''));
        $fax = trim((string) ($input[SystemConfigModel::KEY_PRINT_FAX] ?? ''));

        $this->saveValue(SystemConfigModel::KEY_PRINT_PHONE, $phone);
        $this->saveValue(SystemConfigModel::KEY_PRINT_FAX, $fax);

        return $this->success('保存成功');
    }

    public function form()
    {
        $this->text(SystemConfigModel::KEY_PRINT_PHONE, '打印电话');
        $this->text(SystemConfigModel::KEY_PRINT_FAX, '打印传真');

        $this->disableResetButton();
    }

    public function default(): array
    {
        return [
            SystemConfigModel::KEY_PRINT_PHONE => SystemConfigModel::getValue(
                SystemConfigModel::KEY_PRINT_PHONE,
                SystemConfigModel::DEFAULT_PRINT_PHONE
            ),
            SystemConfigModel::KEY_PRINT_FAX => SystemConfigModel::getValue(
                SystemConfigModel::KEY_PRINT_FAX,
                SystemConfigModel::DEFAULT_PRINT_FAX
            ),
        ];
    }

    private function saveValue(string $key, string $value): void
    {
        SystemConfigModel::query()->updateOrCreate(
            ['config_key' => $key],
            ['config_value' => $value]
        );
    }
}
