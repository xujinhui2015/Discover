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

use App\Admin\Extensions\Grid\ColumnSelector;
use App\Admin\Repositories\PurchaseInItem;
use App\Admin\Repositories\PurchaseOrderAmount;
use App\Http\Controllers\Controller;
use App\Models\PurchaseInOrderModel;
use App\Models\PurchaseOrderAmountModel;
use App\Models\SupplierModel;
use Dcat\Admin\Grid;
use Dcat\Admin\Layout\Content;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class PurchaseInReportController extends Controller
{
    public function orderAmount(Content $content)
    {
        $grid = Grid::make(new PurchaseOrderAmount(['order', 'supplier', 'accountant']), function (Grid $grid) {
            // 定义列配置（用于列选择器）
            $columnConfig = [
                ['name' => 'id', 'label' => 'ID'],
                ['name' => 'order_no', 'label' => '采购订单'],
                ['name' => 'supplier_name', 'label' => '供应商名称'],
                ['name' => 'should_amount', 'label' => '费用金额'],
                ['name' => 'actual_amount', 'label' => '结算金额'],
                ['name' => 'status', 'label' => '单据状态'],
                ['name' => 'created_at', 'label' => '创建时间'],
            ];
            
            // 添加列选择器到工具栏
            $grid->tools(function ($tools) use ($columnConfig) {
                $tools->append(new ColumnSelector($columnConfig));
            });
            
            $grid->column('id')->setHeaderAttributes(['class' => 'column-id'])->sortable();
            $grid->column('order.order_no', "采购订单")->setHeaderAttributes(['class' => 'column-order_no']);
            $grid->column('supplier.name', "供应商名称")->setHeaderAttributes(['class' => 'column-supplier_name']);
            $grid->column('should_amount', '费用金额')->setHeaderAttributes(['class' => 'column-should_amount']);
            $grid->column('actual_amount', '结算金额')->setHeaderAttributes(['class' => 'column-actual_amount']);
            $grid->column('status', '单据状态')->setHeaderAttributes(['class' => 'column-status'])->using(PurchaseOrderAmountModel::STATUS)->label(PurchaseOrderAmountModel::STATUS_COLOR);
            $grid->column('created_at')->setHeaderAttributes(['class' => 'column-created_at']);
            $grid->filter(function (Grid\Filter $filter) {
                $filter->dateRange('created_at', "时间")->datetime()->width(6)->default([
                    'start' => now()->subMonth(),
                    'end' => now()
                ]);
                $filter->in('supplier_id', "供应商名称")->width(3)->multipleSelect(SupplierModel::query()->pluck('name', 'id'));
                $filter->in('status', '单据状态')->width(3)->multipleSelect(PurchaseOrderAmountModel::STATUS);
            });
            $grid->disableCreateButton();
            $grid->export()->rows(function (array $rows) {
                return array_map(function ($row) {
                    return [
                        '采购订单' => $row['order']['order_no'],
                        '供应商名称' => $row['supplier']['name'],
                        '费用金额' => $row['should_amount'],
                        '结算金额' => $row['actual_amount'],
                        '单据状态' => PurchaseOrderAmountModel::STATUS[$row['status']],
                        '创建时间' => $row['created_at'],
                    ];
                }, $rows);
            })->extension("xlsx");
            $grid->disableActions();
        });

        return $content
            ->title("采购订单金额")
            ->description(' ')
            ->full()
            ->body($grid);
    }

    public function items(Content $content)
    {
        $grid = Grid::make(new PurchaseInItem(['order', 'sku', 'order.supplier', 'sku.product', 'position']), function (Grid $grid) {
            // 定义列配置（用于列选择器）
            $columnConfig = [
                ['name' => 'order_no', 'label' => '订单号'],
                ['name' => 'supplier_name', 'label' => '供应商'],
                ['name' => 'product_name', 'label' => '物料名称'],
                ['name' => 'unit_name', 'label' => '单位'],
                ['name' => 'type_str', 'label' => '分类'],
                ['name' => 'brand_name', 'label' => '品牌'],
                ['name' => 'attr_value_ids_str', 'label' => '属性'],
                ['name' => 'standard_str', 'label' => '通用标准'],
                ['name' => 'position_name', 'label' => '入库位置'],
                ['name' => 'should_num', 'label' => '采购数量'],
                ['name' => 'actual_num', 'label' => '入库数量'],
                ['name' => 'price', 'label' => '采购价格'],
                ['name' => 'batch_no', 'label' => '批次号'],
                ['name' => 'apply_at', 'label' => '时间'],
            ];
            
            // 添加列选择器到工具栏
            $grid->tools(function ($tools) use ($columnConfig) {
                $tools->append(new ColumnSelector($columnConfig));
            });
            
            $grid->model()->resetOrderBy();
            $grid->model()->whereHas('order', function (Builder $builder) {
                $builder->where('review_status', PurchaseInOrderModel::REVIEW_STATUS_OK);
            })->orderByDesc('id')->orderByDesc('order_id');
            $grid->column('order.order_no', '订单号')->setHeaderAttributes(['class' => 'column-order_no']);
            $grid->column('order.supplier.name', '供应商')->setHeaderAttributes(['class' => 'column-supplier_name']);
            $grid->column('sku.product.name', '物料名称')->setHeaderAttributes(['class' => 'column-product_name']);
            $grid->column('sku.product.unit.name', '单位')->setHeaderAttributes(['class' => 'column-unit_name']);
            $grid->column('sku.product.type_str', '分类')->setHeaderAttributes(['class' => 'column-type_str']);
            $grid->column('sku.product.brand.name', '品牌')->setHeaderAttributes(['class' => 'column-brand_name']);
            $grid->column('sku.attr_value_ids_str', '属性')->setHeaderAttributes(['class' => 'column-attr_value_ids_str']);
//            $grid->column('percent', '含绒百分比');
            $grid->column('standard_str', '通用标准')->setHeaderAttributes(['class' => 'column-standard_str']);
            $grid->column('position.name', '入库位置')->setHeaderAttributes(['class' => 'column-position_name']);
            $grid->column('should_num', '采购数量')->setHeaderAttributes(['class' => 'column-should_num'])->sortable();
            $grid->column('actual_num', '入库数量')->setHeaderAttributes(['class' => 'column-actual_num'])->sortable();
            $grid->column('price', '采购价格')->setHeaderAttributes(['class' => 'column-price'])->sortable();
            $grid->column('batch_no', '批次号')->setHeaderAttributes(['class' => 'column-batch_no'])->sortable();
            $grid->column('order.apply_at', '时间')->setHeaderAttributes(['class' => 'column-apply_at'])->sortable();
            $grid->filter(function (Grid\Filter $filter) {
                $filter->dateRange('order.apply_at', "时间")->datetime()->width(6)->default([
                    'start' => now()->subMonth(),
                    'end' => now()
                ]);
                $filter->where("product_name", function (Builder $query) {
                    $query->whereHasIn("sku.product", function (Builder $query) {
                        $query->where(function (Builder $query) {
                            $query->orWhere("name", "like", "%" . $this->getValue()."%");
                            $query->orWhere("py_code", "like", "%" . $this->getValue()."%");
                            $query->orWhere('item_no', 'like', "%" . $this->getValue()."%");
                        });
                    });
                }, "关键字")->placeholder("物料名称，拼音码，编号")->width(3);
                $filter->equal('order.supplier_id', '供应商')->select(SupplierModel::query()->latest()->pluck('name', 'id'))->width(3);
                $filter->group('should_num', function ($group) {
                    $group->gt('大于');
                    $group->lt('小于');
                    $group->nlt('不小于');
                    $group->ngt('不大于');
                    $group->equal('等于');
                }, '采购数量')->width(3);
                $filter->group('actual_num', function ($group) {
                    $group->gt('大于');
                    $group->lt('小于');
                    $group->nlt('不小于');
                    $group->ngt('不大于');
                    $group->equal('等于');
                }, '入库数量')->width(3);
//                $filter->like('percent', "含绒量")->decimal()->width(3);
                $filter->equal('standard', "通用标准")->select(PurchaseInOrderModel::STANDARD)->width(3);
            });

            $grid->export()->rows(function (array $rows) {
                return array_map(function ($row) {
                    return [
                        '订单号' => $row['order']['order_no'],
                        '供应商' => $row['order']['supplier']['name'],
                        '物料名称' => $row['sku']['product']['name'],
                        '单位' => $row['sku']['product']['unit']['name'],
                        '分类' => $row['sku']['product']['type_str'],
                        '品牌' => $row['sku']['product']['brand']['name'],
                        '属性' => $row['sku']['attr_value_ids_str'],
//                        '含绒百分比' => $row['percent'],
                        '通用标准' => $row['standard_str'],
                        '入库位置' => $row['position']['name'],
                        '采购数量' => $row['should_num'],
                        '入库数量' => $row['actual_num'],
                        '采购价格' => $row['price'],
                        '批次号' => $row['batch_no']
                    ];
                }, $rows);
            })->extension("xlsx");

            $grid->disableCreateButton();
            $grid->disableActions();
        });
        return $content
            ->title("采购入库明细")
            ->description(' ')
            ->full()
            ->body($grid);
    }

    public function summaryBySupplier(Content $content)
    {
        $grid = Grid::make(new PurchaseInItem(['sku', 'sku.product']), function (Grid $grid) {
            // 定义列配置（用于列选择器）
            $columnConfig = [
                ['name' => 'supplier_id', 'label' => '供应商'],
                ['name' => 'product_name', 'label' => '物料名称'],
                ['name' => 'unit_name', 'label' => '单位'],
                ['name' => 'type_str', 'label' => '分类'],
                ['name' => 'brand_name', 'label' => '品牌'],
                ['name' => 'attr_value_ids_str', 'label' => '属性'],
                ['name' => 'standard_str', 'label' => '通用标准'],
                ['name' => 'sum_should_num', 'label' => '采购数量'],
                ['name' => 'sum_actual_num', 'label' => '入库数量'],
            ];
            
            // 添加列选择器到工具栏
            $grid->tools(function ($tools) use ($columnConfig) {
                $tools->append(new ColumnSelector($columnConfig));
            });
            
            $grid->model()->resetOrderBy();
            $grid->model()
                ->select([
                    'purchase_in_order.supplier_id as supplier_id',
                    'purchase_in_item.sku_id as sku_id',
                    'purchase_in_item.standard as standard',
                    'purchase_in_item.percent as percent',
                    DB::raw('sum(purchase_in_item.should_num) as sum_should_num'),
                    DB::raw('sum(purchase_in_item.actual_num) as sum_actual_num'),
                ])
                ->leftJoin('purchase_in_order', 'purchase_in_item.order_id', '=', 'purchase_in_order.id')
                ->where('purchase_in_order.review_status', PurchaseInOrderModel::REVIEW_STATUS_OK)
                ->groupBy(
                    'purchase_in_order.supplier_id',
                    'purchase_in_item.sku_id',
                    'purchase_in_item.standard',
                    'purchase_in_item.percent'
                );

            $grid->column('supplier_id', '供应商')->setHeaderAttributes(['class' => 'column-supplier_id'])->display(function ($val) {
                return SupplierModel::query()->where('id', $val)->value('name');
            })->sortable();
            $grid->column('sku.product.name', '物料名称')->setHeaderAttributes(['class' => 'column-product_name']);
            $grid->column('sku.product.unit.name', '单位')->setHeaderAttributes(['class' => 'column-unit_name']);
            $grid->column('sku.product.type_str', '分类')->setHeaderAttributes(['class' => 'column-type_str']);
            $grid->column('sku.product.brand.name', '品牌')->setHeaderAttributes(['class' => 'column-brand_name']);
            $grid->column('sku.attr_value_ids_str', '属性')->setHeaderAttributes(['class' => 'column-attr_value_ids_str']);
//            $grid->column('percent', '含绒百分比');
            $grid->column('standard_str', '通用标准')->setHeaderAttributes(['class' => 'column-standard_str']);
            $grid->column('sum_should_num', '采购数量')->setHeaderAttributes(['class' => 'column-sum_should_num'])->sortable();
            $grid->column('sum_actual_num', '入库数量')->setHeaderAttributes(['class' => 'column-sum_actual_num'])->sortable();

            $grid->filter(function (Grid\Filter $filter) {
                $filter->dateRange('order.apply_at', "时间")->datetime()->width(6)->default([
                    'start' => now()->subMonth(),
                    'end' => now()
                ]);
                $filter->where("product_name", function (Builder $query) {
                    $query->whereHasIn("sku.product", function (Builder $query) {
                        $query->where(function (Builder $query) {
                            $query->orWhere("name", "like", "%" . $this->getValue()."%");
                            $query->orWhere("py_code", "like", "%" . $this->getValue()."%");
                            $query->orWhere('item_no', 'like', "%" . $this->getValue()."%");
                        });
                    });
                }, "关键字")->placeholder("物料名称，拼音码，编号")->width(3);
                $filter->equal('supplier_id', '供应商')->select(SupplierModel::query()->latest()->pluck('name', 'id'))->width(3);
//                $filter->like('percent', "含绒量")->decimal()->width(3);
                $filter->equal('standard', "通用标准")->select(PurchaseInOrderModel::STANDARD)->width(3);
            });

            $grid->export()->rows(function (array $rows) {
                return array_map(function ($row) {
                    return [
                        '供应商' => SupplierModel::query()->where('id', $row['supplier_id'])->value('name'),
                        '物料名称' => $row['sku']['product']['name'],
                        '单位' => $row['sku']['product']['unit']['name'],
                        '分类' => $row['sku']['product']['type_str'],
                        '品牌' => $row['sku']['product']['brand']['name'],
                        '属性' => $row['sku']['attr_value_ids_str'],
//                        '含绒百分比' => $row['percent'],
                        '通用标准' => $row['standard_str'],
                        '采购数量' => $row['sum_should_num'],
                        '入库数量' => $row['sum_actual_num'],
                    ];
                }, $rows);
            })->extension("xlsx");

            $grid->disableActions();
            $grid->disableCreateButton();
        });
        return $content
            ->title("采购入库汇总(供应商)")
            ->description(' ')
            ->full()
            ->body($grid);
    }

    public function summaryBySku(Content $content)
    {
        $grid = Grid::make(new PurchaseInItem(['order', 'sku', 'sku.product']), function (Grid $grid) {
            // 定义列配置（用于列选择器）
            $columnConfig = [
                ['name' => 'product_name', 'label' => '物料名称'],
                ['name' => 'unit_name', 'label' => '单位'],
                ['name' => 'type_str', 'label' => '分类'],
                ['name' => 'brand_name', 'label' => '品牌'],
                ['name' => 'attr_value_ids_str', 'label' => '属性'],
                ['name' => 'standard_str', 'label' => '通用标准'],
                ['name' => 'sum_should_num', 'label' => '采购数量'],
                ['name' => 'sum_actual_num', 'label' => '入库数量'],
            ];
            
            // 添加列选择器到工具栏
            $grid->tools(function ($tools) use ($columnConfig) {
                $tools->append(new ColumnSelector($columnConfig));
            });
            
            $grid->model()->resetOrderBy();
            $grid->model()->select(
                'sku_id',
                'standard',
//                'percent',
                DB::raw('sum(should_num) as sum_should_num'),
                DB::raw('sum(actual_num) as sum_actual_num')
            )->whereHas('order', function (Builder $builder) {
                $builder->where('review_status', PurchaseInOrderModel::REVIEW_STATUS_OK);
            })->groupBy(
                'sku_id',
//                'percent',
                'standard'
            );

            $grid->column('sku.product.name', '物料名称')->setHeaderAttributes(['class' => 'column-product_name']);
            $grid->column('sku.product.unit.name', '单位')->setHeaderAttributes(['class' => 'column-unit_name']);
            $grid->column('sku.product.type_str', '分类')->setHeaderAttributes(['class' => 'column-type_str']);
            $grid->column('sku.product.brand.name', '品牌')->setHeaderAttributes(['class' => 'column-brand_name']);
            $grid->column('sku.attr_value_ids_str', '属性')->setHeaderAttributes(['class' => 'column-attr_value_ids_str']);
//            $grid->column('percent', '含绒百分比');
            $grid->column('standard_str', '通用标准')->setHeaderAttributes(['class' => 'column-standard_str']);
            $grid->column('sum_should_num', '采购数量')->setHeaderAttributes(['class' => 'column-sum_should_num'])->sortable();
            $grid->column('sum_actual_num', '入库数量')->setHeaderAttributes(['class' => 'column-sum_actual_num'])->sortable();

            $grid->filter(function (Grid\Filter $filter) {
                $filter->dateRange('order.apply_at', "时间")->datetime()->width(6)->default([
                    'start' => now()->subMonth(),
                    'end' => now()
                ]);
                $filter->where("product_name", function (Builder $query) {
                    $query->whereHasIn("sku.product", function (Builder $query) {
                        $query->where(function (Builder $query) {
                            $query->orWhere("name", "like", "%" . $this->getValue()."%");
                            $query->orWhere("py_code", "like", "%" . $this->getValue()."%");
                            $query->orWhere('item_no', 'like', "%" . $this->getValue()."%");
                        });
                    });
                }, "关键字")->placeholder("物料名称，拼音码，编号")->width(3);

//                $filter->like('percent', "含绒量")->decimal()->width(3);
                $filter->equal('standard', "通用标准")->select(PurchaseInOrderModel::STANDARD)->width(3);
            });

            $grid->export()->rows(function (array $rows) {
                return array_map(function ($row) {
                    return [
                        '物料名称' => $row['sku']['product']['name'],
                        '单位' => $row['sku']['product']['unit']['name'],
                        '分类' => $row['sku']['product']['type_str'],
                        '品牌' => $row['sku']['product']['brand']['name'],
                        '属性' => $row['sku']['attr_value_ids_str'],
//                        '含绒百分比' => $row['percent'],
                        '通用标准' => $row['standard_str'],
                        '采购数量' => $row['sum_should_num'],
                        '入库数量' => $row['sum_actual_num'],
                    ];
                }, $rows);
            })->extension("xlsx");

            $grid->disableCreateButton();
            $grid->disableActions();
        });
        return $content
            ->title("采购入库汇总(物料)")
            ->description(' ')
            ->full()
            ->body($grid);
    }
}
