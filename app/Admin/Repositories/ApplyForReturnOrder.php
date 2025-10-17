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

namespace App\Admin\Repositories;

use App\Models\ApplyForOrderModel;
use App\Models\ApplyForReturnOrderModel as Model;
use Dcat\Admin\Repositories\EloquentRepository;
use Illuminate\Support\Collection;

class ApplyForReturnOrder extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;

    public function getApplyForOrder(): Collection
    {
        return ApplyForOrderModel::where('review_status', ApplyForOrderModel::REVIEW_STATUS_OK)
            ->orderBy('id', 'desc')->pluck('order_no', 'id');
    }
}
