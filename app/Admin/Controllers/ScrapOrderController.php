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
use App\Admin\Actions\Grid\ScrapOrderUnreview;
use App\Admin\Extensions\Form\Order\OrderController;
use App\Admin\Extensions\Grid\BatchDeail;
use App\Admin\Extensions\Grid\ColumnSelector;
use App\Admin\Extensions\Grid\ScrapOrderItemDetail;
use App\Admin\Repositories\ScrapOrder;
use App\Models\PersonalConfigModel;
use App\Models\ProductModel;
use App\Models\ProductSkuModel;
use App\Models\ScrapItemModel;
use App\Models\ScrapOrderModel;
use Dcat\Admin\Admin;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Models\Administrator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Fluent;

class ScrapOrderController extends OrderController
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
        return Grid::make(new ScrapOrder(['user']), function (Grid $grid) {
            $useNameStyle = $this->useMaterialNameStyle();
            
            // 定义列配置（用于列选择器）
            $columnConfig = [
                ['name' => 'id', 'label' => 'ID'],
                ['name' => 'order_no', 'label' => '单号'],
                ['name' => 'scrap_type', 'label' => '报废类型'],
                ['name' => 'user', 'label' => '创建用户'],
                ['name' => 'product_info', 'label' => '物料信息'],
                ['name' => 'review_status', 'label' => '审核状态'],
                ['name' => 'created_at', 'label' => '创建时间'],
                ['name' => 'other', 'label' => '备注'],
            ];
            
            $grid->column('id')->setHeaderAttributes(['class' => 'column-id'])->sortable();
            $grid->column('order_no')->setHeaderAttributes(['class' => 'column-order_no']);
            $grid->column('scrap_type', '报废类型')->setHeaderAttributes(['class' => 'column-scrap_type'])->display(function () {
                return ScrapOrderModel::SCRAP_TYPE[$this->scrap_type] ?? '-';
            });
            $grid->column('user.username', '创建用户')->setHeaderAttributes(['class' => 'column-user']);

            if ($useNameStyle) {
                $grid->column('product_names', '物料名称')->setHeaderAttributes(['class' => 'column-product_info'])->display(function () {
                    $productNames = ProductModel::query()
                        ->whereIn('id', ProductSkuModel::query()
                            ->whereIn('id', ScrapItemModel::query()
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
                    ->expand(ScrapOrderItemDetail::class);
            }

            $grid->column('review_status', '审核状态')
                ->setHeaderAttributes(['class' => 'column-review_status'])
                ->using($this->oredr_model::REVIEW_STATUS)
                ->label($this->oredr_model::REVIEW_STATUS_COLOR);
            $grid->column('created_at')->setHeaderAttributes(['class' => 'column-created_at']);
            $grid->column('other')->setHeaderAttributes(['class' => 'column-other'])->emp();
            $grid->disableQuickEditButton();
            $grid->actions(EditOrder::make());
            $grid->tools(function ($tools) use ($columnConfig) {
                $tools->append(new ColumnSelector($columnConfig));
                $tools->append(BatchOrderPrint::make());
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
                $filter->equal('scrap_type', '报废类型')->select(ScrapOrderModel::SCRAP_TYPE)->width(3);
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
            $row->width(6)->text('order_no', '单号')->default(build_order_no('BF'))->required()->readOnly();
            $row->width(6)->datetime('created_at', '业务日期')->default(now())->required();
        });
        $form->row(function (Form\Row $row) {
            $users = Administrator::query()->latest()->pluck('name', 'id');
            $row->width(6)->select('scrap_type', '报废类型')
                ->options(ScrapOrderModel::SCRAP_TYPE)
                ->default(ScrapOrderModel::SCRAP_TYPE_EXPIRED)
                ->required();
            $row->width(6)->select('apply_id', '审批人')
                ->options($users)
                ->default(head($users->keys()->toArray()))
                ->required();
        });
        $form->row(function (Form\Row $row) {
            $row->width(12)->text('other', '备注')->saveAsString();
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
                $table->select('standard', '通用标准')->options(ScrapItemModel::STANDARD)->default(0);
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
            return $order->review_status === ScrapOrderModel::REVIEW_STATUS_OK;
        })->display(function () {
            return $this->sku['attr_value_ids_str'] ?? '';
        })->else()->selectplus(function (Fluent $fluent) {
            return $fluent->sku['product']['sku_key_value'];
        });

        $grid->column('standard', '通用标准')->if(function () use ($order) {
            return $order->review_status === ScrapOrderModel::REVIEW_STATUS_OK;
        })->display(function () {
            return ScrapItemModel::STANDARD[$this->standard];
        })->else()->selectplus(ScrapItemModel::STANDARD);

        $grid->column('actual_num', '报废数量');
        $grid->column('sku_stock_num', '库存')->display(function ($val) {
            return $val;
        });
        $grid->column('pcxq', '批次详情')->batch_detail(function (BatchDeail $batchDeail) {
            return route('scrap-batchs.index', [
                Grid::IFRAME_QUERY_NAME => 1,
                'item_id' => $batchDeail->row->id,
                'sku_id' => $batchDeail->row->sku_id,
                'standard' => $batchDeail->row->standard,
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
            $grid->tools(ScrapOrderUnreview::make());
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
