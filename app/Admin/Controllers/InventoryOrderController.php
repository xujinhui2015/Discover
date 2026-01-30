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

namespace App\Admin\Controllers;

use App\Admin\Actions\Grid\BatchOrderPrint;
use App\Admin\Actions\Grid\BatchStockSelect;
use App\Admin\Actions\Grid\EditOrder;
use App\Admin\Actions\Grid\OrderDelete;
use App\Admin\Actions\Grid\OrderPrint;
use App\Admin\Actions\Grid\OrderReview;
use App\Admin\Extensions\Form\Order\OrderController;
use App\Admin\Extensions\Grid\ColumnSelector;
use App\Admin\Extensions\Grid\InventoryOrderItemDetail;
use App\Admin\Repositories\InventoryOrder;
use App\Models\InventoryItemModel;
use App\Models\InventoryModel;
use App\Models\InventoryOrderModel;
use App\Models\PersonalConfigModel;
use App\Models\ProductModel;
use App\Models\ProductSkuModel;
use App\Models\SkuStockBatchModel;
use Dcat\Admin\Admin;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Models\Administrator;
use Illuminate\Database\Eloquent\Builder;

class InventoryOrderController extends OrderController
{
    public $item_relations = ['stock_batch', 'stock_batch.sku', 'stock_batch.sku.product'];
    private const MATERIAL_DETAIL_STYLE_KEY = 'material_detail_style';
    private const MATERIAL_DETAIL_STYLE_DETAIL = 'detail';
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new InventoryOrder(['with_order', 'user']), function (Grid $grid) {
            $grid->model()->whereHas('with_order', function (Builder $builder) {
                $builder->where('status', "!=", InventoryModel::STATUS_NOT_STARTED);
            })->orderBy('id', 'desc');
            $useNameStyle = $this->useMaterialNameStyle();
            
            // 定义列配置（用于列选择器）
            $columnConfig = [
                ['name' => 'id', 'label' => 'ID'],
                ['name' => 'order_no', 'label' => '单号'],
                ['name' => 'with_order_no', 'label' => '任务单号'],
                ['name' => 'user', 'label' => '创建用户'],
                ['name' => 'product_info', 'label' => '物料信息'],
                ['name' => 'review_status', 'label' => '审核状态'],
                ['name' => 'created_at', 'label' => '创建时间'],
            ];
            
            $grid->column('id')->setHeaderAttributes(['class' => 'column-id'])->sortable();
            $grid->column('order_no')->setHeaderAttributes(['class' => 'column-order_no']);
            $grid->column('with_order.order_no', '任务单号')->setHeaderAttributes(['class' => 'column-with_order_no'])->emp();
            $grid->column('user.username', '创建用户')->setHeaderAttributes(['class' => 'column-user']);
            if ($useNameStyle) {
                $grid->column('product_names', '物料名称')->setHeaderAttributes(['class' => 'column-product_info'])->display(function () {
                    $productNames = ProductModel::query()
                        ->whereIn('id', ProductSkuModel::query()
                            ->whereIn('id', SkuStockBatchModel::query()
                                ->whereIn('id', InventoryItemModel::query()
                                    ->where('order_id', $this->id)
                                    ->select('stock_batch_id'))
                                ->select('sku_id'))
                            ->select('product_id'))
                        ->pluck('name')
                        ->toArray();
                    $displayNames = '';
                    foreach ($productNames as $productName) {
                        $displayNames .= '<span class="badge" style="background:#5c6bc6">' . $productName . '</span><br>';
                    }
                    return $displayNames;
                });
            } else {
                $grid->column('_', '物料明细')
                    ->setAttributes(['class' => 'material-detail-cell'])
                    ->setHeaderAttributes(['class' => 'column-product_info'])
                    ->expand(InventoryOrderItemDetail::class);
            }
            $grid->column('review_status', '审核状态')->setHeaderAttributes(['class' => 'column-review_status'])->using($this->oredr_model::REVIEW_STATUS)->label($this->oredr_model::REVIEW_STATUS_COLOR);
            $grid->column('created_at')->setHeaderAttributes(['class' => 'column-created_at']);
            $grid->disableQuickEditButton();
            $grid->actions(EditOrder::make());

            $grid->disableCreateButton();
            $grid->tools(function ($tools) use ($columnConfig) {
                $tools->append(BatchOrderPrint::make());
                $tools->append(new ColumnSelector($columnConfig));
            });

            $grid->filter(function (Grid\Filter $filter) {
                $filter->where('product_keyword', function (Builder $query) {
                    $keyword = $this->getValue();
                    $query->whereHasIn('items', function (Builder $query) use ($keyword) {
                        $query->whereHasIn('stock_batch.sku.product', function (Builder $query) use ($keyword) {
                            $query->where(function (Builder $query) use ($keyword) {
                                $query->orWhere('name', 'like', '%' . $keyword . '%');
                                $query->orWhere('py_code', 'like', '%' . $keyword . '%');
                                $query->orWhere('item_no', 'like', '%' . $keyword . '%');
                            });
                        });
                    });
                }, '物料信息')->placeholder('物料名称，拼音码，编号')->width(3);
            });
        });
    }

    protected function setForm(Form &$form): void
    {
        $form->row(function (Form\Row $row) {
            $row->width(6)->text('order_no', '单号')->default(build_order_no('PD'))->required()->readOnly();
            $row->width(6)->datetime('created_at', '业务日期')->default(now())->required();
        });
        $form->row(function (Form\Row $row) use ($form) {
            if ($form->isEditing()) {
                $row->width(6)->select('with_id', '相关单据')->options(InventoryModel::query()->pluck(
                    'order_no',
                    'id'
                ))->disable();
            }
            $users = Administrator::query()->latest()->pluck('name', 'id');
            $row->width(6)->select('apply_id', '审批人')->options($users)->default(head($users->keys()->toArray()))->required();
        });
        $form->row(function (Form\Row $row) {
            $row->width(6)->text('other', '备注')->saveAsString();
        });
    }

    public function creating(Form &$form): void
    {
    }

    public function setItems(Grid &$grid): void
    {
        $order = $this->order;
        $grid->column('stock_batch.sku.product.name', '物料名称');
        $grid->column('stock_batch.sku.attr_value_ids_str', '属性');
        $grid->column('stock_batch.batch_no', '批次号');
//        $grid->column('stock_batch.percent', '含绒量');
        $grid->column('stock_batch.standard_str', '通用标准');
        $grid->column('cost_price', "成本单价");
        $grid->column('should_num', '库存数量');
        $grid->column("actual_num", "实盘数量")->if(function (Grid\Column $colum) use ($order) {
            return $order->review_status === InventoryOrderModel::REVIEW_STATUS_OK;
        })->display(function ($val) {
            return $val;
        })->else()->edit();
        $grid->column('diff_num', '盈亏数量');
        $grid->column("diff_cost_price", "盈亏金额")->display(function () {
            return bcmul($this->diff_num, $this->cost_price, 2);
        });
    }

    public function setItemsCommon(Grid &$grid): void
    {
        $grid->tools(OrderPrint::make());
        if ($this->order && $this->order->review_status !== $this->oredr_model::REVIEW_STATUS_OK) {
            $with_order = $this->order->with_order;
            if ($with_order->status === InventoryModel::STATUS_WAIT && $this->shouldShowReviewTool()) {
                $grid->tools(OrderReview::make(show_order_review($this->order->review_status)));
            }
            $grid->tools(OrderDelete::make());
            $grid->tools(BatchStockSelect::make());
        }
        $grid->disableActions();
        $grid->disablePagination();
        $grid->disableCreateButton();
        $grid->disableBatchDelete();
    }

    private function useMaterialNameStyle(): bool
    {
        $userId = (int) optional(Admin::user())->id;
        if (! $userId) {
            return true;
        }

        $style = PersonalConfigModel::query()
            ->where('user_id', $userId)
            ->where('config_key', self::MATERIAL_DETAIL_STYLE_KEY)
            ->value('config_value');

        return $style !== self::MATERIAL_DETAIL_STYLE_DETAIL;
    }
}
