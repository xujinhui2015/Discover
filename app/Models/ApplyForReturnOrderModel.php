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
use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApplyForReturnOrderModel extends BaseModel
{
    use HasDateTimeFormatter;
    use SoftDeletes;

    protected $table = 'apply_for_return_order';

    /**
     * @return HasMany
     */
    public function items(): HasMany
    {
        return $this->hasMany(ApplyForReturnItemModel::class, 'order_id');
    }

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(Administrator::class, 'user_id');
    }

    /**
     * @return BelongsTo
     */
    public function apply_user(): BelongsTo
    {
        return $this->belongsTo(Administrator::class, 'apply_id');
    }

    /**
     * @return BelongsTo
     */
    public function apply_for_order():BelongsTo
    {
        return $this->belongsTo(ApplyForOrderModel::class, 'apply_for_order_id');
    }
}
