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

use App\Models\ApplyForItemModel;
use App\Models\ApplyForOrderModel;
use App\Models\ApplyForReturnOrderModel;
use Dcat\Admin\Admin;
use Dcat\Admin\Grid\BatchAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BatchCreateApplyForReturnOrderSave extends BatchAction
{
    /**
     * @return string
     */
    protected $title = '保存';

    public function handle(Request $request)
    {
        $index = $request->input('_index');
        DB::transaction(function () {
            foreach ($this->getKey() as $key) {
                $applyForOrderModel = ApplyForOrderModel::findOrFail($key);
                $this->orderSync($applyForOrderModel);
            }
        });
        return $this->response()->script("parent.layer.close({$index})");
    }

    protected function orderSync(ApplyForOrderModel $applyForOrderModel): void
    {
        $applyForReturnOrder = ApplyForReturnOrderModel::create([
            'apply_for_order_id' => $applyForOrderModel->id,
            'order_no' => build_order_no('SLR'),
            'user_id' => Admin::user()->id,
            'other' => $applyForOrderModel->other,
            'status' => ApplyForOrderModel::REVIEW_STATUS_WAIT,
        ]);

        $items = $applyForOrderModel->items->map(function (ApplyForItemModel $applyForItemModel) {
            return [
                'sku_id' => $applyForItemModel->sku_id,
                'should_num' => $applyForItemModel->should_num,
            ];
        });
        $applyForReturnOrder->items()->createMany($items);
    }

    protected function html(): string
    {
        return <<<HTML
<a {$this->formatHtmlAttributes()}><button class="btn btn-primary btn-mini"><i class="feather icon-user-check"></i> {$this->title()}</button></a>
HTML;
    }

    public function actionScript(): string
    {
        $warning = "请选择领料明细！";

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
