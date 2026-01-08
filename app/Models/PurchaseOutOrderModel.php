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

class PurchaseOutOrderModel extends PurchaseBaseModel
{
    use SoftDeletes;

    protected $table = 'purchase_out_order';

    public function with_order(): BelongsTo
    {
        return $this->belongsTo(PurchaseInOrderModel::class, 'with_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOutItemModel::class, 'order_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(SupplierModel::class, 'supplier_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(Administrator::class, 'user_id');
    }

    public function getStatusStrAttribute(): string
    {
        return self::STATUS[$this->status] ?? '';
    }

    public function getSupplierStrAttribute(): string
    {
        return $this->supplier->name ?? '';
    }
}
