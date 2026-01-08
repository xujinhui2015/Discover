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

use App\Traits\HasStandard;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOutItemModel extends BaseModel
{
    use HasStandard;

    protected $table = 'purchase_out_item';

    protected $with = ['sku'];

    protected $appends = ['standard_str'];

    public function sku(): BelongsTo
    {
        return $this->belongsTo(ProductSkuModel::class, 'sku_id');
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(PositionModel::class, 'position_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(PurchaseOutOrderModel::class, 'order_id');
    }
}
