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

use App\Admin\Actions\Grid\BatchCreateApplyForOrder;
use App\Admin\Actions\Grid\EditOrder;
use App\Admin\Actions\Grid\ApplyForReturnOrderUnreview;
use App\Admin\Extensions\Form\Order\OrderController;
use App\Admin\Extensions\Grid\ApplyForReturnOrderItemDetail;
use App\Admin\Repositories\ApplyForReturnOrder;
use App\Models\ApplyForOrderModel;
use App\Models\ApplyForReturnItemModel;
use App\Models\ApplyForReturnOrderModel;
use App\Models\PersonalConfigModel;
use App\Models\ProductModel;
use App\Models\ProductSkuModel;
use Dcat\Admin\Admin;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Models\Administrator;
use Illuminate\Database\Eloquent\Builder;

class ApplyForReturnOrderController extends OrderController
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
        return Grid::make(new ApplyForReturnOrder(['apply_for_order', 'user']), function (Grid $grid) {
            $useNameStyle = $this->useMaterialNameStyle();
            $grid->column('id')->sortable();
            $grid->column('apply_for_order.order_no', '物料单号')->emp();
            $grid->column('order_no');
            $grid->column('user.username', '创建用户');
            if ($useNameStyle) {
                $grid->column('product_names', '物料名称')->display(function () {
                    $productNames = ProductModel::query()
                        ->whereIn('id', ProductSkuModel::query()
                            ->whereIn('id', ApplyForReturnItemModel::query()
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
                    ->expand(ApplyForReturnOrderItemDetail::class);
            }
            $grid->column('review_status', '审核状态')
                ->using($this->oredr_model::REVIEW_STATUS)
                ->label($this->oredr_model::REVIEW_STATUS_COLOR);
            $grid->column('created_at');
            $grid->column('other')->emp();
            $grid->disableQuickEditButton();
            $grid->disableCreateButton();

            // 选择单据入库
            $grid->tools(BatchCreateApplyForOrder::make());

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
                $filter->where('apply_for_order_order_no', function (Builder $builder) {
                    $builder->whereHasIn('apply_for_order', function (Builder $builder) {
                        $builder->where("order_no", "like", "%" . $this->getValue() . "%");
                    });
                }, '物料单号')->width(3);
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
            $row->width(6)->text('order_no', '单号')->default(build_order_no('SL'))->required()->readOnly();
            $row->width(6)->datetime('created_at', '业务日期')->default(now())->required();
        });
        $apply_for_order = $this->order_repository->getApplyForOrder();
        $form->row(function (Form\Row $row) use ($apply_for_order,$form) {
            $order = $this->order;
            $review_statu_ok = $this->oredr_model::REVIEW_STATUS_OK;
            if ($order && $order->review_status === $review_statu_ok) {
                $row->width(6)->select('apply_for_order_id', '相关单据')
                    ->options(ApplyForOrderModel::query()->pluck('order_no', 'id'))
                    ->disable();
            } else {
                if ($form->isCreating() && request()->get('apply_for_order_id')) {
                    $row->width(6)->select('', '相关单据')
                        ->options($apply_for_order)
                        ->default(request()->get('apply_for_order_id'))
                        ->disable();
                    $row->width(0)->hidden('apply_for_order_id')->value(request()->get('apply_for_order_id'));
                } else {
                    $row->width(6)->select('apply_for_order_id', '相关单据')->options($apply_for_order)->default(0)->required();
                }
            }
            $users = Administrator::query()->latest()->pluck('name', 'id');
            $row->width(6)->select('apply_id', '审批人')
                ->options($users)->default(head($users->keys()->toArray()));
        });
        $form->row(function (Form\Row $row) {
            $row->width(6)->text('other', '备注')->saveAsString();
        });
    }

    public function creating(Form &$form): void
    {
        $form->width(12)->row(function (Form\Row $row) {
            $row->hasMany('items', '', function (Form\NestedForm $table) {
                $table->select('product_id', '物料名称')->options(ProductModel::pluck('name', 'id'))->loadpku(route('api.product.find'))->required();
                $table->ipt('unit', '单位')->rem(3)->default('-')->disable();
                $table->select('sku_id', '属性选择')->options()->required();
                $table->num('should_num', '申领数量')->required();
            })->useTable()->width(12)->enableHorizontal();
        });
    }

    public function setItems(Grid &$grid): void
    {
        $grid->column('id')->sortable();
        $grid->column('sku.product.name', '物料名称');
        $grid->column('standard', '通用标准');
        $grid->column('sku.product.unit.name', '单位');
        $grid->column('sku.product.type_str', '分类');
        $grid->column('sku.product.brand.name', '品牌');
        $grid->column('sku_id', '属性')
            ->display(function () {
            return $this->sku['attr_value_ids_str'] ?? '';
        });

        $grid->column('should_num', '返仓数量')
            ->if(function () {
                return ApplyForReturnOrderModel::query()
                    ->where('id', $this->order_id)
                    ->value('review_status') == ApplyForReturnOrderModel::REVIEW_STATUS_WAIT;
            })
            ->edit();
    }

    public function setItemsCommon(Grid &$grid): void
    {
        parent::setItemsCommon($grid);

        if ($this->order
            && $this->order->review_status === $this->oredr_model::REVIEW_STATUS_OK
            && $this->hasUnreviewPermission()
        ) {
            $grid->tools(ApplyForReturnOrderUnreview::make());
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
