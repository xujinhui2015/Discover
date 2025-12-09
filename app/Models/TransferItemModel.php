<?php

namespace App\Models;

use App\Traits\HasStandard;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransferItemModel extends BaseModel
{
    use HasStandard;

    protected $table = 'transfer_item';

    protected $with = ['sku', 'out_position', 'in_position'];

    protected $appends = ['standard_str'];

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
}
