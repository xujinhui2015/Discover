<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\TransferItem;
use App\Models\SkuStockBatchModel;
use App\Models\TransferItemModel;
use App\Models\TransferOrderModel;
use Dcat\Admin\Controllers\AdminController;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;

class TransferItemController extends AdminController
{
    protected function grid()
    {
        return Grid::make(new TransferItem(['sku.product', 'order']), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('order.order_no', '单号');
            $grid->column('sku.product.name', '物料名称');
            $grid->column('num', '数量');
            $grid->column('batch_no', '批次号');
            $grid->column('created_at');
            
            $grid->disableActions();
            $grid->disableCreateButton();
        });
    }

    protected function form()
    {
        return Form::make(new TransferItem(['sku.product', 'order']), function (Form $form) {
            $form->display('id');
            $form->display('order.order_no', '单号');
            $form->display('sku.product.name', '物料名称');
            $form->display('sku.product.unit.name', '单位');
            $form->display('sku.product.type_str', '分类');

            $form->select('batch_no', '批次号')->options(function () use ($form) {
                $model = $form->model();
                $skuId = $model->sku_id;
                $order = $model->order;
                $outPositionId = $order->out_position_id ?? null;

                if (! $skuId || ! $outPositionId) {
                    return [];
                }

                $options = SkuStockBatchModel::query()
                    ->where('sku_id', $skuId)
                    ->where('position_id', $outPositionId)
                    ->get()
                    ->mapWithKeys(function (SkuStockBatchModel $batch) {
                        return [$batch->batch_no => $batch->batch_no.' (库存: '.$batch->num.')'];
                    })
                    ->toArray();

                if ($model->batch_no && ! isset($options[$model->batch_no])) {
                    $options[$model->batch_no] = $model->batch_no;
                }

                return $options;
            })->required();

            $form->tableDecimal('num', '数量')->required();

            $form->saving(function (Form $form) {
                /** @var TransferItemModel $item */
                $item = $form->model();
                $order = $item->order instanceof TransferOrderModel
                    ? $item->order
                    : TransferOrderModel::find($item->order_id ?? ($item->order['id'] ?? null));

                if (! $order) {
                    return $form->error('单据不存在');
                }

                if ($order->review_status !== TransferOrderModel::REVIEW_STATUS_WAIT) {
                    return $form->error('仅待审核单可编辑');
                }

                $skuId = $item->sku_id;
                $batchNo = $form->input('batch_no');
                $numRaw = $form->input('num');
                $num = $numRaw === null ? null : (float) str_replace(',', '', $numRaw);
                $outPositionId = $order->out_position_id;

                if ($skuId && $batchNo && $num !== null) {
                    $batch = SkuStockBatchModel::query()
                        ->where('sku_id', $skuId)
                        ->where('batch_no', $batchNo)
                        ->where('position_id', $outPositionId)
                        ->first(['num', 'position_id']);

                    if (! $batch) {
                        return $form->error('所选批次不存在或库存不足');
                    }

                    // 批次变更时，数量直接同步为批次数量，避免手工填错
                    request()->merge(['num' => $batch->num]);
                    $item->num = $batch->num;
                }
            });
        });
    }
}
