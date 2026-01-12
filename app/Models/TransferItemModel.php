<?php

namespace App\Models;

use App\Traits\HasStandard;
use Exception;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\SkuStockBatchModel;
use App\Models\TransferOrderModel;

class TransferItemModel extends BaseModel
{
    use HasStandard;

    protected $table = 'transfer_item';

    protected $with = ['sku.product'];

    protected $appends = ['standard_str', 'product_id', 'unit'];

    protected static function booted(): void
    {
        static::saving(function (TransferItemModel $item) {
            // 批次调整时，同步数量为所选批次的库存，保持与新增页面一致的体验
            if (! $item->exists || ! $item->isDirty('batch_no')) {
                return true;
            }

            $order = $item->relationLoaded('order') ? $item->order : $item->order()->first();

            if (! $order || $order->review_status !== TransferOrderModel::REVIEW_STATUS_WAIT) {
                return true;
            }

            $batch = SkuStockBatchModel::query()
                ->where('sku_id', $item->sku_id)
                ->where('batch_no', $item->batch_no)
                ->where('position_id', $order->out_position_id)
                ->first(['num']);

            if (! $batch) {
                throw new Exception('所选批次不存在或库存不足');
            }

            $item->num = $batch->num;

            return true;
        });
    }

    public function sku(): BelongsTo
    {
        return $this->belongsTo(ProductSkuModel::class, 'sku_id')->withTrashed();
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(TransferOrderModel::class, 'order_id');
    }

    public function getUnitAttribute(): string
    {
        return $this->sku && $this->sku->product && $this->sku->product->unit
            ? (string) $this->sku->product->unit->name
            : '';
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
