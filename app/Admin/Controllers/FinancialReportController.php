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
use App\Admin\Repositories\CostOrder;
use App\Admin\Repositories\StatementItem;
use App\Models\CostOrderModel;
use App\Models\CustomerModel;
use App\Models\StatementOrderModel;
use App\Models\SupplierModel;
use Dcat\Admin\Controllers\AdminController;
use Dcat\Admin\Grid;
use Dcat\Admin\Layout\Content;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class FinancialReportController extends AdminController
{
    public function settlementHistory(Content $content)
    {
        $grid = Grid::make(new StatementItem(['cost_order', 'order']), function (Grid $grid) {
            $grid->model()->resetOrderBy();
            $grid->model()->whereHas('order', function (Builder $builder) {
                $builder->where('review_status', StatementOrderModel::REVIEW_STATUS_OK);
            })->orderByDesc('id')->orderByDesc('statement_order_id');
            
            // 定义列配置（用于列选择器）
            $columnConfig = [
                ['name' => 'cost_order_no', 'label' => '费用单号'],
                ['name' => 'order_no', 'label' => '结算单号'],
                ['name' => 'company_name', 'label' => '公司名称'],
                ['name' => 'category_str', 'label' => '费用分类'],
                ['name' => 'should_amount', 'label' => '期初应付'],
                ['name' => 'actual_amount', 'label' => '本期发生额'],
                ['name' => 'discount_amount', 'label' => '本期优惠金额'],
                ['name' => 'remaining_sum', 'label' => '结余应付'],
                ['name' => 'other', 'label' => '备注'],
                ['name' => 'updated_at', 'label' => '操作日期'],
            ];
            
            // 添加列选择器到工具栏
            $grid->tools(function ($tools) use ($columnConfig) {
                $tools->append(new ColumnSelector($columnConfig));
            });
            
            $grid->column('cost_order.order_no', '费用单号')->setHeaderAttributes(['class' => 'column-cost_order_no'])->sortable();
            $grid->column('order.order_no', '结算单号')->setHeaderAttributes(['class' => 'column-order_no'])->sortable();
            $grid->column('order.company_name', '公司名称')->setHeaderAttributes(['class' => 'column-company_name']);
            $grid->column('order.category_str', '费用分类')->setHeaderAttributes(['class' => 'column-category_str']);
            $grid->column('should_amount', '期初应付')->setHeaderAttributes(['class' => 'column-should_amount'])->sortable();
            $grid->column('actual_amount', '本期发生额')->setHeaderAttributes(['class' => 'column-actual_amount'])->sortable();
            $grid->column('discount_amount', '本期优惠金额')->setHeaderAttributes(['class' => 'column-discount_amount'])->sortable();
            $grid->column('remaining_sum', '结余应付')->setHeaderAttributes(['class' => 'column-remaining_sum']);
            $grid->column('order.other', '备注')->setHeaderAttributes(['class' => 'column-other'])->emp();
            $grid->column('order.updated_at', '操作日期')->setHeaderAttributes(['class' => 'column-updated_at']);

            $grid->filter(function (Grid\Filter $filter) {
                $filter->dateRange('order.updated_at', "时间")->datetime()->width(6)->default([
                    'start' => now()->subMonth(),
                    'end' => now()
                ]);
                $filter->equal('order.category', '费用分类')->width(3)->radio(CostOrderModel::CATEGORY);
                $filter->like('cost_order.order_no', '费用单号')->width(3);
                $filter->like('order.order_no', '结算单号')->width(3);
                if (request()->exists('order.category') && request('order.category') == StatementOrderModel::CATEGORY_CUSTOMER) {
                    $filter->equal('order.company_id', "客户名称")->width(3)->select(CustomerModel::query()->latest()->pluck('name', 'id'));
                }
                if (request()->exists('order.category') && request('order.category') == StatementOrderModel::CATEGORY_SUPPLIER) {
                    $filter->equal('order.company_id', "供应商名称")->width(3)->select(SupplierModel::query()->latest()->pluck('name', 'id'));
                }
            });
            $grid->export()->rows(function (array $rows) {
                return array_map(function ($row) {
                    return [
                        '费用单号' => $row['cost_order']['order_no'],
                        '结算单号' => $row['order']['order_no'],
                        '公司名称' => $row['order']['company_name'],
                        '费用分类' => $row['order']['category_str'],
                        '期初应付' => $row['should_amount'],
                        '本期发生额' => $row['actual_amount'],
                        '本期优惠金额' => $row['discount_amount'],
                        '结余应付' => $row['remaining_sum'],
                        '备注' => $row['order']['other'],
                        '操作日期' => $row['order']['updated_at'],
                    ];
                }, $rows);
            })->extension("xlsx");
        });
        $grid->disableCreateButton();
        $grid->disableActions();
        return $content
            ->title("结算往来帐")
            ->description(' ')
            ->full()
            ->body($grid);
    }

    public function costOrderStatistical(Content $content)
    {
        $yearMonth = (new CostOrder())->getYearMonth();
        $grid = Grid::make(new CostOrder(), function (Grid $grid) use ($yearMonth) {
            $grid->model()->resetOrderBy();
            $grid->model()->where('review_status', CostOrderModel::REVIEW_STATUS_OK);
            
            // 定义列配置（用于列选择器）
            $columnConfig = [
                ['name' => 'year_month', 'label' => '费用月份'],
                ['name' => 'category', 'label' => '费用分类'],
                ['name' => 'company_str', 'label' => '公司名称'],
                ['name' => 'total_amount', 'label' => '费用总金额'],
                ['name' => 'settlement_amount', 'label' => '已付款金额'],
                ['name' => 'discount_amount', 'label' => '已优惠金额'],
                ['name' => 'created_at', 'label' => '创建时间'],
            ];
            
            // 添加列选择器到工具栏
            $grid->tools(function ($tools) use ($columnConfig) {
                $tools->append(new ColumnSelector($columnConfig));
            });
            
            $grid->column("accountant_item.year_month", "费用月份")->setHeaderAttributes(['class' => 'column-year_month'])->emp();
            $grid->column('category', "费用分类")->setHeaderAttributes(['class' => 'column-category'])->using(CostOrderModel::CATEGORY);
            $grid->column('company_str', "公司名称")->setHeaderAttributes(['class' => 'column-company_str']);
            $grid->column('total_amount', "费用总金额")->setHeaderAttributes(['class' => 'column-total_amount'])->sortable();
            $grid->column('settlement_amount', '已付款金额')->setHeaderAttributes(['class' => 'column-settlement_amount'])->sortable();
            $grid->column('discount_amount', '已优惠金额')->setHeaderAttributes(['class' => 'column-discount_amount'])->sortable();
            $grid->column('created_at')->setHeaderAttributes(['class' => 'column-created_at'])->sortable();
            $grid->disableActions();
            $grid->disableCreateButton();

            $grid->filter(function (Grid\Filter $filter) use ($yearMonth) {
                $filter->dateRange('created_at', "时间")->datetime()->width(6)->default([
                    'start' => now()->subMonth(),
                    'end' => now()
                ]);
                $filter->equal('category', '费用分类')->width(3)->radio(CostOrderModel::CATEGORY);
                $filter->equal('accountant_item_id', "费用月份")->width(3)->select($yearMonth);

                if (request()->exists('category') && request('category') == StatementOrderModel::CATEGORY_CUSTOMER) {
                    $filter->equal('company_id', "客户名称")->width(3)->select(CustomerModel::query()->latest()->pluck('name', 'id'));
                }
                if (request()->exists('category') && request('category') == StatementOrderModel::CATEGORY_SUPPLIER) {
                    $filter->equal('company_id', "供应商名称")->width(3)->select(SupplierModel::query()->latest()->pluck('name', 'id'));
                }
            });

            $grid->export()->rows(function (array $rows) {
                return array_map(function ($row) {
                    return [
                        '费用月份' => $row['accountant_item']['year_month'],
                        '费用分类' => CostOrderModel::CATEGORY[$row['category']],
                        '公司名称' => $row['company_name'],
                        '费用总金额' => $row['total_amount'],
                        '已付款金额' => $row['settlement_amount'],
                        '已优惠金额' => $row['discount_amount'],
                        '创建时间' => $row['created_at'],
                    ];
                }, $rows);
            })->extension("xlsx");
        });
        return $content
            ->title("费用汇总")
            ->description(' ')
            ->full()
            ->body($grid);
    }

    public function unsettledCost(Content $content)
    {
        $yearMonth = (new CostOrder())->getYearMonth();
        $grid = Grid::make(new CostOrder(), function (Grid $grid) use ($yearMonth) {
            $grid->model()->resetOrderBy();
            $grid->model()->select([
                'company_id',
                'accountant_item_id',
                'category',
                DB::raw('sum(total_amount) - sum(settlement_amount) - sum(discount_amount) as sum_unsettled_amount'),
            ])->where('review_status', CostOrderModel::REVIEW_STATUS_OK)
                ->groupBy('company_id', 'category', 'accountant_item_id')
                ->having('sum_unsettled_amount', '>', 0);
            
            // 定义列配置（用于列选择器）
            $columnConfig = [
                ['name' => 'company_str', 'label' => '公司名称'],
                ['name' => 'category_str', 'label' => '费用分类'],
                ['name' => 'year_month', 'label' => '费用月份'],
                ['name' => 'sum_unsettled_amount', 'label' => '未结算金额'],
            ];
            
            // 添加列选择器到工具栏
            $grid->tools(function ($tools) use ($columnConfig) {
                $tools->append(new ColumnSelector($columnConfig));
            });
            
            $grid->column('company_str', "公司名称")->setHeaderAttributes(['class' => 'column-company_str']);
            $grid->column('category_str', "费用分类")->setHeaderAttributes(['class' => 'column-category_str']);
            $grid->column("accountant_item.year_month", "费用月份")->setHeaderAttributes(['class' => 'column-year_month'])->emp();
            $grid->column('sum_unsettled_amount', "未结算金额")->setHeaderAttributes(['class' => 'column-sum_unsettled_amount'])->sortable();

            $grid->filter(function (Grid\Filter $filter) use ($yearMonth) {
                $filter->equal('category', '费用分类')->width(4)->radio(CostOrderModel::CATEGORY);
                $filter->equal('accountant_item_id', "费用月份")->width(4)->select($yearMonth);

                if (request()->exists('category') && request('category') == StatementOrderModel::CATEGORY_CUSTOMER) {
                    $filter->equal('company_id', "客户名称")->width(4)->select(CustomerModel::query()->latest()->pluck('name', 'id'));
                }
                if (request()->exists('category') && request('category') == StatementOrderModel::CATEGORY_SUPPLIER) {
                    $filter->equal('company_id', "供应商名称")->width(4)->select(SupplierModel::query()->latest()->pluck('name', 'id'));
                }
            });

            $grid->export()->rows(function (array $rows) {
                return array_map(function ($row) {
                    return [
                        '公司名称' => $row['company_str'],
                        '费用分类' => $row['category_str'],
                        '费用月份' => $row['accountant_item']['year_month'],
                        '未结算金额' => $row['sum_unsettled_amount']
                    ];
                }, $rows);
            })->extension("xlsx");
            $grid->disableActions();
            $grid->disableCreateButton();
        });
        return $content
            ->title("未结算费用报表")
            ->description(' ')
            ->full()
            ->body($grid);
    }
}
