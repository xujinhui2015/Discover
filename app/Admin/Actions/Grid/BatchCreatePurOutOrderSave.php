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

use App\Models\PurchaseInItemModel;
use App\Models\PurchaseInOrderModel;
use App\Models\PurchaseOutOrderModel;
use Dcat\Admin\Actions\Response;
use Dcat\Admin\Admin;
use Dcat\Admin\Grid\BatchAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BatchCreatePurOutOrderSave extends BatchAction
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
                $purchaseInOrder = PurchaseInOrderModel::findOrFail($key);
                $this->orderSync($purchaseInOrder);
            }
        });
        return $this->response()->script("parent.layer.close({$index})");
    }

    protected function orderSync(PurchaseInOrderModel $purchaseInOrder): void
    {
        $outOrder = PurchaseOutOrderModel::create([
            'order_no' => build_order_no('CT'),
            'supplier_id' => $purchaseInOrder->supplier_id,
            'status' => PurchaseOutOrderModel::STATUS_RETURNING,
            'other' => $purchaseInOrder->other,
            'user_id' => Admin::user()->id,
            'with_id' => $purchaseInOrder->id,
        ]);

        $items = $purchaseInOrder->items->map(function (PurchaseInItemModel $purchaseInItem) {
            return [
                'sku_id' => $purchaseInItem->sku_id,
                'should_num' => $purchaseInItem->actual_num,
                'actual_num' => 0,
                'price' => $purchaseInItem->price,
                'standard' => $purchaseInItem->standard,
                'batch_no' => $purchaseInItem->batch_no,
                'position_id' => $purchaseInItem->position_id,
            ];
        });
        $outOrder->items()->createMany($items);
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
