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

use App\Admin\Actions\Grid\BatchCreatePurInOrderSave;
use App\Admin\Actions\Grid\BatchOrderPrint;
use App\Admin\Actions\Grid\EditOrder;
use App\Admin\Actions\Grid\PurchaseOrderUnreview;
use App\Admin\Extensions\Form\Order\OrderController;
use App\Admin\Extensions\Grid\ColumnSelector;
use App\Admin\Extensions\Grid\PurchaseOrderItemDetail;
use App\Admin\Repositories\PurchaseOrder;
use App\Models\PersonalConfigModel;
use App\Models\ProductModel;
use App\Models\ProductSkuModel;
use App\Models\PurchaseItemModel;
use App\Models\PurchaseOrderModel;
use App\Repositories\SupplierRepository;
use Dcat\Admin\Admin;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Fluent;

class PurchaseOrderController extends OrderController
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
        return Grid::make(new PurchaseOrder(['user', 'supplier']), function (Grid $grid) {
            $useNameStyle = $this->useMaterialNameStyle();
            
            // 定义列配置（用于列选择器）
            $columnConfig = [
                ['name' => 'id', 'label' => 'ID'],
                ['name' => 'order_no', 'label' => '单号'],
                ['name' => 'status', 'label' => '单据状态'],
                ['name' => 'review_status', 'label' => '审核状态'],
                ['name' => 'product_info', 'label' => '物料信息'],
                ['name' => 'supplier', 'label' => '供应商名称'],
                ['name' => 'user', 'label' => '创建用户'],
                ['name' => 'created_at', 'label' => '创建时间'],
                ['name' => 'other', 'label' => '备注'],
            ];
            
            $grid->column('id')->setHeaderAttributes(['class' => 'column-id'])->sortable();
//            $grid->column('check_status')->using(PurchaseOrderModel::CHECK_STATUS);
            $grid->column('order_no')->setHeaderAttributes(['class' => 'column-order_no']);
            $grid->column('status', '单据状态')->setHeaderAttributes(['class' => 'column-status'])->using(PurchaseOrderModel::STATUS)->label(PurchaseOrderModel::STATUS_COLOR);
            $grid->column('review_status', '审核状态')->setHeaderAttributes(['class' => 'column-review_status'])->using(PurchaseOrderModel::REVIEW_STATUS)->label(PurchaseOrderModel::REVIEW_STATUS_COLOR);
            if ($useNameStyle) {
                $grid->column('product_names', '物料名称')->setHeaderAttributes(['class' => 'column-product_info'])->display(function () {
                    $productNames = ProductModel::withTrashed()
                        ->whereIn('id', ProductSkuModel::withTrashed()
                            ->whereIn('id', PurchaseItemModel::query()
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
                    ->expand(PurchaseOrderItemDetail::class);
            }
            $grid->column('supplier.name', '供应商名称')->setHeaderAttributes(['class' => 'column-supplier'])->emp();
            $grid->column('user.username', '创建用户')->setHeaderAttributes(['class' => 'column-user']);
            $grid->column('created_at')->setHeaderAttributes(['class' => 'column-created_at']);
            $grid->column('other')->setHeaderAttributes(['class' => 'column-other'])->emp();
            
            $grid->tools(function ($tools) use ($columnConfig) {
                $tools->append(BatchOrderPrint::make());
                $tools->append(new ColumnSelector($columnConfig));
            });
            
            $grid->disableQuickEditButton();
            $grid->actions(new EditOrder());
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
                $filter->equal('supplier_id', '供应商')->select(SupplierRepository::pluck())->width(3);
                $filter->in('status', '单据状态')->multipleSelect(PurchaseOrderModel::STATUS)->width(3);
                $filter->between('created_at', '业务日期')->datetime()->width(3);
            });

        });
    }

    public function iFrameGrid()
    {
        return Grid::make(new PurchaseOrder(['user', 'supplier']), function (Grid $grid) {
            $useNameStyle = $this->useMaterialNameStyle();
            $grid->model()
                ->whereIn('status', [
                    PurchaseOrderModel::STATUS_WAIT,
                    PurchaseOrderModel::STATUS_PART_RETURNED,
                ])
                ->where([
                    'review_status' => PurchaseOrderModel::REVIEW_STATUS_OK
                ])->orderBy('id', 'desc');

            $grid->column('id')->sortable();
//            $grid->column('check_status')->using(PurchaseOrderModel::CHECK_STATUS);

            $grid->column('order_no');
            $grid->column('other')->emp();
            $grid->column('status', '单据状态')->using(PurchaseOrderModel::STATUS)->label(PurchaseOrderModel::STATUS_COLOR);
            $grid->column('review_status', '审核状态')->using(PurchaseOrderModel::REVIEW_STATUS)->label(PurchaseOrderModel::REVIEW_STATUS_COLOR);
            if ($useNameStyle) {
                $grid->column('product_names', '物料名称')->display(function () {
                    $productNames = ProductModel::withTrashed()
                        ->whereIn('id', ProductSkuModel::withTrashed()
                            ->whereIn('id', PurchaseItemModel::query()
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
                    ->expand(PurchaseOrderItemDetail::class);
            }
            $grid->column('supplier.name', '供应商名称')->emp();
            $grid->column('user.username', '创建用户');
            $grid->column('created_at');
            $grid->disableQuickEditButton();
            $grid->disableActions();
            $grid->disableCreateButton();
            $grid->tools(BatchCreatePurInOrderSave::make());
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
                $filter->equal('supplier_id', '供应商')->select(SupplierRepository::pluck())->width(3);
                $filter->between('created_at', '业务日期')->datetime()->width(3);
            });
        });
    }

    protected function setForm(Form &$form): void
    {
        $form->row(function (Form\Row $row) {
            $row->width(6)->text('order_no', '单号')->default('提交后自动生成')->required()->readOnly();
            $row->width(6)->datetime('created_at', '业务日期')->default(now())->required();
        });
        $form->row(function (Form\Row $row) {
            $order = $this->order;
            //$row->width(6)->select('check_status', '检测状态')->options(PurchaseOrderModel::CHECK_STATUS)->default(0)->required();
            if ($order && $order->review_status === PurchaseOrderModel::REVIEW_STATUS_OK) {
                $row->width(6)->select('status', '单据状态')->options(PurchaseOrderModel::STATUS)->default($this->oredr_model::STATUS_WAIT)->required();
            } else {
                $row->width(6)->select('status', '单据状态')->options([PurchaseOrderModel::STATUS_WAIT => '待收货'])->default(PurchaseOrderModel::STATUS_WAIT)->required();
            }
            $supplier = SupplierRepository::pluck();
            $row->width(6)->select('supplier_id', '供应商')->options($supplier)->default(head($supplier->keys()->toArray()))->required();
        });
        $form->row(function (Form\Row $row) {
            $row->width(6)->text('other', '备注')->saveAsString();
        });

        $form->saving(function (Form $form) {
            if ($form->isCreating()) {
                $form->order_no = build_order_no('CG');
            }
        });
    }

    protected function creating(Form &$form): void
    {
        $form->row(function (Form\Row $row) {
            $row->hasMany('items', '', function (Form\NestedForm $table) {
                // 使用异步加载物料，默认显示最近10条
                $table->select('product_id', '物料名称')
                    ->ajax(route('api.product.search'))
                    ->config('ajax.delay', 100) // 添加 100ms 防抖延迟
                    ->options(function ($id) {
                        if ($id) {
                            return ProductModel::where('id', $id)->pluck('name', 'id');
                        }
                        // 默认显示最近10条物料
                        return ProductModel::orderBy('id', 'desc')->limit(10)->pluck('name', 'id');
                    })
                    ->loadpku(route('api.product.find'))
                    ->required();

                $table->ipt('unit', '单位')->rem(3)->default('-')->disable();
                $table->ipt('type', '分类')->rem(5)->default('-')->disable();
                $table->ipt('brand', '品牌')->rem(3)->default('-')->disable();
                $table->select('sku_id', '属性选择')->options()->required();
//                $table->tableDecimal('percent', '含绒量')->default(0);
                $table->select('standard', '通用标准')->options(PurchaseOrderModel::STANDARD)->default(0);
                $table->num('should_num', '采购数量')->required();
                $table->tableDecimal('price', '采购价格')->default(0.00)->required();
            })->useTable()->width(12)->enableHorizontal();
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
            return $order->review_status === PurchaseOrderModel::REVIEW_STATUS_OK;
        })->display(function () {
            return $this->sku['attr_value_ids_str'] ?? '';
        })->else()->selectplus(function (Fluent $fluent) {
            return $fluent->sku['product']['sku_key_value'];
        });
//        $grid->column('percent', '含绒量')->if(function () use ($order) {
//            return $order->review_status !== PurchaseOrderModel::REVIEW_STATUS_OK;
//        })->edit();

        $grid->column('standard', '通用标准')->if(function () use ($order) {
            return $order->review_status === PurchaseOrderModel::REVIEW_STATUS_OK;
        })->display(function () {
            return PurchaseOrderModel::STANDARD[$this->standard];
        })->else()->select(PurchaseOrderModel::STANDARD);

        $grid->column('should_num', '采购数量')->if(function () use ($order) {
            return $order->review_status !== PurchaseOrderModel::REVIEW_STATUS_OK;
        })->edit();
        $grid->column('price', '采购价格')->if(function () use ($order) {
            return $order->review_status !== PurchaseOrderModel::REVIEW_STATUS_OK;
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
            $grid->tools(PurchaseOrderUnreview::make());
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
