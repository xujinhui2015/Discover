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

use Dcat\Admin\Models\Administrator;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ScrapOrderModel extends BaseModel
{
    use SoftDeletes;

    protected $table = 'scrap_order';

    protected $appends = ['scrap_type_str'];

    const SCRAP_TYPE_EXPIRED = 1;
    const SCRAP_TYPE_PACKAGING_UPDATE = 2;

    const SCRAP_TYPE = [
        self::SCRAP_TYPE_EXPIRED => '过期报废',
        self::SCRAP_TYPE_PACKAGING_UPDATE => '包材更新',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(ScrapItemModel::class, 'order_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(Administrator::class, 'user_id');
    }

    public function apply_user(): BelongsTo
    {
        return $this->belongsTo(Administrator::class, 'apply_id');
    }

    public function getScrapTypeStrAttribute(): string
    {
        return self::SCRAP_TYPE[$this->scrap_type] ?? '-';
    }
}
