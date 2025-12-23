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

namespace App\Models;

/**
 * App\Models\PersonalConfigModel
 *
 * @property int $id
 * @property int $user_id
 * @property string $config_key
 * @property string|null $config_value
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|PersonalConfigModel newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PersonalConfigModel newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|PersonalConfigModel query()
 * @method static \Illuminate\Database\Eloquent\Builder|PersonalConfigModel whereConfigKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PersonalConfigModel whereConfigValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PersonalConfigModel whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PersonalConfigModel whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PersonalConfigModel whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|PersonalConfigModel whereUserId($value)
 * @mixin \Eloquent
 */
class PersonalConfigModel extends BaseModel
{
    protected $table = 'personal_config';
}
