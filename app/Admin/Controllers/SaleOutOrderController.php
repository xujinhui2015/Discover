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

use App\Admin\Actions\Grid\BatchCreateSaleInOrderSave;
use App\Admin\Actions\Grid\BatchCreateSaleOutOrder;
use App\Admin\Actions\Grid\BatchOrderPrint;
use App\Admin\Actions\Grid\EditOrder;
use App\Admin\Actions\Grid\SaleOutOrderUnreview;
use App\Admin\Extensions\Form\Order\OrderController;
use App\Admin\Extensions\Grid\BatchDeail;
use App\Admin\Extensions\Grid\SaleOutOrderItemDetail;
use App\Admin\Repositories\SaleOutOrder;
use App\Models\CustomerModel;
use App\Models\PersonalConfigModel;
use App\Models\PurchaseOrderModel;
use App\Models\ProductModel;
use App\Models\ProductSkuModel;
use App\Models\SaleOrderModel;
use App\Models\SaleOutItemModel;
use App\Models\SaleOutOrderModel;
use Dcat\Admin\Admin;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Fluent;

class SaleOutOrderController extends OrderController
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
        return Grid::make(new SaleOutOrder(['customer', 'user']), function (Grid $grid) {
            $useNameStyle = $this->useMaterialNameStyle();
            $grid->column('id')->sortable();
            $grid->column('customer.name', '客户');

            $grid->column('order_no');
            $grid->column('user.name', '创建用户');
            if ($useNameStyle) {
                $grid->column('product_names', '物料名称')->display(function () {
                    $productNames = ProductModel::query()
                        ->whereIn('id', ProductSkuModel::query()
                            ->whereIn('id', SaleOutItemModel::query()
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
                    ->expand(SaleOutOrderItemDetail::class);
            }
            $grid->column('status', "单据状态")->using($this->oredr_model::STATUS)->label($this->oredr_model::STATUS_COLOR);
            $grid->column('review_status', '审核状态')->using($this->oredr_model::REVIEW_STATUS)->label($this->oredr_model::REVIEW_STATUS_COLOR);
            $grid->column('created_at');
            $grid->column('apply_at', "审核时间")->emp();
            $grid->column('other', '备注')->emp();
            $grid->disableQuickEditButton();
            $grid->disableCreateButton();
            $grid->actions(EditOrder::make());
            $grid->tools(BatchOrderPrint::make());
            $grid->tools(BatchCreateSaleOutOrder::make());
//            $grid->batchActions(BatchCreateSaleInOrderSave::make());

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('customer_id', '客户名称')
                    ->select(CustomerModel::query()->latest()->pluck('name', 'id'))
                    ->width(3);
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
            });
        });
    }

    public function iFrameGrid()
    {
        return Grid::make(new SaleOutOrder(['customer', 'user']), function (Grid $grid) {
            $useNameStyle = $this->useMaterialNameStyle();
            $grid->model()->where([
                'review_status' => SaleOutOrderModel::REVIEW_STATUS_OK
            ])->orderBy('id', 'desc');

            $grid->column('id')->sortable();
            $grid->column('customer.name', '客户');
            $grid->column('order_no');
            $grid->column('user.name', '创建用户');
            if ($useNameStyle) {
                $grid->column('product_names', '物料名称')->display(function () {
                    $productNames = ProductModel::query()
                        ->whereIn('id', ProductSkuModel::query()
                            ->whereIn('id', SaleOutItemModel::query()
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
                    ->expand(SaleOutOrderItemDetail::class);
            }
            $grid->column('status', '状态')->using($this->oredr_model::STATUS)->label($this->oredr_model::STATUS_COLOR);
            $grid->column('review_status', '审核状态')->using($this->oredr_model::REVIEW_STATUS)->label($this->oredr_model::REVIEW_STATUS_COLOR);
            $grid->column('created_at');
            $grid->column('apply_at', "审核时间")->emp();
            $grid->column('other', '备注')->emp();
            $grid->tools(BatchCreateSaleInOrderSave::make());

            $grid->disableActions();
            $grid->disableCreateButton();

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('customer_id', '客户名称')
                    ->select(CustomerModel::query()->latest()->pluck('name', 'id'))
                    ->width(3);
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
            });
        });
    }

    protected function setForm(Form &$form): void
    {
        $form->row(function (Form\Row $row) {
            $row->width(6)->text('order_no', '单号')->default(build_order_no('CH'))->required()->readOnly();
            $row->width(6)->datetime('created_at', '业务日期')->default(now())->required();
        });
        $with_order = $this->order_repository->getWithOrder();
        $form->row(function (Form\Row $row) use ($with_order) {
            $order = $this->order;
            $review_statu_ok = $this->oredr_model::REVIEW_STATUS_OK;
            if ($order && $order->review_status === $review_statu_ok) {
                $row->width(6)->select('status', '单据状态')->options(SaleOutOrderModel::STATUS)->default($this->oredr_model::STATUS_SEND)->required();
                $row->width(6)->select('with_id', '相关单据')->options(SaleOrderModel::query()->pluck('order_no', 'id'))->disable();
            } else {
                $row->width(6)->select('status', '单据状态')->options([$this->oredr_model::STATUS_SEND => '已发送'])->default($this->oredr_model::STATUS_SEND)->required();
                $row->width(6)->select('with_id', '相关单据')->options($with_order)->default(0)->required()->with_order();
            }
        });
        $customer = $form->repository()->customer();
        $form->row(function (Form\Row $row) use ($customer) {
            $row->width(6)->select('customer_id', '客户')->options($customer)->loads(
                ['address_id', 'drawee_id'],
                [route('api.customer.address.find'), route('api.customer.drawee.find')]
            )->required();
            $row->width(6)->select('address_id', '地址')->required();
        });

        $form->row(function (Form\Row $row) {
            $row->width(6)->select('drawee_id', '付款人')->required();
            $row->width(6)->text('other', '备注')->saveAsString();
        });
    }

    protected function setItems(Grid &$grid): void
    {
        $order = $this->order;
        $grid->column('sku.product.name', '物料名称');
        $grid->column('sku.product.unit.name', '单位');
        $grid->column('sku.product.type_str', '分类');
        $grid->column('sku.product.brand.name', '品牌');
        $grid->column('sku_id', '属性')->if(function () use ($order) {
            return $order->review_status === SaleOutOrderModel::REVIEW_STATUS_OK;
        })->display(function () {
            return $this->sku['attr_value_ids_str'] ?? '';
        })->else()->selectplus(function (Fluent $fluent) {
            $options = data_get($fluent, 'sku.product.sku_key_value', []);

            return is_array($options) ? $options : [];
        });

//        $grid->column('percent', '含绒百分比')->if(function () use ($order) {
//            return $order->review_status !== SaleOutOrderModel::REVIEW_STATUS_OK;
//        })->edit();

        $grid->column('standard', '通用标准')->if(function () use ($order) {
            return $order->review_status === SaleOutOrderModel::REVIEW_STATUS_OK;
        })->display(function () {
            return PurchaseOrderModel::STANDARD[$this->standard];
        })->else()->selectplus(SaleOutOrderModel::STANDARD);
        $grid->column('sku_stock_num', "库存")->display(function ($val) {
            return $val;
        });

        $grid->column('should_num', '要货数量');
        $grid->column('actual_num', '销售数量');
        $grid->column('price', '销售价格')->if(function () use ($order) {
            return $order->review_status !== SaleOutOrderModel::REVIEW_STATUS_OK;
        })->edit();
        $grid->column("_", '合计')->display(function () {
            return bcmul($this->actual_num, $this->price, 2);
        });
        $grid->column('pcxq', '批次详情')->batch_detail(function (BatchDeail $batchDeail) {
            return route('sale-out-batchs.index', [
                Grid::IFRAME_QUERY_NAME => 1,
                'item_id'               => $batchDeail->row->id,
                'sku_id'                => $batchDeail->row->sku_id,
                'standard'              => $batchDeail->row->standard,
//                'percent'               => $batchDeail->row->percent,
            ]);
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
            $grid->tools(SaleOutOrderUnreview::make());
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
