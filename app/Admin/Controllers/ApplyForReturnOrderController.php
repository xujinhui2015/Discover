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
use App\Admin\Actions\Grid\BatchOrderPrint;
use App\Admin\Actions\Grid\EditOrder;
use App\Admin\Actions\Grid\ApplyForReturnOrderUnreview;
use App\Admin\Extensions\Form\Order\OrderController;
use App\Admin\Extensions\Grid\ColumnSelector;
use App\Admin\Extensions\Grid\ApplyForReturnOrderItemDetail;
use App\Admin\Repositories\ApplyForReturnOrder;
use App\Models\AttrModel;
use App\Models\ApplyForOrderModel;
use App\Models\ApplyForReturnItemModel;
use App\Models\ApplyForReturnOrderModel;
use App\Models\BrandModel;
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
            
            // 定义列配置（用于列选择器）
            $columnConfig = [
                ['name' => 'id', 'label' => 'ID'],
                ['name' => 'apply_for_order_no', 'label' => '物料单号'],
                ['name' => 'order_no', 'label' => '单号'],
                ['name' => 'user', 'label' => '创建用户'],
                ['name' => 'product_info', 'label' => '物料信息'],
                ['name' => 'review_status', 'label' => '审核状态'],
                ['name' => 'created_at', 'label' => '创建时间'],
                ['name' => 'other', 'label' => '备注'],
            ];
            
            $grid->column('id')->setHeaderAttributes(['class' => 'column-id'])->sortable();
            $grid->column('apply_for_order.order_no', '物料单号')->setHeaderAttributes(['class' => 'column-apply_for_order_no'])->emp();
            $grid->column('order_no')->setHeaderAttributes(['class' => 'column-order_no']);
            $grid->column('user.username', '创建用户')->setHeaderAttributes(['class' => 'column-user']);
            if ($useNameStyle) {
                $grid->column('product_names', '物料名称')->setHeaderAttributes(['class' => 'column-product_info'])->display(function () {
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
                    ->setHeaderAttributes(['class' => 'column-product_info'])
                    ->expand(ApplyForReturnOrderItemDetail::class);
            }
            $grid->column('review_status', '审核状态')
                ->setHeaderAttributes(['class' => 'column-review_status'])
                ->using($this->oredr_model::REVIEW_STATUS)
                ->label($this->oredr_model::REVIEW_STATUS_COLOR);
            $grid->column('created_at')->setHeaderAttributes(['class' => 'column-created_at']);
            $grid->column('other')->setHeaderAttributes(['class' => 'column-other'])->emp();
            $grid->disableQuickEditButton();
            $grid->disableCreateButton();

            $grid->actions(EditOrder::make());

            // 添加列选择器和其他工具
            $grid->tools(function ($tools) use ($columnConfig) {
                $tools->append(BatchOrderPrint::make());
                $tools->append(BatchCreateApplyForOrder::make());
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

                $attrIdFilter = $filter->where('attr_id', function (Builder $query) {
                    $attrId = (string) $this->getValue();
                    if ($attrId === '') {
                        return;
                    }

                    $query->whereHasIn('items', function (Builder $query) use ($attrId) {
                        $query->whereHasIn('sku', function (Builder $query) use ($attrId) {
                            $query->whereExists(function ($query) use ($attrId) {
                                $query->selectRaw('1')
                                    ->from('attr_value')
                                    ->where('attr_id', $attrId)
                                    ->whereRaw('FIND_IN_SET(attr_value.id, product_sku.attr_value_ids)');
                            });
                        });
                    });
                }, '属性')->width(3);
                $attrIdFilter->select(AttrModel::query()->pluck('name', 'id'))
                    ->load('attr_value_id', 'api/get-attr-value');

                $attrValueFilter = $filter->where('attr_value_id', function (Builder $query) {
                    $attrValueId = (string) $this->getValue();
                    if ($attrValueId === '') {
                        return;
                    }

                    $query->whereHasIn('items', function (Builder $query) use ($attrValueId) {
                        $query->whereHasIn('sku', function (Builder $query) use ($attrValueId) {
                            $query->whereRaw("CONCAT(',', IFNULL(attr_value_ids, ''), ',') LIKE ?", ["%,{$attrValueId},%"]);
                        });
                    });
                }, '属性值')
                    ->width(3);
                $attrValueFilter->select([])->placeholder('请选择属性值');

                $filter->where('brand_id', function (Builder $query) {
                    $query->whereHasIn('items', function (Builder $query) {
                        $query->whereHasIn('sku.product', function (Builder $query) {
                            $query->whereIn('brand_id', $this->getValue());
                        });
                    });
                }, '品牌')
                    ->multipleSelect(BrandModel::query()->pluck('name', 'id'))
                    ->width(3);

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
                $table->num('should_num', '退数')->required();
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

        $grid->column('should_num', '退数')
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
