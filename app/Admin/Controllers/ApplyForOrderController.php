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

use App\Admin\Actions\Grid\AddApplyForOrder;
use App\Admin\Actions\Grid\ApplyForOrderUnreview;
use App\Admin\Actions\Grid\BatchCreateApplyForReturnOrderSave;
use App\Admin\Actions\Grid\BatchCreateProSave;
use App\Admin\Actions\Grid\BatchCreatePurInOrderSave;
use App\Admin\Actions\Grid\BatchOrderPrint;
use App\Admin\Actions\Grid\EditOrder;
use App\Admin\Extensions\Form\Order\OrderController;
use App\Admin\Extensions\Grid\ApplyForOrderItemDetail;
use App\Admin\Extensions\Grid\BatchDeail;
use App\Admin\Extensions\Grid\ColumnSelector;
use App\Admin\Repositories\ApplyForOrder;
use App\Admin\Repositories\ApplyForReturnOrder;
use App\Admin\Repositories\Product;
use App\Models\ApplyForItemModel;
use App\Models\ApplyForOrderModel;
use App\Models\ApplyForReturnOrderModel;
use App\Models\PersonalConfigModel;
use App\Models\ProductModel;
use App\Models\ProductSkuModel;
use App\Models\PurchaseOrderModel;
use App\Models\TaskModel;
use Dcat\Admin\Admin;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Models\Administrator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Fluent;

class ApplyForOrderController extends OrderController
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
        return Grid::make(new ApplyForOrder(['with_order', 'user']), function (Grid $grid) {
            $useNameStyle = $this->useMaterialNameStyle();
            
            // 定义列配置（用于列选择器）
            $columnConfig = [
                ['name' => 'id', 'label' => 'ID'],
                ['name' => 'with_order_no', 'label' => '任务单号'],
                ['name' => 'order_no', 'label' => '单号'],
                ['name' => 'user', 'label' => '创建用户'],
                ['name' => 'product_info', 'label' => '物料信息'],
                ['name' => 'review_status', 'label' => '审核状态'],
                ['name' => 'created_at', 'label' => '创建时间'],
            ];
            
            $grid->column('id')->setHeaderAttributes(['class' => 'column-id'])->sortable();
            $grid->column('with_order.order_no', '任务单号')->setHeaderAttributes(['class' => 'column-with_order_no'])->emp();
            $grid->column('order_no')->setHeaderAttributes(['class' => 'column-order_no']);
            $grid->column('user.username', '创建用户')->setHeaderAttributes(['class' => 'column-user']);
//            $grid->column('other')->emp();
            if ($useNameStyle) {
                $grid->column('product_names', '物料名称')->display(function () {
                    $productNames = ProductModel::query()
                        ->whereIn('id', ProductSkuModel::query()
                            ->whereIn('id', ApplyForItemModel::query()
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
                    ->expand(ApplyForOrderItemDetail::class);
            }
            $grid->column('review_status', '审核状态')->setHeaderAttributes(['class' => 'column-review_status'])->using($this->oredr_model::REVIEW_STATUS)->label($this->oredr_model::REVIEW_STATUS_COLOR);
            $grid->column('created_at')->setHeaderAttributes(['class' => 'column-created_at']);
            $grid->disableQuickEditButton();
            $grid->tools(function ($tools) use ($columnConfig) {
                $tools->append(BatchOrderPrint::make());
                $tools->append(new ColumnSelector($columnConfig));
            });
            $grid->actions(EditOrder::make());

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
                $filter->where('with_order_order_no', function (Builder $builder) {
                    $builder->whereHasIn('with_order', function (Builder $builder) {
                        $builder->where("order_no", "like", "%" . $this->getValue() . "%");
                    });
                }, '任务单号')->width(3);
                $filter->like('order_no')->width(3);
                $filter->equal('review_status', '审核状态')->select($this->oredr_model::REVIEW_STATUS)->width(3);
            });
        });
    }

    public function iFrameGrid()
    {
        return Grid::make(new ApplyForOrder(['with_order', 'user']), function (Grid $grid) {
            $useNameStyle = $this->useMaterialNameStyle();

            $grid->model()
                ->whereDoesntHave('apply_for_return_order', function (Builder $builder) {
                    $builder->where('review_status',ApplyForReturnOrderModel::REVIEW_STATUS_WAIT);
                })
                ->where('review_status', $this->oredr_model::REVIEW_STATUS_OK)
                ->orderByDesc('id');


            $grid->column('id')->sortable();
            $grid->column('with_order.order_no', '任务单号')->emp();
            $grid->column('order_no');
            $grid->column('user.username', '创建用户');
            $grid->column('other')->emp();
            if ($useNameStyle) {
                $grid->column('product_names', '物料名称')->display(function () {
                    $productNames = ProductModel::query()
                        ->whereIn('id', ProductSkuModel::query()
                            ->whereIn('id', ApplyForItemModel::query()
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
                    ->expand(ApplyForOrderItemDetail::class);
            }
            $grid->column('review_status', '审核状态')
                ->using($this->oredr_model::REVIEW_STATUS)
                ->label($this->oredr_model::REVIEW_STATUS_COLOR);
            $grid->column('created_at');
            $grid->disableQuickEditButton();
            $grid->disableActions();
            $grid->disableCreateButton();

            $grid->tools(BatchCreateApplyForReturnOrderSave::make());


            $grid->filter(function (Grid\Filter $filter) {
                $filter->expand(false);

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
                $filter->where('with_order_order_no', function (Builder $builder) {
                    $builder->whereHasIn('with_order', function (Builder $builder) {
                        $builder->where("order_no", "like", "%" . $this->getValue() . "%");
                    });
                }, '任务单号')->width(3);
                $filter->like('order_no')->width(3);
//                $filter->equal('review_status', '审核状态')
//                    ->select($this->oredr_model::REVIEW_STATUS)->width(3);
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
        $form->row(function (Form\Row $row) use ($with_order,$form) {
            $order = $this->order;
            $review_statu_ok = $this->oredr_model::REVIEW_STATUS_OK;
            if ($order && $order->review_status === $review_statu_ok) {
                $row->width(6)->select('with_id', '相关单据')->options(TaskModel::query()->pluck('order_no', 'id'))->disable();
            } else {
                if ($form->isCreating() && request()->get('with_id')) {
                    $row->width(6)->select('', '相关单据')->options($with_order)->default(request()->get('with_id'))->disable();
                    $row->width(0)->hidden('with_id')->value(request()->get('with_id'));
                } else {
                    $row->width(6)->select('with_id', '相关单据')->options($with_order)->default(0)->required();
                }
            }
            $users = Administrator::query()->latest()->pluck('name', 'id');
            $row->width(6)->select('apply_id', '审批人')->options($users)->default(head($users->keys()->toArray()))->required();
        });
        $form->row(function (Form\Row $row) {
            $row->width(6)->text('other', '备注')->saveAsString();
        });
        
        // 在保存时动态生成订单号
        $form->saving(function (Form $form) {
            // 仅在新建时生成订单号
            if ($form->isCreating()) {
                $form->order_no = build_order_no('SL');
            }
        });
    }

    public function creating(Form &$form): void
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
            })->useTable()->width(12)->enableHorizontal();
        });
    }

    public function setItems(Grid &$grid): void
    {
        $order = $this->order;
        $grid->column('sku.product.name', '物料名称');
        $grid->column('sku.product.unit.name', '单位');
        $grid->column('sku.product.type_str', '分类');
        $grid->column('sku.product.brand.name', '品牌');
        $grid->column('sku_id', '属性')->if(function () use ($order) {
            return $order->review_status === ApplyForOrderModel::REVIEW_STATUS_OK;
        })->display(function () {
            return $this->sku['attr_value_ids_str'] ?? '';
        })->else()->selectplus(function (Fluent $fluent) {
            return $fluent->sku['product']['sku_key_value'];
        });

//        $grid->column('percent', '含绒百分比')->if(function () use ($order) {
//            return $order->review_status !== ApplyForOrderModel::REVIEW_STATUS_OK;
//        })->edit();
        $grid->column('standard', '通用标准')->if(function () use ($order) {
            return $order->review_status === ApplyForOrderModel::REVIEW_STATUS_OK;
        })->display(function () {
            return ApplyForOrderModel::STANDARD[$this->standard];
        })->else()->selectplus(ApplyForOrderModel::STANDARD);
        $grid->column('cost_price', "成本总价");
        $grid->column('should_num', '需数');
        $grid->column('actual_num', '实领数量');
        $grid->column('sku_stock_num', "库存")->display(function ($val) {
            return $val;
        });
        $grid->column('pcxq', '批次详情')->batch_detail(function (BatchDeail $batchDeail) {
            return route('apply-for-batchs.index', [
                Grid::IFRAME_QUERY_NAME => 1,
                'item_id'               => $batchDeail->row->id,
                'sku_id'                => $batchDeail->row->sku_id,
                'standard'              => $batchDeail->row->standard,
//                'percent'               => $batchDeail->row->percent,
            ]);
        });
    }

    public function setItemsCommon(Grid &$grid): void
    {
        parent::setItemsCommon($grid);

        if ($this->order
            && $this->order->review_status === $this->oredr_model::REVIEW_STATUS_OK
            && $this->hasUnreviewPermission()
        ) {
            $grid->tools(ApplyForOrderUnreview::make());
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
