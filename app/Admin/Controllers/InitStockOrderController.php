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
use App\Admin\Extensions\Form\Order\OrderController;
use App\Admin\Extensions\Grid\ColumnSelector;
use App\Admin\Extensions\Grid\InitStockOrderItemDetail;
use App\Admin\Repositories\InitStockOrder;
use App\Models\InitStockItemModel;
use App\Models\InitStockOrderModel;
use App\Models\PersonalConfigModel;
use App\Models\PositionModel;
use App\Models\ProductModel;
use App\Models\ProductSkuModel;
use Dcat\Admin\Admin;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Models\Administrator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Fluent;

class InitStockOrderController extends OrderController
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
        return Grid::make(new InitStockOrder(['user']), function (Grid $grid) {
            $useNameStyle = $this->useMaterialNameStyle();
            
            // 定义列配置（用于列选择器）
            $columnConfig = [
                ['name' => 'id', 'label' => 'ID'],
                ['name' => 'order_no', 'label' => '单号'],
                ['name' => 'user', 'label' => '创建用户'],
                ['name' => 'product_info', 'label' => '物料信息'],
                ['name' => 'other', 'label' => '备注'],
                ['name' => 'review_status', 'label' => '审核状态'],
                ['name' => 'created_at', 'label' => '创建时间'],
            ];
            
            $grid->column('id')->setHeaderAttributes(['class' => 'column-id'])->sortable();
            $grid->column('order_no')->setHeaderAttributes(['class' => 'column-order_no']);
            $grid->column('user.username', '创建用户')->setHeaderAttributes(['class' => 'column-user']);
            if ($useNameStyle) {
                $grid->column('product_names', '物料名称')->setHeaderAttributes(['class' => 'column-product_info'])->display(function () {
                    $productNames = ProductModel::query()
                        ->whereIn('id', ProductSkuModel::query()
                            ->whereIn('id', InitStockItemModel::query()
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
                    ->expand(InitStockOrderItemDetail::class);
            }
            $grid->column('other')->setHeaderAttributes(['class' => 'column-other'])->emp();
            $grid->column('review_status', '审核状态')->setHeaderAttributes(['class' => 'column-review_status'])->using($this->oredr_model::REVIEW_STATUS)->label($this->oredr_model::REVIEW_STATUS_COLOR);
            $grid->column('created_at')->setHeaderAttributes(['class' => 'column-created_at']);
            $grid->tools(function ($tools) use ($columnConfig) {
                $tools->append(BatchOrderPrint::make());
                $tools->append(new ColumnSelector($columnConfig));
            });
            $grid->disableQuickEditButton();
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
            });
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function setForm(Form &$form): void
    {
        $form->row(function (Form\Row $row) {
            $row->width(6)->text('order_no', '单号')->default(build_order_no('QC'))->required()->readOnly();
            $row->width(6)->datetime('created_at', '业务日期')->default(now())->required();
        });
        $form->row(function (Form\Row $row) use ($form) {
            $users = Administrator::query()->latest()->pluck('name', 'id');
            $row->width(6)->select('apply_id', '审批人')->options($users)->default(head($users->keys()->toArray()))->required();
            $row->width(6)->text('other', '备注')->saveAsString();
        });
    }

    protected function creating(Form &$form): void
    {
        $form->row(function (Form\Row $row) {
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
                $table->ipt('type', '分类')->rem(5)->default('-')->disable();
                $table->ipt('brand', '品牌')->rem(3)->default('-')->disable();
                $table->select('sku_id', '属性选择')->options()->required();
//                $table->tableDecimal('percent', '含绒百分比')->default(0);
                $table->select('standard', '通用标准')->options(InitStockOrderModel::STANDARD)->default(0);
                $table->tableDecimal('actual_num', '期初库存')->default(0.00)->required();
                $table->tableDecimal('cost_price', '成本单价')->default(0.00)->required();
                $table->select('position_id', '入库位置')->options(PositionModel::orderBy('id', 'desc')->pluck('name', 'id'))->required();
                $table->ipt('batch_no', '批次号')->rem(8)->default("PC".date('Ymd'))->required();
            })->useTable()->width(12)->enableHorizontal();
        });
    }

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
            return $fluent->sku['product']['sku_key_value'];
        });

//        $grid->column('percent', '含绒百分比')->if(function () use ($order, $review_statu_ok) {
//            return $order->review_status !== $review_statu_ok;
//        })->edit();
        $grid->column('standard', '通用标准')->if(function () use ($order) {
            return $order->review_status === InitStockOrderModel::REVIEW_STATUS_OK;
        })->display(function () {
            return InitStockOrderModel::STANDARD[$this->standard];
        })->else()->selectplus(InitStockOrderModel::STANDARD);

        $grid->column('position_id', '入库位置')->if(function () use ($order,$review_statu_ok) {
            return $order->review_status === $review_statu_ok;
        })->display(function ($val) {
            return PositionModel::whereId($val)->value('name') ?? '-';
        })->else()->selectplus(function () {
            return PositionModel::orderBy('id', 'desc')->pluck('name', 'id');
        });

        $grid->column('actual_num', '期初库存')->if(function () use ($order,$review_statu_ok) {
            return $order->review_status !== $review_statu_ok;
        })->edit();
        $grid->column('cost_price', '成本单价')->if(function () use ($order,$review_statu_ok) {
            return $order->review_status !== $review_statu_ok;
        })->edit();

        $grid->column('batch_no', '批次号')->if(function () use ($order,$review_statu_ok) {
            return $order->review_status !== $review_statu_ok;
        })->edit();
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
