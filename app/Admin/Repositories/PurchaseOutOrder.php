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

use App\Models\PurchaseInOrderModel;
use App\Models\PurchaseOutOrderModel as Model;
use Dcat\Admin\Repositories\EloquentRepository;
use Illuminate\Support\Collection;

class PurchaseOutOrder extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;

    public function getWithOrder(): Collection
    {
        return PurchaseInOrderModel::query()
            ->where('review_status', PurchaseInOrderModel::REVIEW_STATUS_OK)
            ->orderBy('id', 'desc')
            ->pluck('order_no', 'id');
    }
}
