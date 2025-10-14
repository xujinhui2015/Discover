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

/**
 * App\Models\SkuStockModel
 *
 * @property int $id
 * @property int $sku_id 物料sku_id
 * @property int $num 物料库存
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\ProductSkuModel $sku
 * @method static \Illuminate\Database\Eloquent\Builder|SkuStockModel newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SkuStockModel newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|SkuStockModel query()
 * @method static \Illuminate\Database\Eloquent\Builder|SkuStockModel whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SkuStockModel whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SkuStockModel whereNum($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SkuStockModel whereSkuId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SkuStockModel whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property string $percent 含绒量
 * @property int $standard 检验标准
 * @property-read mixed $standard_str
 * @method static \Illuminate\Database\Eloquent\Builder|SkuStockModel wherePercent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|SkuStockModel whereStandard($value)
 */
class SkuStockModel extends BaseModel
{
    use HasStandard;

    protected $table = 'sku_stock';

    protected $with = ['sku'];

    // 0库存正常1接近安全库存2低于安全库存
    const WARNING_STATUS_NORMAL = 0;
    const WARNING_STATUS_NEAR = 1;
    const WARNING_STATUS_LOW = 2;

    const WARNING_STATUS = [
        self::WARNING_STATUS_NORMAL => '库存正常',
        self::WARNING_STATUS_NEAR => '接近安全库存',
        self::WARNING_STATUS_LOW => '低于安全库存',
    ];

    const WARNING_STATUS_COLOR = [
        self::WARNING_STATUS_NORMAL => '#2e7d32',
        self::WARNING_STATUS_NEAR => '#f57c00',
        self::WARNING_STATUS_LOW => '#c62828',
    ];

    const WARNING_STATUS_STYLE = [
        self::WARNING_STATUS_NORMAL =>
            "<span style='font-weight: bold;background: #e6f4ea; color: ".self::WARNING_STATUS_COLOR[self::WARNING_STATUS_NORMAL]."; padding: 2px 8px; border-radius: 12px; font-size: 12px;'>✅".self::WARNING_STATUS[self::WARNING_STATUS_NORMAL]."</span>",
        self::WARNING_STATUS_NEAR =>
            "<span style='font-weight: bold;background: #fff8e1; color: ".self::WARNING_STATUS_COLOR[self::WARNING_STATUS_NEAR]."; padding: 2px 8px; border-radius: 12px; font-size: 12px;'>⚠".self::WARNING_STATUS[self::WARNING_STATUS_NEAR]."</span>",
        self::WARNING_STATUS_LOW =>
            "<span style='font-weight: bold;background: #ffebee; color: ".self::WARNING_STATUS_COLOR[self::WARNING_STATUS_LOW]."; padding: 2px 8px; border-radius: 12px; font-size: 12px;'>❗".self::WARNING_STATUS[self::WARNING_STATUS_LOW]."</span>",
    ];

    protected $appends = ['standard_str', 'warning_status', 'warning_status_str'];

    public function sku(): BelongsTo
    {
        return $this->belongsTo(ProductSkuModel::class, 'sku_id');
    }

    /**
     * 预警状态
     */
    public function getWarningStatusAttribute(): int
    {
        $stock = $this->num;
        $warningStock = $this->sku->product->warning_num;

        // 如果没有安全库存配置，则认为正常
        if ($warningStock <= 0) {
            return self::WARNING_STATUS_NORMAL;
        }

        // 判断逻辑
        if ($stock < $warningStock) {
            return self::WARNING_STATUS_LOW;
        }

        if ($stock < $warningStock * 1.2) {
            return self::WARNING_STATUS_NEAR;
        }

        return self::WARNING_STATUS_NORMAL;
    }

    // 预警状态查询
    public function scopeWarningStatus($query, ?int $status = null)
    {
        if ($status === self::WARNING_STATUS_NORMAL) {
            return $query->whereHas('sku.product', function ($q) {
                $q->whereRaw('sku_stock.num >= product.warning_num * 1.2');
            });
        }

        if ($status === self::WARNING_STATUS_NEAR) {
            return $query->whereHas('sku.product', function ($q) {
                $q->whereRaw('sku_stock.num >= product.warning_num AND sku_stock.num < product.warning_num * 1.2');
            });
        }

        if ($status === self::WARNING_STATUS_LOW) {
            return $query->whereHas('sku.product', function ($q) {
                $q->whereRaw('sku_stock.num < product.warning_num');
            });
        }
    }

    public function getWarningStatusStrAttribute(): string
    {
        return self::WARNING_STATUS[$this->warning_status];
    }
}
