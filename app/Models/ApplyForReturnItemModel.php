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
use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApplyForReturnItemModel extends BaseModel
{
    use HasDateTimeFormatter;
    use SoftDeletes;
    use HasStandard;

    protected $table = 'apply_for_return_item';

    protected $with = ['sku'];

    protected $appends = ['sku_stock_num', 'standard_str'];

    public function sku(): BelongsTo
    {
        return $this->belongsTo(ProductSkuModel::class, 'sku_id')->withTrashed();
    }

    public function getSkuStockNumAttribute()
    {
        return $this->sku_stock()->value('num') ?? 0;
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(ApplyForReturnOrderModel::class, 'order_id');
    }

    public function batchs(): HasMany
    {
        return $this->hasMany(ApplyForBatchModel::class, 'item_id');
    }

    /**
     * @return BelongsTo
     */
    public function sku_stock(): BelongsTo
    {
        return $this->belongsTo(SkuStockModel::class, 'sku_id', 'sku_id')
            ->where([
//                'percent' => $this->percent,
                'standard' => $this->standard,
            ]);
    }

}
