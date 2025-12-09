<?php

namespace App\Models;

use App\Traits\HasStandard;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransferItemModel extends BaseModel
{
    use HasStandard;

    protected $table = 'transfer_item';

    protected $with = ['sku', 'out_position', 'in_position'];

    protected $appends = ['standard_str', 'product_id'];

    public function sku(): BelongsTo
    {
        return $this->belongsTo(ProductSkuModel::class, 'sku_id');
    }

    public function out_position(): BelongsTo
    {
        return $this->belongsTo(PositionModel::class, 'out_position_id');
    }

    public function in_position(): BelongsTo
    {
        return $this->belongsTo(PositionModel::class, 'in_position_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(TransferOrderModel::class, 'order_id');
    }

    /**
     * Ensure product_id is available for nested forms when the column is absent on the table.
     */
    public function getProductIdAttribute(): ?int
    {
        if (array_key_exists('product_id', $this->attributes)) {
            return (int) $this->attributes['product_id'];
        }

        return $this->sku ? (int) $this->sku->product_id : null;
    }
}
