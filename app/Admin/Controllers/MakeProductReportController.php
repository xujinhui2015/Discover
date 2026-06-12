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

use App\Admin\Extensions\Grid\ApplyOfBatchDetail;
use App\Admin\Extensions\Grid\ColumnSelector;
use App\Admin\Repositories\ApplyForItem;
use App\Admin\Repositories\MakeProductItem;
use App\Http\Controllers\Controller;
use App\Models\ApplyForOrderModel;
use App\Models\MakeProductItemModel;
use App\Models\MakeProductOrderModel;
use Dcat\Admin\Grid;
use Dcat\Admin\Layout\Content;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class MakeProductReportController extends Controller
{
    public function applyForItems(Content $content)
    {
        $grid = Grid::make(new ApplyForItem(['order', 'sku', 'sku.product']), function (Grid $grid) {
            // 定义列配置（用于列选择器）
            $columnConfig = [
                ['name' => 'order_no', 'label' => '订单号'],
                ['name' => 'product_name', 'label' => '物料名称'],
                ['name' => 'unit_name', 'label' => '单位'],
                ['name' => 'type_str', 'label' => '分类'],
                ['name' => 'brand_name', 'label' => '品牌'],
                ['name' => 'attr_value_ids_str', 'label' => '属性'],
                ['name' => 'standard_str', 'label' => '通用标准'],
                ['name' => 'cost_price', 'label' => '成本总价'],
                ['name' => 'should_num', 'label' => '申领数量'],
                ['name' => 'actual_num', 'label' => '实领数量'],
                ['name' => 'batch_detail', 'label' => '领料批次详情'],
            ];
            
            // 添加列选择器到工具栏
            $grid->tools(function ($tools) use ($columnConfig) {
                $tools->append(new ColumnSelector($columnConfig));
            });
            
            $grid->model()->resetOrderBy();
            $grid->model()->whereHas('order', function (Builder $builder) {
                $builder->where('review_status', ApplyForOrderModel::REVIEW_STATUS_OK);
            })->orderByDesc('id')->orderByDesc('order_id');
            $grid->column('order.order_no', '订单号')->setHeaderAttributes(['class' => 'column-order_no']);
            $grid->column('sku.product.name', '物料名称')->setHeaderAttributes(['class' => 'column-product_name']);
            $grid->column('sku.product.unit.name', '单位')->setHeaderAttributes(['class' => 'column-unit_name']);
            $grid->column('sku.product.type_str', '分类')->setHeaderAttributes(['class' => 'column-type_str']);
            $grid->column('sku.product.brand.name', '品牌')->setHeaderAttributes(['class' => 'column-brand_name']);
            $grid->column('sku.attr_value_ids_str', '属性')->setHeaderAttributes(['class' => 'column-attr_value_ids_str']);
//            $grid->column('percent', '含绒百分比');
            $grid->column('standard_str', '通用标准')->setHeaderAttributes(['class' => 'column-standard_str']);
            $grid->column('cost_price', "成本总价")->setHeaderAttributes(['class' => 'column-cost_price']);
            $grid->column('should_num', '申领数量')->setHeaderAttributes(['class' => 'column-should_num']);
            $grid->column('actual_num', '实领数量')->setHeaderAttributes(['class' => 'column-actual_num']);
            $grid->column('_', '领料批次详情')->setHeaderAttributes(['class' => 'column-batch_detail'])->expand(ApplyOfBatchDetail::class);

            $grid->filter(function (Grid\Filter $filter) {
                $filter->dateRange('order.updated_at', "时间")->datetime()->width(6)->default([
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

                $filter->group('should_num', function ($group) {
                    $group->gt('大于');
                    $group->lt('小于');
                    $group->nlt('不小于');
                    $group->ngt('不大于');
                    $group->equal('等于');
                }, '申领数量')->width(3);
                $filter->group('actual_num', function ($group) {
                    $group->gt('大于');
                    $group->lt('小于');
                    $group->nlt('不小于');
                    $group->ngt('不大于');
                    $group->equal('等于');
                }, '实领数量')->width(3);
//                $filter->like('percent', "含绒量")->decimal()->width(3);
                $filter->equal('standard', "通用标准")->select(ApplyForOrderModel::STANDARD)->width(3);
            });

            $grid->export()->rows(function (array $rows) {
                return array_map(function ($row) {
                    return [
                        '订单号' => $row['order']['order_no'],
                        '物料名称' => $row['sku']['product']['name'],
                        '单位' => $row['sku']['product']['unit']['name'],
                        '分类' => $row['sku']['product']['type_str'],
                        '品牌' => $row['sku']['product']['brand']['name'],
                        '属性' => $row['sku']['attr_value_ids_str'],
//                        '含绒百分比' => $row['percent'],
                        '通用标准' => $row['standard_str'],
                        '成本总价' => $row['cost_price'],
                        '申领数量' => $row['should_num'],
                        '实领数量' => $row['actual_num'],
                    ];
                }, $rows);
            })->extension("xlsx");

            $grid->disableCreateButton();
            $grid->disableActions();
        });
        return $content
            ->title("领料出库明细")
            ->description(' ')
            ->full()
            ->body($grid);
    }

    public function applyForSummary(Content $content)
    {
        $grid = Grid::make(new ApplyForItem(['order', 'sku', 'sku.product']), function (Grid $grid) {
            // 定义列配置（用于列选择器）
            $columnConfig = [
                ['name' => 'product_name', 'label' => '物料名称'],
                ['name' => 'unit_name', 'label' => '单位'],
                ['name' => 'type_str', 'label' => '分类'],
                ['name' => 'brand_name', 'label' => '品牌'],
                ['name' => 'attr_value_ids_str', 'label' => '属性'],
                ['name' => 'standard_str', 'label' => '通用标准'],
                ['name' => 'sum_should_num', 'label' => '申领数量'],
                ['name' => 'sum_actual_num', 'label' => '实领数量'],
                ['name' => 'sum_cost_price', 'label' => '成本总价'],
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
                DB::raw('sum(actual_num) as sum_actual_num'),
                DB::raw('sum(cost_price) as sum_cost_price')
            )->whereHas('order', function (Builder $builder) {
                $builder->where('review_status', ApplyForOrderModel::REVIEW_STATUS_OK);
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
            $grid->column('sum_should_num', '申领数量')->setHeaderAttributes(['class' => 'column-sum_should_num']);
            $grid->column('sum_actual_num', '实领数量')->setHeaderAttributes(['class' => 'column-sum_actual_num']);
            $grid->column('sum_cost_price', '成本总价')->setHeaderAttributes(['class' => 'column-sum_cost_price']);

            $grid->filter(function (Grid\Filter $filter) {
                $filter->dateRange('order.updated_at', "时间")->datetime()->width(6)->default([
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
                $filter->equal('standard', "通用标准")->select(ApplyForOrderModel::STANDARD)->width(3);
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
                        '成本总价' => $row['sum_cost_price'],
                        '申领数量' => $row['sum_actual_num'],
                        '实领数量' => $row['sum_should_num'],
                    ];
                }, $rows);
            })->extension("xlsx");

            $grid->disableCreateButton();
            $grid->disableActions();
        });
        return $content
            ->title("领料出库汇总")
            ->description(' ')
            ->full()
            ->body($grid);
    }

    public function items(Content $content)
    {
        $grid = Grid::make(new MakeProductItem(['order', 'sku', 'sku.product', 'position']), function (Grid $grid) {
            // 定义列配置（用于列选择器）
            $columnConfig = [
                ['name' => 'order_no', 'label' => '订单号'],
                ['name' => 'product_name', 'label' => '物料名称'],
                ['name' => 'unit_name', 'label' => '单位'],
                ['name' => 'type_str', 'label' => '分类'],
                ['name' => 'brand_name', 'label' => '品牌'],
                ['name' => 'attr_value_ids_str', 'label' => '属性'],
                ['name' => 'standard_str', 'label' => '通用标准'],
                ['name' => 'cost_price', 'label' => '成本单价'],
                ['name' => 'sum_cost_price', 'label' => '成本总价'],
                ['name' => 'should_num', 'label' => '计划入库数'],
                ['name' => 'actual_num', 'label' => '实际入库数'],
                ['name' => 'position_name', 'label' => '入库位置'],
                ['name' => 'batch_no', 'label' => '批次号'],
            ];
            
            // 添加列选择器到工具栏
            $grid->tools(function ($tools) use ($columnConfig) {
                $tools->append(new ColumnSelector($columnConfig));
            });
            
            $grid->model()->resetOrderBy();
            $grid->model()->whereHas('order', function (Builder $builder) {
                $builder->where('review_status', MakeProductItemModel::REVIEW_STATUS_OK);
            })->orderByDesc('id')->orderByDesc('order_id');
            $grid->column('order.order_no', '订单号')->setHeaderAttributes(['class' => 'column-order_no']);
            $grid->column('sku.product.name', '物料名称')->setHeaderAttributes(['class' => 'column-product_name']);
            $grid->column('sku.product.unit.name', '单位')->setHeaderAttributes(['class' => 'column-unit_name']);
            $grid->column('sku.product.type_str', '分类')->setHeaderAttributes(['class' => 'column-type_str']);
            $grid->column('sku.product.brand.name', '品牌')->setHeaderAttributes(['class' => 'column-brand_name']);
            $grid->column('sku.attr_value_ids_str', '属性')->setHeaderAttributes(['class' => 'column-attr_value_ids_str']);
//            $grid->column('percent', '含绒百分比');
            $grid->column('standard_str', '通用标准')->setHeaderAttributes(['class' => 'column-standard_str']);
            $grid->column('cost_price', '成本单价')->setHeaderAttributes(['class' => 'column-cost_price']);
            $grid->column('sum_cost_price', '成本总价')->setHeaderAttributes(['class' => 'column-sum_cost_price']);

            $grid->column('should_num', '计划入库数')->setHeaderAttributes(['class' => 'column-should_num']);
            $grid->column('actual_num', '实际入库数')->setHeaderAttributes(['class' => 'column-actual_num']);
            $grid->column('position.name', '入库位置')->setHeaderAttributes(['class' => 'column-position_name']);
            $grid->column('batch_no', '批次号')->setHeaderAttributes(['class' => 'column-batch_no']);

            $grid->filter(function (Grid\Filter $filter) {
                $filter->dateRange('order.updated_at', "时间")->datetime()->width(6)->default([
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

                $filter->group('should_num', function ($group) {
                    $group->gt('大于');
                    $group->lt('小于');
                    $group->nlt('不小于');
                    $group->ngt('不大于');
                    $group->equal('等于');
                }, '申领数量')->width(3);
                $filter->group('actual_num', function ($group) {
                    $group->gt('大于');
                    $group->lt('小于');
                    $group->nlt('不小于');
                    $group->ngt('不大于');
                    $group->equal('等于');
                }, '实领数量')->width(3);
//                $filter->like('percent', "含绒量")->decimal()->width(3);
                $filter->equal('standard', "通用标准")->select(MakeProductOrderModel::STANDARD)->width(3);
            });

            $grid->export()->rows(function (array $rows) {
                return array_map(function ($row) {
                    return [
                        '订单号' => $row['order']['order_no'],
                        '物料名称' => $row['sku']['product']['name'],
                        '单位' => $row['sku']['product']['unit']['name'],
                        '分类' => $row['sku']['product']['type_str'],
                        '品牌' => $row['sku']['product']['brand']['name'],
                        '属性' => $row['sku']['attr_value_ids_str'],
//                        '含绒百分比' => $row['percent'],
                        '通用标准' => $row['standard_str'],
                        '成本单价' => $row['cost_price'],
                        '成本总价' => $row['sum_cost_price'],
                        '计划入库数' => $row['should_num'],
                        '实际入库数' => $row['actual_num'],
                        '入库位置' => $row['position']['name'],
                        '批次号' => $row['batch_no']
                    ];
                }, $rows);
            })->extension("xlsx");

            $grid->disableCreateButton();
            $grid->disableActions();
        });
        return $content
            ->title("生产入库明细")
            ->description(' ')
            ->full()
            ->body($grid);
    }

    public function summary(Content $content)
    {
        $grid = Grid::make(new MakeProductItem(['order', 'sku', 'sku.product']), function (Grid $grid) {
            // 定义列配置（用于列选择器）
            $columnConfig = [
                ['name' => 'product_name', 'label' => '物料名称'],
                ['name' => 'unit_name', 'label' => '单位'],
                ['name' => 'type_str', 'label' => '分类'],
                ['name' => 'brand_name', 'label' => '品牌'],
                ['name' => 'attr_value_ids_str', 'label' => '属性'],
                ['name' => 'standard_str', 'label' => '通用标准'],
                ['name' => 'sum_should_num', 'label' => '计划入库数'],
                ['name' => 'sum_actual_num', 'label' => '实际入库数'],
                ['name' => 'sum_cost_price', 'label' => '成本总价'],
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
                DB::raw('sum(actual_num) as sum_actual_num'),
                DB::raw('sum(sum_cost_price) as sum_cost_price')
            )->whereHas('order', function (Builder $builder) {
                $builder->where('review_status', MakeProductOrderModel::REVIEW_STATUS_OK);
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
            $grid->column('sum_should_num', '计划入库数')->setHeaderAttributes(['class' => 'column-sum_should_num']);
            $grid->column('sum_actual_num', '实际入库数')->setHeaderAttributes(['class' => 'column-sum_actual_num']);
            $grid->column('sum_cost_price', '成本总价')->setHeaderAttributes(['class' => 'column-sum_cost_price']);

            $grid->filter(function (Grid\Filter $filter) {
                $filter->dateRange('order.updated_at', "时间")->datetime()->width(6)->default([
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
                $filter->equal('standard', "通用标准")->select(MakeProductOrderModel::STANDARD)->width(3);
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
                        '成本总价' => $row['sum_cost_price'],
                        '实际入库数' => $row['sum_actual_num'],
                        '计划入库数' => $row['sum_should_num'],
                    ];
                }, $rows);
            })->extension("xlsx");

            $grid->disableCreateButton();
            $grid->disableActions();
        });
        return $content
            ->title("领料出库汇总")
            ->description(' ')
            ->full()
            ->body($grid);
    }
}
