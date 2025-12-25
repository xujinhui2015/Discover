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

use App\Models\BaseModel;
use App\Models\MakeProductItemModel;
use App\Models\MakeProductOrderModel;
use App\Models\TaskModel;
use Dcat\Admin\Admin;
use Dcat\Admin\Grid\RowAction;
use Illuminate\Support\Facades\DB;

class AddMakeProduct extends RowAction
{
    /**
     * @return string
     */
    protected $title = '生产入库';

    public function html()
    {
        $makeProductOrder = MakeProductOrderModel::query()->where('with_id', $this->getKey())->first();

        if (!$makeProductOrder) {
            $makeProductOrder = $this->createMakeProductOrder($this->getKey());
        }

        $url = route('make-product-orders.edit', $makeProductOrder->id);
        $showBtn = $makeProductOrder->review_status === BaseModel::REVIEW_STATUS_OK ? 'no' : 'yes';

        $style = 'display:block; padding:6px 10px; margin:4px 0; border-radius:4px; text-align:center; cursor:pointer; transition:all 0.2s ease; width:100%; box-sizing:border-box; white-space:nowrap;';
        return <<<HTML
<a style="{$style}" class="{$this->getElementClass()} btn btn-sm btn-success grid-actions-btn" data-show-btn="{$showBtn}" href="javascript:void(0)" data-action="$url">{$this->title()}</a>
HTML;
    }

    /**
     * 自动创建生产入库单
     *
     * @param int $taskId
     * @return MakeProductOrderModel
     */
    protected function createMakeProductOrder(int $taskId): MakeProductOrderModel
    {
        return DB::transaction(function () use ($taskId) {
            $task = TaskModel::query()->with('sku')->findOrFail($taskId);

            $userId = Admin::user()?->id ?? 1;

            // 创建生产入库单主表
            $makeProductOrder = new MakeProductOrderModel();
            $makeProductOrder->with_id = $task->id;
            $makeProductOrder->order_no = build_order_no('SCRK');
            $makeProductOrder->user_id = $userId;
            $makeProductOrder->apply_id = $userId;
            $makeProductOrder->other = '';
            $makeProductOrder->review_status = BaseModel::REVIEW_STATUS_WAIT;
            $makeProductOrder->created_at = now();
            $makeProductOrder->save();

            // 创建物料明细
            $item = new MakeProductItemModel();
            $item->order_id = $makeProductOrder->id;
            $item->sku_id = $task->sku_id;
            $item->standard = $task->standard;
            $item->should_num = $task->plan_num;
            $item->actual_num = 0;
            $item->cost_price = 0;
            $item->position_id = 0;
            $item->batch_no = 'PC' . date('Ymd');
            $item->percent = $task->percent ?? 0;
            $item->save();

            return $makeProductOrder;
        });
    }

    public function script()
    {
        $class = $this->getElementClass();
        return <<<JS
        $(".{$class}").on("click",function(){
            var action = $(this).data('action');
            var show_btn = $(this).data('show-btn');
            var option = {
                title:'生产入库单',
                type: 2,
                area: ['85%', '90%'], //宽高
                content:[action],
                scrollbar:false,
                // maxmin:true,
                end: function(){
                    Dcat.reload();
                },
            };
            if (show_btn == 'yes') {
                option.btn = ['保存'];
                option.btn1 = function(index, layero){
                    var orderInfo = $('#layui-layer-iframe'+index).contents().find('.content .row:eq(0) .col-md-12:eq(0) form:eq(0)');
                    var url = orderInfo.attr('action');
                    Dcat.NP.start();
                    $.ajax({
                        type: "POST",
                        dataType: "json",
                        url: url ,//url
                        data: orderInfo.serialize(),
                        success: function (data) {
                            if (data.status) {
                                Dcat.success(data.message);
                            } else {
                                Dcat.error(data.message);
                            }
                        },
                        error : function(a,b,c) {
                            Dcat.handleAjaxError(a, b, c);
                        },
                        complete:function(a,b) {
                            Dcat.NP.done();
                        }
                    });
                    layer.close(index);
                };
            }
            layer.open(option)
        });
JS;
    }
}
