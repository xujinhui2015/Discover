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

namespace App\Models;

/**
 * App\Models\SystemConfigModel
 *
 * @property string $config_key
 * @property string|null $config_value
 * @method static \Illuminate\Database\Eloquent\Builder|SystemConfigModel newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SystemConfigModel newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SystemConfigModel query()
 * @method static \Illuminate\Database\Eloquent\Builder|SystemConfigModel whereConfigKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SystemConfigModel whereConfigValue($value)
 * @mixin \Eloquent
 */
class SystemConfigModel extends BaseModel
{
    // 打印配置
    public const KEY_PRINT_PHONE = 'print_phone';
    public const KEY_PRINT_FAX = 'print_fax';
    public const DEFAULT_PRINT_PHONE = '020-86326688';
    public const DEFAULT_PRINT_FAX = '020-36265293';

    // ERP配置
    public const KEY_PRODUCTION_WAREHOUSE = 'production_warehouse';
    public const DEFAULT_PRODUCTION_WAREHOUSE = '成品仓';
    public const KEY_DEFAULT_ATTR_NAME = 'default_attr_name';
    public const DEFAULT_ATTR_NAME = '通用';
    public const KEY_DEFAULT_ATTR_VALUE_NAME = 'default_attr_value_name';
    public const DEFAULT_ATTR_VALUE_NAME = '基础';
    public const KEY_CHECK_INVENTORY = 'check_inventory';
    public const DEFAULT_CHECK_INVENTORY = '0';
    public const KEY_DEFAULT_PURCHASE_IN_POSITION = 'default_purchase_in_position';
    public const DEFAULT_PURCHASE_IN_POSITION = '';

    protected $table = 'system_config';

    public static function getValue(string $key, ?string $default = null): ?string
    {
        $value = static::query()
            ->where('config_key', $key)
            ->value('config_value');

        return $value !== null && $value !== '' ? $value : $default;
    }
}
