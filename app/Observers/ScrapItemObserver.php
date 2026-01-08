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

class ScrapItemObserver
{
    public function saving(ScrapItemModel $scrapItemModel): void
    {
        ScrapBatchModel::query()->where(function (Builder $query) use ($scrapItemModel) {
            $query->where('item_id', $scrapItemModel->id);
        })->delete();
        $scrapItemModel->actual_num = 0;
    }

    public function deleted(ScrapItemModel $scrapItemModel): void
    {
        ScrapBatchModel::query()->where(function (Builder $query) use ($scrapItemModel) {
            $query->where('item_id', $scrapItemModel->id);
        })->delete();
    }
}
