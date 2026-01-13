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
use Dcat\Admin\Admin;
use Dcat\Admin\Widgets\Form;

class SystemConfigForm extends Form
{
    public function handle(array $input)
    {
        // 保存打印配置
        $phone = trim((string) ($input[SystemConfigModel::KEY_PRINT_PHONE] ?? ''));
        $fax = trim((string) ($input[SystemConfigModel::KEY_PRINT_FAX] ?? ''));

        $this->saveValue(SystemConfigModel::KEY_PRINT_PHONE, $phone);
        $this->saveValue(SystemConfigModel::KEY_PRINT_FAX, $fax);

        // 保存ERP配置
        $warehouse = trim((string) ($input[SystemConfigModel::KEY_PRODUCTION_WAREHOUSE] ?? ''));
        $this->saveValue(SystemConfigModel::KEY_PRODUCTION_WAREHOUSE, $warehouse);

        $attrName = trim((string) ($input[SystemConfigModel::KEY_DEFAULT_ATTR_NAME] ?? ''));
        $this->saveValue(SystemConfigModel::KEY_DEFAULT_ATTR_NAME, $attrName);

        $attrValueName = trim((string) ($input[SystemConfigModel::KEY_DEFAULT_ATTR_VALUE_NAME] ?? ''));
        $this->saveValue(SystemConfigModel::KEY_DEFAULT_ATTR_VALUE_NAME, $attrValueName);

        $checkInventory = trim((string) ($input[SystemConfigModel::KEY_CHECK_INVENTORY] ?? '0'));
        $this->saveValue(SystemConfigModel::KEY_CHECK_INVENTORY, $checkInventory);

        return $this->success('保存成功');
    }

    public function form()
    {
        Admin::style(<<<'CSS'
.system-config-form {
    background: #fff;
}
CSS
        );
        $this->appendHtmlAttribute('class', 'system-config-form');

        $this->tab('ERP设置', function () {
            $this->text(SystemConfigModel::KEY_PRODUCTION_WAREHOUSE, '默认生产仓')
                ->required()
                ->help('用于生产入库时的默认仓库名称');

            $this->text(SystemConfigModel::KEY_DEFAULT_ATTR_NAME, '默认属性名')
                ->required()
                ->help('商品无规格时自动绑定的默认属性名称');

            $this->text(SystemConfigModel::KEY_DEFAULT_ATTR_VALUE_NAME, '默认属性值名')
                ->required()
                ->help('商品无规格时自动绑定的默认属性值名称');

            $this->radio(SystemConfigModel::KEY_CHECK_INVENTORY, '出库验证库存')
                ->options([
                    '1' => '开启',
                    '0' => '关闭',
                ])
                ->default('0')
                ->help('开启后，销售出库审核时将验证库存是否充足，不允许负库存');
        });

        $this->tab('打印设置', function () {
            $this->text(SystemConfigModel::KEY_PRINT_PHONE, '打印电话');
            $this->text(SystemConfigModel::KEY_PRINT_FAX, '打印传真');
        });

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
            SystemConfigModel::KEY_PRODUCTION_WAREHOUSE => SystemConfigModel::getValue(
                SystemConfigModel::KEY_PRODUCTION_WAREHOUSE,
                SystemConfigModel::DEFAULT_PRODUCTION_WAREHOUSE
            ),
            SystemConfigModel::KEY_DEFAULT_ATTR_NAME => SystemConfigModel::getValue(
                SystemConfigModel::KEY_DEFAULT_ATTR_NAME,
                SystemConfigModel::DEFAULT_ATTR_NAME
            ),
            SystemConfigModel::KEY_DEFAULT_ATTR_VALUE_NAME => SystemConfigModel::getValue(
                SystemConfigModel::KEY_DEFAULT_ATTR_VALUE_NAME,
                SystemConfigModel::DEFAULT_ATTR_VALUE_NAME
            ),
            SystemConfigModel::KEY_CHECK_INVENTORY => SystemConfigModel::getValue(
                SystemConfigModel::KEY_CHECK_INVENTORY,
                SystemConfigModel::DEFAULT_CHECK_INVENTORY
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
