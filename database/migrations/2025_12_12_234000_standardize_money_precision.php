<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class StandardizeMoneyPrecision extends Migration
{
    public function up()
    {
        // 先做数据舍入，避免 strict 模式下 ALTER 触发截断报错
        DB::statement("UPDATE `purchase_item` SET `price` = ROUND(`price`, 2) WHERE `price` IS NOT NULL");
        DB::statement("UPDATE `purchase_in_item` SET `price` = ROUND(`price`, 2) WHERE `price` IS NOT NULL");
        DB::statement("UPDATE `sale_item` SET `price` = ROUND(`price`, 2) WHERE `price` IS NOT NULL");
        DB::statement("UPDATE `sale_out_item` SET `price` = ROUND(`price`, 2) WHERE `price` IS NOT NULL");
        DB::statement("UPDATE `sale_in_item` SET `price` = ROUND(`price`, 2) WHERE `price` IS NOT NULL");

        DB::statement(
            "UPDATE `stock_history` SET
                `cost_price` = ROUND(`cost_price`, 2),
                `in_price` = ROUND(`in_price`, 2),
                `out_price` = ROUND(`out_price`, 2)"
        );

        DB::statement("UPDATE `sku_stock_batch` SET `cost_price` = ROUND(`cost_price`, 2) WHERE `cost_price` IS NOT NULL");
        DB::statement("UPDATE `sale_out_batch` SET `cost_price` = ROUND(`cost_price`, 2) WHERE `cost_price` IS NOT NULL");
        DB::statement(
            "UPDATE `sale_out_item` SET
                `sum_cost_price` = ROUND(`sum_cost_price`, 2),
                `sum_price` = ROUND(`sum_price`, 2)"
        );

        DB::statement("UPDATE `apply_for_item` SET `cost_price` = ROUND(`cost_price`, 2) WHERE `cost_price` IS NOT NULL");
        DB::statement("UPDATE `inventory_item` SET `cost_price` = ROUND(`cost_price`, 2) WHERE `cost_price` IS NOT NULL");
        DB::statement(
            "UPDATE `make_product_item` SET
                `cost_price` = ROUND(`cost_price`, 2),
                `sum_cost_price` = ROUND(`sum_cost_price`, 2)"
        );
        DB::statement("UPDATE `init_stock_item` SET `cost_price` = ROUND(`cost_price`, 2) WHERE `cost_price` IS NOT NULL");

        DB::statement(
            "UPDATE `purchase_order_amount` SET
                `should_amount` = ROUND(`should_amount`, 2),
                `actual_amount` = ROUND(`actual_amount`, 2)"
        );
        DB::statement(
            "UPDATE `sale_order_amount` SET
                `should_amount` = ROUND(`should_amount`, 2),
                `actual_amount` = ROUND(`actual_amount`, 2)"
        );
        DB::statement(
            "UPDATE `cost_order` SET
                `total_amount` = ROUND(`total_amount`, 2),
                `settlement_amount` = ROUND(`settlement_amount`, 2),
                `discount_amount` = ROUND(`discount_amount`, 2)"
        );
        DB::statement(
            "UPDATE `cost_item` SET
                `should_amount` = ROUND(`should_amount`, 2),
                `actual_amount` = ROUND(`actual_amount`, 2)"
        );
        DB::statement(
            "UPDATE `statement_order` SET
                `should_amount` = ROUND(`should_amount`, 2),
                `actual_amount` = ROUND(`actual_amount`, 2),
                `discount_amount` = ROUND(`discount_amount`, 2)"
        );
        DB::statement(
            "UPDATE `statement_item` SET
                `order_amount` = ROUND(`order_amount`, 2),
                `should_amount` = ROUND(`should_amount`, 2),
                `actual_amount` = ROUND(`actual_amount`, 2),
                `discount_amount` = ROUND(`discount_amount`, 2),
                `already_actual_amount` = ROUND(`already_actual_amount`, 2),
                `already_discount_amount` = ROUND(`already_discount_amount`, 2)"
        );

        // 单价/成本
        DB::statement("ALTER TABLE `purchase_item` MODIFY `price` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '价格'");
        DB::statement("ALTER TABLE `purchase_in_item` MODIFY `price` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '价格'");
        DB::statement("ALTER TABLE `sale_item` MODIFY `price` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '价格'");
        DB::statement("ALTER TABLE `sale_out_item` MODIFY `price` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '价格'");
        DB::statement("ALTER TABLE `sale_in_item` MODIFY `price` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '价格'");

        DB::statement(
            "ALTER TABLE `stock_history`
                MODIFY `cost_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '成本价格',
                MODIFY `in_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '入库价格',
                MODIFY `out_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '出库价格'"
        );

        DB::statement(
            "ALTER TABLE `sku_stock_batch`
                MODIFY `cost_price` DECIMAL(12,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '成本价'"
        );

        DB::statement(
            "ALTER TABLE `sale_out_batch`
                MODIFY `cost_price` DECIMAL(12,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '成本单价'"
        );

        DB::statement(
            "ALTER TABLE `sale_out_item`
                MODIFY `sum_cost_price` DECIMAL(12,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '成本单价',
                MODIFY `sum_price` DECIMAL(12,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '销售总价格'"
        );

        DB::statement(
            "ALTER TABLE `apply_for_item`
                MODIFY `cost_price` DECIMAL(12,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '成本价格'"
        );

        DB::statement(
            "ALTER TABLE `inventory_item`
                MODIFY `cost_price` DECIMAL(12,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '成本单价'"
        );

        DB::statement(
            "ALTER TABLE `make_product_item`
                MODIFY `cost_price` DECIMAL(12,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '成本价格',
                MODIFY `sum_cost_price` DECIMAL(12,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '成本总价格'"
        );

        DB::statement(
            "ALTER TABLE `init_stock_item`
                MODIFY `cost_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '成本价格'"
        );

        // 应收/应付/结算类金额
        DB::statement(
            "ALTER TABLE `purchase_order_amount`
                MODIFY `should_amount` DECIMAL(12,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '费用金额',
                MODIFY `actual_amount` DECIMAL(12,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '结算金额'"
        );

        DB::statement(
            "ALTER TABLE `sale_order_amount`
                MODIFY `should_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '费用金额',
                MODIFY `actual_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '结算金额'"
        );

        DB::statement(
            "ALTER TABLE `cost_order`
                MODIFY `total_amount` DECIMAL(12,2) UNSIGNED NOT NULL DEFAULT 0.00,
                MODIFY `settlement_amount` DECIMAL(12,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '结算实付金额',
                MODIFY `discount_amount` DECIMAL(12,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '已优惠金额'"
        );

        DB::statement(
            "ALTER TABLE `cost_item`
                MODIFY `should_amount` DECIMAL(12,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '应付金额',
                MODIFY `actual_amount` DECIMAL(12,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '实付金额'"
        );

        DB::statement(
            "ALTER TABLE `statement_order`
                MODIFY `should_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '应付金额',
                MODIFY `actual_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '实付金额',
                MODIFY `discount_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT '优惠金额'"
        );

        DB::statement(
            "ALTER TABLE `statement_item`
                MODIFY `order_amount` DECIMAL(12,2) UNSIGNED NOT NULL DEFAULT 0.00,
                MODIFY `should_amount` DECIMAL(12,2) UNSIGNED NOT NULL DEFAULT 0.00,
                MODIFY `actual_amount` DECIMAL(12,2) UNSIGNED NOT NULL DEFAULT 0.00,
                MODIFY `discount_amount` DECIMAL(12,2) UNSIGNED NOT NULL DEFAULT 0.00,
                MODIFY `already_actual_amount` DECIMAL(12,2) UNSIGNED NOT NULL DEFAULT 0.00,
                MODIFY `already_discount_amount` DECIMAL(12,2) UNSIGNED NOT NULL DEFAULT 0.00"
        );
    }

    public function down()
    {
        DB::statement("ALTER TABLE `purchase_item` MODIFY `price` DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT '价格'");
        DB::statement("ALTER TABLE `purchase_in_item` MODIFY `price` DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT '价格'");
        DB::statement("ALTER TABLE `sale_item` MODIFY `price` DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT '价格'");
        DB::statement("ALTER TABLE `sale_out_item` MODIFY `price` DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT '价格'");
        DB::statement("ALTER TABLE `sale_in_item` MODIFY `price` DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT '价格'");

        DB::statement(
            "ALTER TABLE `stock_history`
                MODIFY `cost_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '成本价格',
                MODIFY `in_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '入库价格',
                MODIFY `out_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '出库价格'"
        );

        DB::statement(
            "ALTER TABLE `sku_stock_batch`
                MODIFY `cost_price` DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '成本价'"
        );

        DB::statement(
            "ALTER TABLE `sale_out_batch`
                MODIFY `cost_price` DECIMAL(8,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '成本单价'"
        );

        DB::statement(
            "ALTER TABLE `sale_out_item`
                MODIFY `sum_cost_price` DECIMAL(8,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '成本单价',
                MODIFY `sum_price` DECIMAL(8,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '销售总价格'"
        );

        DB::statement(
            "ALTER TABLE `apply_for_item`
                MODIFY `cost_price` DECIMAL(8,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '成本价格'"
        );

        DB::statement(
            "ALTER TABLE `inventory_item`
                MODIFY `cost_price` DECIMAL(8,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '成本单价'"
        );

        DB::statement(
            "ALTER TABLE `make_product_item`
                MODIFY `cost_price` DECIMAL(8,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '成本价格',
                MODIFY `sum_cost_price` DECIMAL(8,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '成本总价格'"
        );

        DB::statement(
            "ALTER TABLE `init_stock_item`
                MODIFY `cost_price` DECIMAL(8,2) NOT NULL DEFAULT 0.00 COMMENT '成本价格'"
        );

        DB::statement(
            "ALTER TABLE `purchase_order_amount`
                MODIFY `should_amount` DECIMAL(8,2) UNSIGNED NOT NULL DEFAULT 0 COMMENT '费用金额',
                MODIFY `actual_amount` DECIMAL(8,2) UNSIGNED NOT NULL DEFAULT 0 COMMENT '结算金额'"
        );

        DB::statement(
            "ALTER TABLE `sale_order_amount`
                MODIFY `should_amount` DECIMAL(8,2) UNSIGNED NOT NULL DEFAULT 0 COMMENT '费用金额',
                MODIFY `actual_amount` DECIMAL(8,2) UNSIGNED NOT NULL DEFAULT 0 COMMENT '结算金额'"
        );

        DB::statement(
            "ALTER TABLE `cost_order`
                MODIFY `total_amount` DECIMAL(8,2) UNSIGNED NOT NULL DEFAULT 0.00,
                MODIFY `settlement_amount` DECIMAL(8,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '结算实付金额',
                MODIFY `discount_amount` DECIMAL(8,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '已优惠金额'"
        );

        DB::statement(
            "ALTER TABLE `cost_item`
                MODIFY `should_amount` DECIMAL(8,2) UNSIGNED NOT NULL DEFAULT 0 COMMENT '应付金额',
                MODIFY `actual_amount` DECIMAL(8,2) UNSIGNED NOT NULL DEFAULT 0 COMMENT '实付金额'"
        );

        DB::statement(
            "ALTER TABLE `statement_order`
                MODIFY `should_amount` DECIMAL(8,2) NOT NULL DEFAULT 0 COMMENT '应付金额',
                MODIFY `actual_amount` DECIMAL(8,2) NOT NULL DEFAULT 0 COMMENT '实付金额',
                MODIFY `discount_amount` DECIMAL(8,2) NOT NULL DEFAULT 0 COMMENT '优惠金额'"
        );

        DB::statement(
            "ALTER TABLE `statement_item`
                MODIFY `order_amount` DECIMAL(8,2) UNSIGNED NOT NULL DEFAULT 0,
                MODIFY `should_amount` DECIMAL(8,2) UNSIGNED NOT NULL DEFAULT 0,
                MODIFY `actual_amount` DECIMAL(8,2) UNSIGNED NOT NULL DEFAULT 0,
                MODIFY `discount_amount` DECIMAL(8,2) UNSIGNED NOT NULL DEFAULT 0,
                MODIFY `already_actual_amount` DECIMAL(8,2) UNSIGNED NOT NULL DEFAULT 0,
                MODIFY `already_discount_amount` DECIMAL(8,2) UNSIGNED NOT NULL DEFAULT 0"
        );
    }
}
