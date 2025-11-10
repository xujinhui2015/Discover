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

use App\Admin\Metrics\OrderStatus;
use App\Admin\Metrics\PurchaseTrend;
use App\Admin\Metrics\SaleTrend;
use App\Admin\Metrics\StockWarning;
use App\Admin\Metrics\TotalPurchase;
use App\Admin\Metrics\TotalPurchaseAmount;
use App\Admin\Metrics\TotalSaleAmount;
use App\Admin\Metrics\TotalSaleOrder;
use App\Http\Controllers\Controller;
use Dcat\Admin\Layout\Column;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Layout\Row;

class HomeController extends Controller
{
    public function index(Content $content)
    {
        return $content
            ->header('仪表盘')
            ->body(function (Row $row) {
                // 第一行：核心指标卡片
                $row->column(3, function (Column $column) {
                    $column->row(TotalSaleOrder::make());
                });
                
                $row->column(3, function (Column $column) {
                    $column->row(TotalSaleAmount::make());
                });
                
                $row->column(3, function (Column $column) {
                    $column->row(TotalPurchase::make());
                });
                
                $row->column(3, function (Column $column) {
                    $column->row(TotalPurchaseAmount::make());
                });

                // 第二行：库存预警和订单状态
                $row->column(6, function (Column $column) {
                    $column->row(StockWarning::make());
                });
                
                $row->column(6, function (Column $column) {
                    $column->row(OrderStatus::make());
                });

                // 第三行：趋势图表
                $row->column(6, function (Column $column) {
                    $column->row(SaleTrend::make());
                });
                
                $row->column(6, function (Column $column) {
                    $column->row(PurchaseTrend::make());
                });
            });
    }
}
