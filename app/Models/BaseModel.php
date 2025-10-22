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

use Dcat\Admin\Traits\HasDateTimeFormatter;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\BaseModel
 *
 * @method static \Illuminate\Database\Eloquent\Builder|BaseModel newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BaseModel newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BaseModel query()
 * @mixin \Eloquent
 */
class BaseModel extends Model
{
    use HasDateTimeFormatter;

    protected $guarded = ['id'];

    const STATUS_NO = 0;
    const STATUS_OK = 1;

    const REVIEW_STATUS_WAIT = 0;
    const REVIEW_STATUS_OK = 1;
    const REVIEW_STATUS_REREVIEW = 2;

    const STANDARD_NO_CHOICE = 0;
    const STANDARD_INDUSTRY = 1;
    const STANDARD_CHINA_2023 = 2;
    const STANDARD_CHINA_2022 = 3;
    const STANDARD_IFRA = 4;

//字段改为：
//（默认暂无）
//行标 QB/T 1858-2004
//国标 GB/T 27575-2023
//国标 GB/T 22731-2022
//IFRA 标准

    const STANDARD = [
        self::STANDARD_NO_CHOICE => '暂无',
        self::STANDARD_INDUSTRY => '行标 QB/T 1858-2004',
        self::STANDARD_CHINA_2023     => '国标 GB/T 27575-2023',
        self::STANDARD_CHINA_2022     => '国标 GB/T 22731-2022',
        self::STANDARD_IFRA => 'IFRA 标准',
    ];

    const REVIEW_STATUS = [
        self::REVIEW_STATUS_WAIT     => "待审核",
        self::REVIEW_STATUS_OK       => "已审核",
        self::REVIEW_STATUS_REREVIEW => "反审核",
    ];

    const REVIEW_STATUS_COLOR = [
        self::REVIEW_STATUS_WAIT     => "gray",
        self::REVIEW_STATUS_OK       => "success",
        self::REVIEW_STATUS_REREVIEW => "red",
    ];
}
