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

namespace App\Admin\Actions\Grid;

use App\Models\SaleOutItemModel;
use App\Models\SaleOutOrderModel;
use App\Models\SaleInOrderModel;
use Dcat\Admin\Actions\Response;
use Dcat\Admin\Admin;
use Dcat\Admin\Grid\BatchAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BatchCreateSaleInOrderSave extends BatchAction
{
    /**
     * @return string
     */
    protected $title = '保存';

    /**
     * Handle the action request.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function handle(Request $request)
    {
        $index = $request->input('_index');
        DB::transaction(function () {
            foreach ($this->getKey() as $key) {
                $sale_out_order = SaleOutOrderModel::findOrFail($key);
                $this->orderSync($sale_out_order);
            }
        });
        return $this->response()->script("parent.layer.close({$index})");
    }

    protected function orderSync(SaleOutOrderModel $saleOutOrderModel): void
    {
        $in_order = SaleInOrderModel::create([
            'order_no' => build_order_no('TH'),
            'customer_id' => $saleOutOrderModel->customer_id,
            'status' => SaleInOrderModel::STATUS_SEND,
            'other' => $saleOutOrderModel->other,
            'user_id' => Admin::user()->id,
            'with_id' => $saleOutOrderModel->id,
            'address_id' => $saleOutOrderModel->address_id,
            'drawee_id' => $saleOutOrderModel->drawee_id,
        ]);
        $items = $saleOutOrderModel->items->map(function (SaleOutItemModel $saleOutItemModel) {
            return [
                'sku_id' => $saleOutItemModel->sku_id,
                'should_num' => $saleOutItemModel->actual_num,
                'actual_num' => 0,
                'return_num' => 0,
                'price' => $saleOutItemModel->price,
                'standard' => $saleOutItemModel->standard,
            ];
        });
        $in_order->items()->createMany($items);
    }

    protected function html(): string
    {
        return <<<HTML
<a {$this->formatHtmlAttributes()}><button class="btn btn-primary btn-mini"><i class="feather icon-user-check"></i> {$this->title()}</button></a>
HTML;
    }

    public function actionScript(): string
    {
        $warning = "请选择退货的明细！";

        return <<<JS
function (data, target, action) {
    var key = {$this->getSelectedKeysScript()}

    if (key.length === 0) {
        Dcat.warning('{$warning}');
        return false;
    }
    data["_index"] = parent.layer.getFrameIndex(window.name);

    // 设置主键为复选框选中的行ID数组
    action.options.key = key;
}
JS;
    }
}
