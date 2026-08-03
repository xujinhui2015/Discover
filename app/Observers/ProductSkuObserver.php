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

use App\Models\ProductSkuModel;
use App\Models\SkuStockModel;

class ProductSkuObserver
{
    /**
     * 新增SKU时同步建立零库存记录，
     * 否则该规格在首次入库前不会出现在物料库存列表中。
     *
     * @param ProductSkuModel $productSkuModel
     */
    public function created(ProductSkuModel $productSkuModel): void
    {
        $this->ensureStock($productSkuModel);
    }

    /**
     * 恢复软删除的SKU时同样补齐库存记录
     *
     * @param ProductSkuModel $productSkuModel
     */
    public function restored(ProductSkuModel $productSkuModel): void
    {
        $this->ensureStock($productSkuModel);
    }

    /**
     * @param ProductSkuModel $productSkuModel
     */
    protected function ensureStock(ProductSkuModel $productSkuModel): void
    {
        if (! $productSkuModel->id) {
            return;
        }

        // 该SKU只要已有任意检验标准的库存行，列表就能展示，无需再建占位记录
        if (SkuStockModel::query()->where('sku_id', $productSkuModel->id)->exists()) {
            return;
        }

        SkuStockModel::query()->create([
            'sku_id'   => $productSkuModel->id,
            'standard' => SkuStockModel::STANDARD_NO_CHOICE,
            'num'      => 0,
        ]);
    }
}
