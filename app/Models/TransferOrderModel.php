<?php

namespace App\Models;

use Dcat\Admin\Models\Administrator;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransferOrderModel extends BaseModel
{
    use SoftDeletes;

    protected $table = 'transfer_order';

    const REVIEW_STATUS_WAIT = 0;
    const REVIEW_STATUS_OK = 1;

    const REVIEW_STATUS = [
        self::REVIEW_STATUS_WAIT => '待审核',
        self::REVIEW_STATUS_OK => '已审核',
    ];

    const REVIEW_STATUS_COLOR = [
        self::REVIEW_STATUS_WAIT => 'red',
        self::REVIEW_STATUS_OK => 'green',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(Administrator::class, 'user_id');
    }
    
    public function audit_user(): BelongsTo
    {
        return $this->belongsTo(Administrator::class, 'audit_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TransferItemModel::class, 'order_id');
    }

    public function getReviewStatusStrAttribute(): string
    {
        return self::REVIEW_STATUS[$this->review_status] ?? '';
    }
}
