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
use App\Admin\Actions\Grid\EditOrder;
use App\Admin\Actions\Grid\OrderPrint;
use App\Admin\Actions\Grid\OrderReview;
use App\Admin\Extensions\Form\Order\OrderController;
use App\Admin\Extensions\Grid\MakeProductOrderItemDetail;
use App\Admin\Repositories\MakeProductOrder;
use App\Models\MakeProductOrderModel;
use App\Models\MakeProductItemModel;
use App\Models\PersonalConfigModel;
use App\Models\PositionModel;
use App\Models\ProductModel;
use App\Models\ProductSkuModel;
use App\Models\TaskModel;
use Dcat\Admin\Admin;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Models\Administrator;
use Illuminate\Database\Eloquent\Builder;

class MakeProductOrderController extends OrderController
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
        return Grid::make(new MakeProductOrder(['with_order', 'user']), function (Grid $grid) {
            $useNameStyle = $this->useMaterialNameStyle();
            $grid->column('id')->sortable();
            $grid->column('with_order.order_no', '任务单号')->emp();
            $grid->column('order_no');
            $grid->column('user.username', '创建用户');
            if ($useNameStyle) {
                $grid->column('product_names', '物料名称')->display(function () {
                    $productNames = ProductModel::query()
                        ->whereIn('id', ProductSkuModel::query()
                            ->whereIn('id', MakeProductItemModel::query()
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
                $grid->column('_', '物料明细')->expand(MakeProductOrderItemDetail::class);
            }
            $grid->column('other')->emp();
            $grid->column('review_status', '审核状态')->using($this->oredr_model::REVIEW_STATUS)->label($this->oredr_model::REVIEW_STATUS_COLOR);
            $grid->column('created_at');
            $grid->disableQuickEditButton();
            $grid->tools(BatchOrderPrint::make());
            $grid->actions(EditOrder::make());
            $grid->disableCreateButton();

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

    /**
     * @param Form $form
     */
    protected function setForm(Form &$form): void
    {
        $form->row(function (Form\Row $row) {
            $row->width(6)->text('order_no', '单号')->default(build_order_no('SCRK'))->required()->readOnly();
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
    }

    /**
     * @param Form $form
     */
    protected function creating(Form &$form): void
    {
        $form->width(12)->row(function (Form\Row $row) {
            $row->hasMany('items', '', function (Form\NestedForm $table) {
                $table->select('product_id', '物料名称')->options(ProductModel::pluck('name', 'id'))->loadpku(route('api.product.find'))->required();
                $table->ipt('unit', '单位')->rem(3)->default('-')->disable();
                $table->select('sku_id', '属性选择')->options()->required();
//                $table->tableDecimal('percent', '含绒量')->default(0);
                $table->select('standard', '通用标准')->options(MakeProductOrderModel::STANDARD)->default(0);
                $table->num('should_num', '计划入库数')->required();
                $table->tableDecimal('price', '实际入库数')->default(0.00)->required();
                $table->select('position_id', '入库位置')->options(PositionModel::orderBy('id', 'desc')->pluck('name', 'id'));
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

        $grid->column('sku.product.name', '物料名称');
        $grid->column('sku.product.unit.name', '单位');
        $grid->column('sku.product.type_str', '分类');
        $grid->column('sku.product.brand.name', '品牌');


        $grid->column('sku_id', '属性')->display(function () {
            return $this->sku['attr_value_ids_str'] ?? '';
        });

//        $grid->column('percent', '含绒量');
        $grid->column('standard', '通用标准')->display(function () {
            return  MakeProductOrderModel::STANDARD[$this->standard];
        });

        $grid->column('cost_price', '成本价格');
        $grid->column('should_num', '计划入库数');
        $grid->column('actual_num', '实际入库数')->if(function () use ($order) {
            return $order->review_status !== MakeProductOrderModel::REVIEW_STATUS_OK && $order->with_order->status === TaskModel::STATUS_DRAW;
        })->edit();

        $grid->column('position_id', '入库位置')->if(function () use ($order) {
            return $order->review_status !== MakeProductOrderModel::REVIEW_STATUS_OK && $order->with_order->status === TaskModel::STATUS_DRAW;
        })->selectplus(function () {
            return PositionModel::orderBy('id', 'desc')->pluck('name', 'id');
        })->else()->display(function ($val) {
            return PositionModel::whereId($val)->value('name') ?? '-';
        });
        $grid->column('batch_no', '批次号')->if(function () use ($order) {
            return $order->review_status !== MakeProductOrderModel::REVIEW_STATUS_OK && $order->with_order->status === TaskModel::STATUS_DRAW;
        })->edit();
    }

    public function setItemsCommon(Grid &$grid): void
    {
        $grid->tools(OrderPrint::make());
        if ($this->order && $this->order->with_order->status === TaskModel::STATUS_DRAW && $this->shouldShowReviewTool()) {
            $grid->tools(OrderReview::make(show_order_review($this->order->review_status)));
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
