<?php

namespace App\Admin\Extensions\Grid;

use App\Models\PurchaseInOrderModel;
use App\Models\PurchaseInItemModel;
use App\Models\BaseModel;
use Dcat\Admin\Support\LazyRenderable;
use Dcat\Admin\Widgets\Table;

class PurchaseInOfOrders extends LazyRenderable
{
    public function render()
    {
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

            $rows[] = [
                $index + 1,
                "<a href='" . admin_url("purchase-in-orders/{$inOrder->id}") . "' target='_blank'>{$inOrder->order_no}</a>",
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
}
