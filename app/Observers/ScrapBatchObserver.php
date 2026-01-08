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

namespace App\Observers;

use App\Models\ScrapBatchModel;
use App\Models\ScrapItemModel;
use Illuminate\Database\Eloquent\Builder;

class ScrapBatchObserver
{
    protected function updateItemActual(ScrapBatchModel $scrapBatchModel): void
    {
        $scrapBatchs = ScrapBatchModel::query()->where(function (Builder $query) use ($scrapBatchModel) {
            $query->where('item_id', $scrapBatchModel->item_id);
        })->get(['actual_num', 'stock_batch_id']);

        ScrapItemModel::query()->whereId($scrapBatchModel->item_id)->update([
            'actual_num' => $scrapBatchs->sum('actual_num'),
        ]);
    }

    public function deleted(ScrapBatchModel $scrapBatchModel): void
    {
        $this->updateItemActual($scrapBatchModel);
    }

    public function saved(ScrapBatchModel $scrapBatchModel): void
    {
        $this->updateItemActual($scrapBatchModel);
    }
}
