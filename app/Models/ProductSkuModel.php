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
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * App\Models\ProductSkuModel
 *
 * @property int $id
 * @property int $product_id 物料id
 * @property mixed $attr_value_ids 选项值ids
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @method static \Illuminate\Database\Eloquent\Builder|ProductSkuModel newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProductSkuModel newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProductSkuModel query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProductSkuModel whereAttrValueIds($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductSkuModel whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductSkuModel whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProductSkuModel whereDeletedAt($value)
 * @method static \Illuminate\Database\Query\Builder|ProductSkuModel onlyTrashed()
 * @method static \Illuminate\Database\Query\Builder|ProductSkuModel withTrashed()
 * @method static \Illuminate\Database\Query\Builder|ProductSkuModel withoutTrashed()
 * @mixin \Eloquent
 * @property-read string $attr_value_ids_str
 * @property-read \App\Models\ProductModel $product
 */
class ProductSkuModel extends BaseModel
{
    use SoftDeletes;

    protected $table = 'product_sku';
    
    // 不使用 created_at 和 updated_at，但保留 deleted_at
    const CREATED_AT = null;
    const UPDATED_AT = null;

//    protected $with = ['product'];

    protected $appends = ['attr_value_ids_str'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(ProductModel::class, 'product_id')->withTrashed();
    }

    public function getAttrValueIdsStrAttribute(): string
    {
        if (! $this->attr_value_ids) {
            return '';
        }
        return AttrValueModel::getAttrValues()->only(explode(',', $this->attr_value_ids))->implode(',');
    }
}
