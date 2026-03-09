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

use App\Admin\Actions\Grid\BatchCreateSaleOutOrderSave;
use App\Admin\Actions\Grid\BatchOrderPrint;
use App\Admin\Actions\Grid\EditOrder;
use App\Admin\Actions\Grid\SaleOrderUnreview;
use App\Admin\Extensions\Form\Order\OrderController;
use App\Admin\Extensions\Grid\ColumnSelector;
use App\Admin\Extensions\Grid\SaleOrderItemDetail;
use App\Admin\Repositories\SaleOrder;
use App\Models\CustomerModel;
use App\Models\PersonalConfigModel;
use App\Models\ProductModel;
use App\Models\ProductSkuModel;
use App\Models\PurchaseOrderModel;
use App\Models\SaleItemModel;
use App\Models\SaleOrderModel;
use Dcat\Admin\Admin;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Fluent;

class SaleOrderController extends OrderController
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
        return Grid::make(new SaleOrder(['customer', 'user']), function (Grid $grid) {
            $useNameStyle = $this->useMaterialNameStyle();
            
            // 定义列配置（用于列选择器）
            $columnConfig = [
                ['name' => 'id', 'label' => 'ID'],
                ['name' => 'customer', 'label' => '客户名称'],
                ['name' => 'order_no', 'label' => '单号'],
                ['name' => 'user', 'label' => '创建用户'],
                ['name' => 'product_info', 'label' => '物料信息'],
                ['name' => 'status', 'label' => '单据状态'],
                ['name' => 'review_status', 'label' => '审核状态'],
                ['name' => 'created_at', 'label' => '创建时间'],
                ['name' => 'other', 'label' => '备注'],
            ];
            
            $grid->column('id')->setHeaderAttributes(['class' => 'column-id'])->sortable();
            $grid->column('customer.name', '客户名称')->setHeaderAttributes(['class' => 'column-customer']);

            $grid->column('order_no')->setHeaderAttributes(['class' => 'column-order_no']);
            $grid->column('user.name', '创建用户')->setHeaderAttributes(['class' => 'column-user']);
            if ($useNameStyle) {
                $grid->column('product_names', '物料名称')->setHeaderAttributes(['class' => 'column-product_info'])->display(function () {
                    $productNames = ProductModel::withTrashed()
                        ->whereIn('id', ProductSkuModel::withTrashed()
                            ->whereIn('id', SaleItemModel::query()
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
                    ->expand(SaleOrderItemDetail::class);
            }
            $grid->column('status', '单据状态')->setHeaderAttributes(['class' => 'column-status'])->using($this->oredr_model::STATUS)->label($this->oredr_model::STATUS_COLOR);
            $grid->column('review_status', '审核状态')->setHeaderAttributes(['class' => 'column-review_status'])->using($this->oredr_model::REVIEW_STATUS)->label($this->oredr_model::REVIEW_STATUS_COLOR);
            $grid->column('created_at')->setHeaderAttributes(['class' => 'column-created_at']);
//            $grid->column('finished_at', "完成日期")->emp();
            $grid->column('other', '备注')->setHeaderAttributes(['class' => 'column-other'])->emp();

            $grid->disableQuickEditButton();
            $grid->tools(function ($tools) use ($columnConfig) {
                $tools->append(BatchOrderPrint::make());
                $tools->append(new ColumnSelector($columnConfig));
            });
            $grid->actions(EditOrder::make());

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
        return Grid::make(new SaleOrder(['customer', 'user']), function (Grid $grid) {
            $useNameStyle = $this->useMaterialNameStyle();
            $grid->model()->where([
                'status'        => SaleOrderModel::STATUS_DOING,
                'review_status' => SaleOrderModel::REVIEW_STATUS_OK
            ])->orderBy('id', 'desc');

            $grid->column('id')->sortable();
            $grid->column('customer.name', '客户名称');

            $grid->column('order_no');
            $grid->column('user.name', '创建用户');
            if ($useNameStyle) {
                $grid->column('product_names', '物料名称')->display(function () {
                    $productNames = ProductModel::withTrashed()
                        ->whereIn('id', ProductSkuModel::withTrashed()
                            ->whereIn('id', SaleItemModel::query()
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
                    ->expand(SaleOrderItemDetail::class);
            }
            $grid->column('status', '单据状态')->using($this->oredr_model::STATUS)->label($this->oredr_model::STATUS_COLOR);
            $grid->column('review_status', '审核状态')->using($this->oredr_model::REVIEW_STATUS)->label($this->oredr_model::REVIEW_STATUS_COLOR);
            $grid->column('created_at');
            $grid->column('finished_at')->emp();
            $grid->column('other', '备注')->emp();
            $grid->tools(BatchCreateSaleOutOrderSave::make());

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

    /**
     * @param Grid $grid
     */
    protected function setItems(Grid &$grid): void
    {
        $order = $this->order;
        $grid->column('sku.product.name', '物料名称');
        $grid->column('sku.product.unit.name', '单位');
        $grid->column('sku.product.type_str', '分类');
        $grid->column('sku.product.brand.name', '品牌');
        $grid->column('sku_id', '属性')->if(function () use ($order) {
            return $order->review_status === SaleOrderModel::REVIEW_STATUS_OK;
        })->display(function () {
            return $this->sku['attr_value_ids_str'] ?? '';
        })->else()->selectplus(function (Fluent $fluent) {
            return $fluent->sku['product']['sku_key_value'] ?? [];
        });

//        $grid->column('percent', '含绒百分比')->if(function () use ($order) {
//            return $order->review_status !== SaleOrderModel::REVIEW_STATUS_OK;
//        })->edit();

        $grid->column('standard', '通用标准')->if(function () use ($order) {
            return $order->review_status === SaleOrderModel::REVIEW_STATUS_OK;
        })->display(function () {
            return PurchaseOrderModel::STANDARD[$this->standard];
        })->else()->select(SaleOrderModel::STANDARD);

        $grid->column('should_num', '需数')->if(function () use ($order) {
            return $order->review_status !== SaleOrderModel::REVIEW_STATUS_OK;
        })->edit();
        $grid->column('price', '需价')->if(function () use ($order) {
            return $order->review_status !== SaleOrderModel::REVIEW_STATUS_OK;
        })->edit();
        $grid->column("_", '合计')->display(function () {
            return bcmul($this->should_num, $this->price, 2);
        });
    }

    public function setItemsCommon(Grid &$grid): void
    {
        parent::setItemsCommon($grid);

        if ($this->order
            && $this->order->review_status === $this->oredr_model::REVIEW_STATUS_OK
            && $this->hasUnreviewPermission()
        ) {
            $grid->tools(SaleOrderUnreview::make());
        }
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

        $form->row(function (Form\Row $row) {
            $order = $this->order;
            $review_statu_ok = $this->oredr_model::REVIEW_STATUS_OK;
            if ($order && $order->review_status === $review_statu_ok) {
                $row->width(6)->select('status', '单据状态')->options(SaleOrderModel::STATUS)->default($this->oredr_model::STATUS_DOING)->required();
            } else {
                $row->width(6)->select('status', '单据状态')->options([$this->oredr_model::STATUS_DOING => '受理中'])->default($this->oredr_model::STATUS_DOING)->required();
            }
            $row->width(6)->text('other', '备注')->saveAsString();
        });
        $customer = $form->repository()->customer();
        $form->row(function (Form\Row $row) use ($customer) {
            $row->width(6)->select('customer_id', '客户')->options($customer)->loads(
                ['address_id', 'drawee_id'],
                [route('api.customer.address.find'), route('api.customer.drawee.find')]
            )->required();
            $row->width(6)->select('address_id', '客户地址')->required();
        });

        $form->row(function (Form\Row $row) {
            $row->width(6)->select('drawee_id', '付款信息')->required();
        });

        $form->saving(function (Form $form) {
            if ($form->isCreating()) {
                $form->order_no = build_order_no('YH');
            }
        });
    }

    /**
     * @param Form $form
     */
    protected function creating(Form &$form): void
    {
        $form->width(12)->row(function (Form\Row $row) {
            $row->hasMany('items', '', function (Form\NestedForm $table) {
                $table->select('product_id', '物料名称')
                    ->ajax(route('api.product.search'))
                    ->config('ajax.delay', 100)
                    ->options(function ($id) {
                        if ($id) {
                            return ProductModel::where('id', $id)->pluck('name', 'id');
                        }
                        return ProductModel::orderBy('id', 'desc')->limit(10)->pluck('name', 'id');
                    })
                    ->loadpku(route('api.product.find'))
                    ->required();
                $table->ipt('unit', '单位')->rem(3)->default('-')->disable();
                $table->select('sku_id', '属性选择')->options()->required();
//                $table->tableDecimal('percent', '含绒百分比')->default(0);
                $table->select('standard', '通用标准')->options(PurchaseOrderModel::STANDARD)->default(0);
                $table->num('should_num', '需数')->required();
                $table->tableDecimal('price', '需价')->default(0.00)->required();
            })->useTable()->width(12)->enableHorizontal();
        });
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
