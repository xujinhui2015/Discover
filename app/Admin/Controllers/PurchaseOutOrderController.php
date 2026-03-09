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

use App\Admin\Actions\Grid\BatchCreatePurOutOrder;
use App\Admin\Actions\Grid\BatchOrderPrint;
use App\Admin\Actions\Grid\EditOrder;
use App\Admin\Actions\Grid\PurchaseOutOrderUnreview;
use App\Admin\Extensions\Form\Order\OrderController;
use App\Admin\Extensions\Grid\ColumnSelector;
use App\Admin\Extensions\Grid\PurchaseOutOrderItemDetail;
use App\Admin\Repositories\PurchaseOutOrder;
use App\Models\PersonalConfigModel;
use App\Models\PositionModel;
use App\Models\ProductModel;
use App\Models\ProductSkuModel;
use App\Models\PurchaseInOrderModel;
use App\Models\PurchaseOutItemModel;
use App\Models\PurchaseOutOrderModel;
use App\Repositories\SupplierRepository;
use Dcat\Admin\Admin;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Illuminate\Database\Eloquent\Builder;

class PurchaseOutOrderController extends OrderController
{
    private const MATERIAL_DETAIL_STYLE_KEY = 'material_detail_style';
    private const MATERIAL_DETAIL_STYLE_DETAIL = 'detail';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new PurchaseOutOrder(['user', 'supplier', 'with_order']), function (Grid $grid) {
            $useNameStyle = $this->useMaterialNameStyle();
            
            // 定义列配置（用于列选择器）
            $columnConfig = [
                ['name' => 'id', 'label' => 'ID'],
                ['name' => 'order_no', 'label' => '单号'],
                ['name' => 'with_order_no', 'label' => '关联单号'],
                ['name' => 'review_status', 'label' => '审核状态'],
                ['name' => 'product_info', 'label' => '物料信息'],
                ['name' => 'supplier', 'label' => '供应商名称'],
                ['name' => 'user', 'label' => '创建用户'],
                ['name' => 'created_at', 'label' => '创建时间'],
                ['name' => 'apply_at', 'label' => '审核时间'],
                ['name' => 'other', 'label' => '备注'],
            ];
            
            $grid->column('id')->setHeaderAttributes(['class' => 'column-id'])->sortable();
            $grid->column('order_no')->setHeaderAttributes(['class' => 'column-order_no']);
            $grid->column('with_order.order_no', '关联单号')->setHeaderAttributes(['class' => 'column-with_order_no'])->emp();
            $grid->column('review_status', '审核状态')->setHeaderAttributes(['class' => 'column-review_status'])->using($this->oredr_model::REVIEW_STATUS)->label($this->oredr_model::REVIEW_STATUS_COLOR);
            if ($useNameStyle) {
                $grid->column('product_names', '物料名称')->setHeaderAttributes(['class' => 'column-product_info'])->display(function () {
                    $productNames = ProductModel::withTrashed()
                        ->whereIn('id', ProductSkuModel::withTrashed()
                            ->whereIn('id', PurchaseOutItemModel::query()
                                ->where('order_id', $this->id)
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
                    ->expand(PurchaseOutOrderItemDetail::class);
            }
            $grid->column('supplier.name', '供应商名称')->setHeaderAttributes(['class' => 'column-supplier'])->emp();
            $grid->column('user.username', '创建用户')->setHeaderAttributes(['class' => 'column-user']);
            $grid->column('created_at')->setHeaderAttributes(['class' => 'column-created_at']);
            $grid->column('apply_at', '审核时间')->setHeaderAttributes(['class' => 'column-apply_at'])->emp();
            $grid->column('other')->setHeaderAttributes(['class' => 'column-other'])->emp();
            $grid->disableQuickEditButton();
            $grid->disableCreateButton();
            $grid->actions(EditOrder::make());
            $grid->tools(function ($tools) use ($columnConfig) {
                $tools->append(BatchOrderPrint::make());
                $tools->append(BatchCreatePurOutOrder::make());
                $tools->append(new ColumnSelector($columnConfig));
            });

            $grid->filter(function (Grid\Filter $filter) {
                $filter->where('product_keyword', function (Builder $query) {
                    $keyword = $this->getValue();
                    $query->whereHasIn('items', function (Builder $query) use ($keyword) {
                        $query->whereHasIn('sku.product', function (Builder $query) use ($keyword) {
                            $query->where(function (Builder $query) use ($keyword) {
                                $query->orWhere('name', 'like', '%' . $keyword . '%');
                                $query->orWhere('py_code', 'like', '%' . $keyword . '%');
                                $query->orWhere('item_no', 'like', '%' . $keyword . '%');
                            });
                        });
                    });
                }, '物料信息')->placeholder('物料名称，拼音码，编号')->width(3);
                $filter->equal('review_status', '审核状态')->select($this->oredr_model::REVIEW_STATUS)->width(3);
            });
        });
    }

    /**
     * @param Form $form
     */
    protected function setForm(Form &$form): void
    {
        $form->row(function (Form\Row $row) {
            $row->width(6)->text('order_no', '单号')->default('提交后自动生成')->required()->readOnly();
            $row->width(6)->datetime('created_at', '业务日期')->default(now())->required();
        });
        $with_order = $this->order_repository->getWithOrder();
        $form->row(function (Form\Row $row) use ($with_order) {
            $order = $this->order;
            $review_statu_ok = $this->oredr_model::REVIEW_STATUS_OK;
            if ($order && $order->review_status === $review_statu_ok) {
                $row->width(6)->select('with_id', '相关单据')->options(PurchaseInOrderModel::query()->pluck('order_no', 'id'))->disable();
            } else {
                $row->width(6)->select('with_id', '相关入库单据')->options($with_order)->default(0)->required()->with_order();
            }
            $supplier = SupplierRepository::pluck();
            $row->width(6)->select('supplier_id', '供应商')->options($supplier)->default(head($supplier->keys()->toArray()))->required();
        });
        $form->row(function (Form\Row $row) {
            $row->width(6)->text('other', '备注')->saveAsString();
        });

        $form->saving(function (Form $form) {
            if ($form->isCreating()) {
                $form->order_no = build_order_no('CT');
            }
        });
    }

    /**
     * @param Grid $grid
     */
    protected function setItems(Grid &$grid): void
    {
        $order = $this->order;
        $review_statu_ok = $this->oredr_model::REVIEW_STATUS_OK;
        $grid->column('sku.product.name', '物料名称');
        $grid->column('sku.product.unit.name', '单位');
        $grid->column('sku.product.type_str', '分类');
        $grid->column('sku.product.brand.name', '品牌');

        $grid->column('sku_id', '属性')->display(function () {
            return $this->sku['attr_value_ids_str'] ?? '';
        });

        $grid->column('standard', '通用标准')->display(function () {
            return PurchaseOutOrderModel::STANDARD[$this->standard] ?? '';
        });

        $grid->column('position_id', '出库位置')->display(function ($val) {
            return PositionModel::whereId($val)->value('name') ?? '-';
        });

        $grid->column('should_num', '入库数量');
        $grid->column('actual_num', '退货数')->if(function () use ($order, $review_statu_ok) {
            return $order->review_status !== $review_statu_ok;
        })->edit();
        $grid->column('price', '退货价')->if(function () use ($order, $review_statu_ok) {
            return $order->review_status !== $review_statu_ok;
        })->edit();
        $grid->column('_', '合计')->display(function () {
            return bcmul($this->actual_num, $this->price, 2);
        });

        $grid->column('batch_no', '批次号')->display(function ($val) {
            return $val ?: '-';
        });
    }

    protected function creating(Form &$form): void
    {
    }

    public function setItemsCommon(Grid &$grid): void
    {
        parent::setItemsCommon($grid);

        if ($this->order
            && $this->order->review_status === $this->oredr_model::REVIEW_STATUS_OK
            && $this->hasUnreviewPermission()
        ) {
            $grid->tools(PurchaseOutOrderUnreview::make());
        }
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
