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

use App\Models\CustomerModel;
use App\Models\SaleOutOrderModel;
use App\Models\SaleInOrderModel as Model;
use Dcat\Admin\Repositories\EloquentRepository;
use Illuminate\Support\Collection;

class SaleInOrder extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;

    public function customer(): Collection
    {
        return CustomerModel::OrderBy('id', 'desc')->pluck('name', 'id');
    }

    public function getWithOrder(): Collection
    {
        return SaleOutOrderModel::where([
            'status'        => SaleOutOrderModel::STATUS_DOING,
            'review_status' => SaleOutOrderModel::REVIEW_STATUS_OK
        ])->orderBy('id', 'desc')->pluck('order_no', 'id');
    }
}
