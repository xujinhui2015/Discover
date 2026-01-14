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

use App\Admin\Actions\Grid\BatchCreatePurInOrder;
use App\Admin\Actions\Grid\BatchCreatePurOutOrderSave;
use App\Admin\Actions\Grid\BatchOrderPrint;
use App\Admin\Actions\Grid\EditOrder;
use App\Admin\Actions\Grid\PurchaseInOrderUnreview;
use App\Admin\Extensions\Form\Order\OrderController;
use App\Admin\Extensions\Grid\PurchaseInOrderItemDetail;
use App\Admin\Repositories\PurchaseInOrder;
use App\Models\PersonalConfigModel;
use App\Models\PositionModel;
use App\Models\ProductModel;
use App\Models\ProductSkuModel;
use App\Models\PurchaseInItemModel;
use App\Models\PurchaseInOrderModel;
use App\Models\PurchaseOutOrderModel;
use App\Models\PurchaseOrderModel;
use App\Repositories\SupplierRepository;
use App\Models\SystemConfigModel;
use Dcat\Admin\Admin;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Fluent;

class PurchaseInOrderController extends OrderController
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
        return Grid::make(new PurchaseInOrder(['user', 'supplier', 'with_order']), function (Grid $grid) {
            $useNameStyle = $this->useMaterialNameStyle();
            $grid->column('id')->sortable();
            $grid->column('order_no');
            $grid->column('with_order.order_no', '关联单号')->emp();
            $grid->column('status', "单据状态")->using($this->oredr_model::STATUS)->label($this->oredr_model::STATUS_COLOR);
            $grid->column('review_status', '审核状态')->using($this->oredr_model::REVIEW_STATUS)->label($this->oredr_model::REVIEW_STATUS_COLOR);
            if ($useNameStyle) {
                $grid->column('product_names', '物料名称')->display(function () {
                    $productNames = ProductModel::query()
                        ->whereIn('id', ProductSkuModel::query()
                            ->whereIn('id', PurchaseInItemModel::query()
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
                    ->expand(PurchaseInOrderItemDetail::class);
            }
            $grid->column('supplier.name', '供应商名称')->emp();
            $grid->column('user.username', '创建用户');
            $grid->column('created_at');
            $grid->column('apply_at', "审核时间")->emp();
            $grid->column('other')->emp();
            $grid->disableQuickEditButton();
            $grid->disableCreateButton();
            $grid->actions(EditOrder::make());
            $grid->tools(BatchOrderPrint::make());
            $grid->tools(BatchCreatePurInOrder::make());

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

    public function iFrameGrid()
    {
        return Grid::make(new PurchaseInOrder(['user', 'supplier', 'with_order']), function (Grid $grid) {
            $useNameStyle = $this->useMaterialNameStyle();
            $grid->model()
                ->whereDoesntHave('purchase_out_orders', function (Builder $builder) {
                    $builder->where('review_status', PurchaseOutOrderModel::REVIEW_STATUS_WAIT);
                })
                ->where('review_status', PurchaseInOrderModel::REVIEW_STATUS_OK)
                ->orderBy('id', 'desc');

            $grid->column('id')->sortable();
            $grid->column('order_no');
            $grid->column('with_order.order_no', '关联单号')->emp();
            $grid->column('status', '单据状态')->using($this->oredr_model::STATUS)->label($this->oredr_model::STATUS_COLOR);
            $grid->column('review_status', '审核状态')->using($this->oredr_model::REVIEW_STATUS)->label($this->oredr_model::REVIEW_STATUS_COLOR);
            if ($useNameStyle) {
                $grid->column('product_names', '物料名称')->display(function () {
                    $productNames = ProductModel::query()
                        ->whereIn('id', ProductSkuModel::query()
                            ->whereIn('id', PurchaseInItemModel::query()
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
                    ->expand(PurchaseInOrderItemDetail::class);
            }
            $grid->column('supplier.name', '供应商名称')->emp();
            $grid->column('user.username', '创建用户');
            $grid->column('created_at');
            $grid->column('apply_at', '审核时间')->emp();
            $grid->column('other')->emp();
            $grid->disableQuickEditButton();
            $grid->disableActions();
            $grid->disableCreateButton();
            $grid->tools(BatchCreatePurOutOrderSave::make());

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
            $row->width(6)->text('order_no', '单号')->default(build_order_no('RK'))->required()->readOnly();
            $row->width(6)->datetime('created_at', '业务日期')->default(now())->required();
        });
        $with_order = $this->order_repository->getWithOrder();
        $form->row(function (Form\Row $row) use ($with_order) {
            $row->width(6)->select('status', '单据状态')->options([$this->oredr_model::STATUS_ARRIVE => '已收货'])->default($this->oredr_model::STATUS_ARRIVE)->required();
            $order = $this->order;
            $review_statu_ok = $this->oredr_model::REVIEW_STATUS_OK;
            if ($order && $order->review_status === $review_statu_ok) {
                $row->width(6)->select('with_id', '相关单据')->options(PurchaseOrderModel::query()->pluck('order_no', 'id'))->disable();
            } else {
                $row->width(6)->select('with_id', '相关采购单据')->options($with_order)->default(0)->required()->with_order();
            }
        });
        $form->row(function (Form\Row $row) {
            $supplier = SupplierRepository::pluck();
            $row->width(6)->select('supplier_id', '供应商')->options($supplier)->default(head($supplier->keys()->toArray()))->required();
            $row->width(6)->text('other', '备注')->saveAsString();
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
                $table->num('should_num', '采购数量')->required();
                $table->tableDecimal('price', '采购价格')->default(0.00)->required();
                $table->select('position_id', '入库位置')
                    ->options(PositionModel::orderBy('id', 'desc')->pluck('name', 'id'))
                    ->default(SystemConfigModel::getValue(SystemConfigModel::KEY_DEFAULT_PURCHASE_IN_POSITION));
                $table->ipt('batch_no', '批次号')->rem(8)->default("PC".date('Ymd'))->required();
            })->useTable()->width(12)->enableHorizontal();
        });
    }

    /**
     * @param Grid $grid
     */
    public function setItems(Grid &$grid): void
    {
        $order = $this->order;
        $review_statu_ok = $this->oredr_model::REVIEW_STATUS_OK;

        $grid->column('sku.product.name', '物料名称');
        $grid->column('sku.product.unit.name', '单位');
        $grid->column('sku.product.type_str', '分类');
        $grid->column('sku.product.brand.name', '品牌');

        $grid->column('sku_id', '属性')->if(function () use ($order,$review_statu_ok) {
            return $order->review_status === $review_statu_ok;
        })->display(function () {
            return $this->sku['attr_value_ids_str'] ?? '';
        })->else()->selectplus(function (Fluent $fluent) {
            return data_get($fluent, 'sku.product.sku_key_value', []);
        });


//        $grid->column('percent', '含绒百分比')->if(function () use ($order, $review_statu_ok) {
//            return $order->review_status !== $review_statu_ok;
//        })->edit();
        $grid->column('standard', '通用标准')->if(function () use ($order) {
            return $order->review_status === PurchaseOrderModel::REVIEW_STATUS_OK;
        })->display(function () {
            return PurchaseOrderModel::STANDARD[$this->standard];
        })->else()->selectplus(PurchaseOrderModel::STANDARD);

        $grid->column('position_id', '入库位置')->if(function () use ($order,$review_statu_ok) {
            return $order->review_status === $review_statu_ok;
        })->display(function ($val) {
            return PositionModel::whereId($val)->value('name') ?? '-';
        })->else()->selectplus(function () {
            return PositionModel::orderBy('id', 'desc')->pluck('name', 'id');
        });

        $grid->column('should_num', '采购数量');
        $grid->column('actual_num', '入库数量')->if(function () use ($order,$review_statu_ok) {
            return $order->review_status !== $review_statu_ok;
        })->edit();
        $grid->column('price', '采购价格')->if(function () use ($order,$review_statu_ok) {
            return $order->review_status !== $review_statu_ok;
        })->edit();
        $grid->column("_", '合计')->display(function () {
            return bcmul($this->actual_num, $this->price, 2);
        });

        $grid->column('batch_no', '批次号')->if(function () use ($order,$review_statu_ok) {
            return $order->review_status !== $review_statu_ok;
        })->edit();
    }

    public function setItemsCommon(Grid &$grid): void
    {
        parent::setItemsCommon($grid);

        if ($this->order
            && $this->order->review_status === $this->oredr_model::REVIEW_STATUS_OK
            && $this->hasUnreviewPermission()
        ) {
            $grid->tools(PurchaseInOrderUnreview::make());
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
