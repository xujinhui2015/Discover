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

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScrapBatchModel extends BaseModel
{
    protected $table = 'scrap_batch';

    protected $with = ['item', 'stock_batch'];

    public function item(): BelongsTo
    {
        return $this->belongsTo(ScrapItemModel::class, 'item_id');
    }

    public function stock_batch(): BelongsTo
    {
        return $this->belongsTo(SkuStockBatchModel::class, 'stock_batch_id');
    }
}
