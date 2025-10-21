<?php

namespace App\Admin\Actions\Grid;

use App\Models\ProductModel;
use App\Models\ProductSkuModel;
use App\Models\PurchaseItemModel;
use App\Models\SkuStockModel;
use Dcat\Admin\Actions\Response;
use Dcat\Admin\Grid\BatchAction;
use Illuminate\Support\Collection;
use JetBrains\PhpStorm\NoReturn;
use Illuminate\Http\Request;

class BatchDeleteProduct extends BatchAction
{
    protected $title = '删除';

    // 确认弹窗信息
    public function confirm()
    {
        return '确定要删除物料吗？';
    }

    // 处理请求
    public function handle(Request $request)
    {
        // 获取选中的文章ID数组
        $ids = $this->getKey();

        // 检查产品是否已存在单据
        $allSkuIds = ProductSkuModel::whereIn('product_id', $ids)
            ->pluck('product_id', 'id');

        if (PurchaseItemModel::where('sku_id', $allSkuIds->keys())->exists()) {
            return $this->response()->error('删除失败：已存在采购单据')->refresh();
        }

        if (SkuStockModel::where('sku_id', $allSkuIds->keys())->exists()) {
            return $this->response()->error('删除失败：已存在库存')->refresh();
        }

        // 删除商品
        foreach ($ids as $id) {
            ProductModel::query()->whereIn('id', $allSkuIds->values())->first()->delete();
        }

        return $this->response()->success('删除成功')->refresh();
    }
}
