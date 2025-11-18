<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeStockFieldsToDecimal103 extends Migration
{
    public function up()
    {
        Schema::table('purchase_item', function (Blueprint $table) {
            $table->unsignedDecimal('should_num', 10, 3)->default(0)->comment('采购数量')->change();
            $table->unsignedDecimal('actual_num', 10, 3)->default(0)->comment('入库数量')->change();
        });

        Schema::table('purchase_in_item', function (Blueprint $table) {
            $table->unsignedDecimal('should_num', 10, 3)->default(0)->comment('采购数量')->change();
            $table->unsignedDecimal('actual_num', 10, 3)->default(0)->comment('入库数量')->change();
        });

        Schema::table('sku_stock', function (Blueprint $table) {
            $table->decimal('num', 10, 3)->default(0)->comment('物料库存')->change();
        });

        Schema::table('sku_stock_batch', function (Blueprint $table) {
            $table->decimal('num', 10, 3)->default(0)->comment('物料库存')->change();
        });

        Schema::table('sale_item', function (Blueprint $table) {
            $table->unsignedDecimal('should_num', 10, 3)->default(0)->comment('销售数量')->change();
            $table->unsignedDecimal('actual_num', 10, 3)->default(0)->comment('出库数量')->change();
        });

        Schema::table('sale_out_item', function (Blueprint $table) {
            $table->unsignedDecimal('should_num', 10, 3)->default(0)->comment('销售数量')->change();
            $table->unsignedDecimal('actual_num', 10, 3)->default(0)->comment('出库数量')->change();
        });

        Schema::table('sale_in_item', function (Blueprint $table) {
            $table->unsignedDecimal('should_num', 10, 3)->default(0)->comment('销售数量')->change();
            $table->unsignedDecimal('actual_num', 10, 3)->default(0)->comment('出库数量')->change();
            $table->unsignedDecimal('return_num', 10, 3)->default(0)->comment('退回数量')->change();
        });

        Schema::table('sale_out_batch', function (Blueprint $table) {
            $table->unsignedDecimal('actual_num', 10, 3)->default(0)->comment('出库数量')->change();
        });

        Schema::table('stock_history', function (Blueprint $table) {
            $table->decimal('init_num', 10, 3)->default(0)->comment('期初库存')->change();
            $table->decimal('in_num', 10, 3)->default(0)->comment('入库数量')->change();
            $table->decimal('out_num', 10, 3)->default(0)->comment('出库数量')->change();
            $table->decimal('balance_num', 10, 3)->default(0)->comment('结余库存')->change();
            $table->decimal('inventory_num', 10, 3)->default(0)->comment('盘点数量')->change();
            $table->decimal('inventory_diff_num', 10, 3)->default(0)->comment('盘点盈亏数量')->change();
        });

        Schema::table('inventory_item', function (Blueprint $table) {
            $table->decimal('should_num', 10, 3)->default(0)->comment('库存数量')->change();
            $table->decimal('actual_num', 10, 3)->default(0)->comment('实盘数量')->change();
            $table->decimal('diff_num', 10, 3)->default(0)->comment('盈亏数量')->change();
        });

        Schema::table('init_stock_item', function (Blueprint $table) {
            $table->unsignedDecimal('actual_num', 10, 3)->default(0)->comment('期初库存')->change();
        });

        Schema::table('apply_for_item', function (Blueprint $table) {
            $table->unsignedDecimal('should_num', 10, 3)->default(0)->comment('申领数量')->change();
            $table->unsignedDecimal('actual_num', 10, 3)->default(0)->comment('实领数量')->change();
        });

        Schema::table('apply_for_batch', function (Blueprint $table) {
            $table->unsignedDecimal('actual_num', 10, 3)->default(0)->comment('实领数量')->change();
        });

        Schema::table('task', function (Blueprint $table) {
            $table->unsignedDecimal('plan_num', 10, 3)->default(0)->comment('计划数量')->change();
            $table->unsignedDecimal('finish_num', 10, 3)->default(0)->comment('完成数量')->change();
        });

        Schema::table('make_product_item', function (Blueprint $table) {
            $table->unsignedDecimal('should_num', 10, 3)->default(0)->comment('计划入库数量')->change();
            $table->unsignedDecimal('actual_num', 10, 3)->default(0)->comment('实际入库数量')->change();
        });

        Schema::table('apply_for_return_item', function (Blueprint $table) {
            $table->unsignedDecimal('should_num', 10, 3)->default(0)->comment('返仓数量')->change();
        });

        Schema::table('product', function (Blueprint $table) {
            $table->unsignedDecimal('warning_num', 10, 3)->default(0)->comment('预警库存')->change();
        });
    }

    public function down()
    {
        Schema::table('purchase_item', function (Blueprint $table) {
            $table->unsignedInteger('should_num')->default(0)->comment('采购数量')->change();
            $table->unsignedInteger('actual_num')->default(0)->comment('入库数量')->change();
        });

        Schema::table('purchase_in_item', function (Blueprint $table) {
            $table->unsignedInteger('should_num')->default(0)->comment('采购数量')->change();
            $table->unsignedInteger('actual_num')->default(0)->comment('入库数量')->change();
        });

        Schema::table('sku_stock', function (Blueprint $table) {
            $table->integer('num')->default(0)->comment('物料库存')->change();
        });

        Schema::table('sku_stock_batch', function (Blueprint $table) {
            $table->integer('num')->default(0)->comment('物料库存')->change();
        });

        Schema::table('sale_item', function (Blueprint $table) {
            $table->unsignedInteger('should_num')->default(0)->comment('销售数量')->change();
            $table->unsignedInteger('actual_num')->default(0)->comment('出库数量')->change();
        });

        Schema::table('sale_out_item', function (Blueprint $table) {
            $table->unsignedInteger('should_num')->default(0)->comment('销售数量')->change();
            $table->unsignedInteger('actual_num')->default(0)->comment('出库数量')->change();
        });

        Schema::table('sale_in_item', function (Blueprint $table) {
            $table->unsignedInteger('should_num')->default(0)->comment('销售数量')->change();
            $table->unsignedInteger('actual_num')->default(0)->comment('出库数量')->change();
            $table->unsignedInteger('return_num')->default(0)->comment('退回数量')->change();
        });

        Schema::table('sale_out_batch', function (Blueprint $table) {
            $table->unsignedInteger('actual_num')->default(0)->comment('出库数量')->change();
        });

        Schema::table('stock_history', function (Blueprint $table) {
            $table->integer('init_num')->default(0)->comment('期初库存')->change();
            $table->integer('in_num')->default(0)->comment('入库数量')->change();
            $table->integer('out_num')->default(0)->comment('出库数量')->change();
            $table->integer('balance_num')->default(0)->comment('结余库存')->change();
            $table->decimal('inventory_num')->default(0)->comment('盘点数量')->change();
            $table->decimal('inventory_diff_num')->default(0)->comment('盘点盈亏数量')->change();
        });

        Schema::table('inventory_item', function (Blueprint $table) {
            $table->decimal('should_num')->default(0)->comment('库存数量')->change();
            $table->decimal('actual_num')->default(0)->comment('实盘数量')->change();
            $table->decimal('diff_num')->default(0)->comment('盈亏数量')->change();
        });

        Schema::table('init_stock_item', function (Blueprint $table) {
            $table->unsignedInteger('actual_num')->default(0)->comment('期初库存')->change();
        });

        Schema::table('apply_for_item', function (Blueprint $table) {
            $table->unsignedDecimal('should_num')->default(0)->comment('申领数量')->change();
            $table->unsignedDecimal('actual_num')->default(0)->comment('实领数量')->change();
        });

        Schema::table('apply_for_batch', function (Blueprint $table) {
            $table->unsignedDecimal('actual_num')->default(0)->comment('实领数量')->change();
        });

        Schema::table('task', function (Blueprint $table) {
            $table->unsignedDecimal('plan_num')->default(0)->comment('计划数量')->change();
            $table->unsignedDecimal('finish_num')->default(0)->comment('完成数量')->change();
        });

        Schema::table('make_product_item', function (Blueprint $table) {
            $table->unsignedDecimal('should_num')->default(0)->comment('计划入库数量')->change();
            $table->unsignedDecimal('actual_num')->default(0)->comment('实际入库数量')->change();
        });

        Schema::table('apply_for_return_item', function (Blueprint $table) {
            $table->unsignedDecimal('should_num')->default(0)->comment('返仓数量')->change();
        });

        Schema::table('product', function (Blueprint $table) {
            $table->unsignedDecimal('warning_num')->default(0)->comment('预警库存')->change();
        });
    }
}
