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
use App\Admin\Extensions\Grid\ColumnSelector;
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
                ['name' => 'apply_at', 'label' => '审核时间'],
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
                    ->setHeaderAttributes(['class' => 'column-product_info'])
                    ->expand(SaleOutOrderItemDetail::class);
            }
            $grid->column('status', "单据状态")->setHeaderAttributes(['class' => 'column-status'])->using($this->oredr_model::STATUS)->label($this->oredr_model::STATUS_COLOR);
            $grid->column('review_status', '审核状态')->setHeaderAttributes(['class' => 'column-review_status'])->using($this->oredr_model::REVIEW_STATUS)->label($this->oredr_model::REVIEW_STATUS_COLOR);
            $grid->column('created_at')->setHeaderAttributes(['class' => 'column-created_at']);
            $grid->column('apply_at', "审核时间")->setHeaderAttributes(['class' => 'column-apply_at'])->emp();
            $grid->column('other', '备注')->setHeaderAttributes(['class' => 'column-other'])->emp();
            $grid->disableQuickEditButton();
            $grid->disableCreateButton();
            $grid->actions(EditOrder::make());
            
            $grid->tools(function ($tools) use ($columnConfig) {
                $tools->append(BatchOrderPrint::make());
                $tools->append(BatchCreateSaleOutOrder::make());
                $tools->append(new ColumnSelector($columnConfig));
            });
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

            $grid->export()->rows(function (array $rows) {
                $itemsByOrder = SaleOutItemModel::query()
                    ->with(['sku.product.unit', 'sku.product.brand'])
                    ->whereIn('order_id', array_column($rows, 'id'))
                    ->get()
                    ->groupBy('order_id');

                return collect($rows)->flatMap(function ($row) use ($itemsByOrder) {
                    $items = $itemsByOrder->get($row['id'], collect());

                    if ($items->isEmpty()) {
                        return [[
                            'ID' => $row['id'],
                            '客户名称' => data_get($row, 'customer.name', ''),
                            '单号' => $row['order_no'],
                            '创建用户' => data_get($row, 'user.name', ''),
                            '物料名称' => '',
                            '属性' => '',
                            '通用标准' => '',
                            '需数' => '',
                            '销数' => '',
                            '销价' => '',
                            '合计' => '',
                            '单据状态' => SaleOutOrderModel::STATUS[$row['status']] ?? '',
                            '审核状态' => SaleOutOrderModel::REVIEW_STATUS[$row['review_status']] ?? '',
                            '创建时间' => $row['created_at'],
                            '审核时间' => $row['apply_at'],
                            '备注' => $row['other'],
                        ]];
                    }

                    return $items->map(function (SaleOutItemModel $item) use ($row) {
                        $product = data_get($item, 'sku.product');

                        return [
                            'ID' => $row['id'],
                            '客户名称' => data_get($row, 'customer.name', ''),
                            '单号' => $row['order_no'],
                            '创建用户' => data_get($row, 'user.name', ''),
                            '物料名称' => data_get($product, 'name', ''),
                            '属性' => data_get($item, 'sku.attr_value_ids_str', ''),
                            '通用标准' => $item->standard_str,
                            '需数' => $item->should_num,
                            '销数' => $item->actual_num,
                            '销价' => $item->price,
                            '合计' => bcmul((string) $item->actual_num, (string) $item->price, 2),
                            '单据状态' => SaleOutOrderModel::STATUS[$row['status']] ?? '',
                            '审核状态' => SaleOutOrderModel::REVIEW_STATUS[$row['review_status']] ?? '',
                            '创建时间' => $row['created_at'],
                            '审核时间' => $row['apply_at'],
                            '备注' => $row['other'],
                        ];
                    })->all();
                })->values()->all();
            })->extension('xlsx');
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
            $grid->column('customer.name', '客户名称');
            $grid->column('order_no');
            $grid->column('user.name', '创建用户');
            if ($useNameStyle) {
                $grid->column('product_names', '物料名称')->display(function () {
                    $productNames = ProductModel::withTrashed()
                        ->whereIn('id', ProductSkuModel::withTrashed()
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
            $grid->column('status', '单据状态')->using($this->oredr_model::STATUS)->label($this->oredr_model::STATUS_COLOR);
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
            $row->width(6)->text('order_no', '单号')->default('提交后自动生成')->required()->readOnly();
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
            $row->width(6)->select('customer_id', '客户名称')->options($customer)->loads(
                ['address_id', 'drawee_id'],
                [route('api.customer.address.find'), route('api.customer.drawee.find')]
            )->required();
            $row->width(6)->select('address_id', '客户地址')->required();
        });

        $form->row(function (Form\Row $row) {
            $row->width(6)->select('drawee_id', '付款信息')->required();
            $row->width(6)->text('other', '备注')->saveAsString();
        });

        $form->saving(function (Form $form) {
            if ($form->isCreating()) {
                $form->order_no = build_order_no('CH');
            }
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

        $grid->column('should_num', '需数');
        $grid->column('actual_num', '销数');
        $grid->column('price', '销价')->if(function () use ($order) {
            return $order->review_status !== SaleOutOrderModel::REVIEW_STATUS_OK;
        })->edit(true);
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
