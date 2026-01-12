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
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScrapItemModel extends BaseModel
{
    use HasStandard;

    protected $table = 'scrap_item';

    protected $with = ['sku'];

    protected $appends = ['sku_stock_num', 'standard_str'];

    public function sku(): BelongsTo
    {
        return $this->belongsTo(ProductSkuModel::class, 'sku_id')->withTrashed();
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(ScrapOrderModel::class, 'order_id');
    }

    public function batchs(): HasMany
    {
        return $this->hasMany(ScrapBatchModel::class, 'item_id');
    }

    public function getSkuStockNumAttribute()
    {
        return $this->sku_stock()->value('num') ?? 0;
    }

    public function sku_stock(): BelongsTo
    {
        return $this->belongsTo(SkuStockModel::class, 'sku_id', 'sku_id')
            ->where([
                'standard' => $this->standard,
            ]);
    }
}
