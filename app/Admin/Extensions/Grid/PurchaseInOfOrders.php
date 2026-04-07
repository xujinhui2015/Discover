<?php

namespace App\Admin\Extensions\Grid;

use App\Models\PurchaseInOrderModel;
use App\Models\PurchaseInItemModel;
use App\Models\BaseModel;
use Dcat\Admin\Admin;
use Dcat\Admin\Support\LazyRenderable;
use Dcat\Admin\Widgets\Table;

class PurchaseInOfOrders extends LazyRenderable
{
    public function render()
    {
        Admin::script($this->script());

        $id = $this->key;
        $inOrders = PurchaseInOrderModel::query()
            ->where('with_id', $id)
            ->latest()
            ->get();

        $rows = [];
        foreach ($inOrders as $index => $inOrder) {
            $statusLabel = PurchaseInOrderModel::STATUS[$inOrder->status] ?? '-';
            $reviewLabel = BaseModel::REVIEW_STATUS[$inOrder->review_status] ?? '-';

            // 获取入库明细行
            $items = PurchaseInItemModel::query()
                ->where('order_id', $inOrder->id)
                ->with('sku.product')
                ->get();

            $itemDetails = $items->map(function ($item) {
                $name = $item->sku->product->name ?? '-';
                $attr = $item->sku->attr_value_ids_str ?? '';
                return "{$name} {$attr}: {$item->actual_num}";
            })->implode('<br>');

            $editUrl = route('purchase-in-orders.edit', $inOrder->id);
            $showBtn = $inOrder->review_status === BaseModel::REVIEW_STATUS_OK ? 'no' : 'yes';

            $rows[] = [
                $index + 1,
                "<a class='open-purchase-in-order' href='javascript:void(0)' data-show-btn='{$showBtn}' data-action='{$editUrl}'>{$inOrder->order_no}</a>",
                $statusLabel,
                $reviewLabel,
                $itemDetails ?: '-',
                $inOrder->created_at,
            ];
        }

        $titles = [
            '序号',
            '入库单号',
            '单据状态',
            '审核状态',
            '入库明细（物料: 数量）',
            '创建时间',
        ];

        return Table::make($titles, $rows);
    }

    public function script()
    {
        return <<<'JS'
        $(".open-purchase-in-order").on("click", function(){
            var action = $(this).data('action');
            var show_btn = $(this).data('show-btn');
            var option = {
                title: '采购入库单',
                type: 2,
                area: ['85%', '90%'],
                content: [action],
                scrollbar: false,
                end: function(){
                    if (show_btn == "yes") {
                        Dcat.reload();
                    }
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
                        url: url,
                        data: orderInfo.serialize(),
                        success: function (data) {
                            if (data.status) {
                                Dcat.success(data.message);
                            } else {
                                Dcat.error(data.message);
                            }
                        },
                        error: function(a, b, c) {
                            Dcat.handleAjaxError(a, b, c);
                        },
                        complete: function(a, b) {
                            Dcat.NP.done();
                        }
                    });
                    layer.close(index);
                };
            }
            layer.open(option);
        });
JS;
    }
}
