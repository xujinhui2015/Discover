<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ClearTableCommand extends Command
{
    protected $signature = 'tool:clear-table';

    protected $description = '清理表数据';

    public function handle(): void
    {
        if (! $this->confirm('确认要清理表数据吗?')) {
            $this->info('已取消');

            return;
        }

        if (! $this->confirm('再次确认要清理表数据吗?')) {
            $this->info('已取消');

            return;
        }
        DB::table('accountant_date')->truncate();
        DB::table('accountant_date_item')->truncate();
        DB::table('apply_for_batch')->truncate();
        DB::table('apply_for_item')->truncate();
        DB::table('apply_for_return_item')->truncate();
        DB::table('apply_for_return_order')->truncate();
        DB::table('attr')->truncate();
        DB::table('attr_value')->truncate();
        DB::table('brand')->truncate();
        DB::table('check_product')->truncate();
        DB::table('cost_item')->truncate();
        DB::table('cost_order')->truncate();
        DB::table('craft')->truncate();
        DB::table('customer')->truncate();
        DB::table('customer_address')->truncate();
        DB::table('customer_drawee')->truncate();
        DB::table('demand')->truncate();
        DB::table('drawee')->truncate();
        DB::table('init_stock_item')->truncate();
        DB::table('init_stock_order')->truncate();
        DB::table('inventory')->truncate();
        DB::table('inventory_item')->truncate();
        DB::table('inventory_order')->truncate();
        DB::table('make_product_item')->truncate();
        DB::table('make_product_order')->truncate();
        DB::table('order_no_generator')->truncate();
        DB::table('position')->truncate();
        DB::table('product')->truncate();
        DB::table('product_attr')->truncate();
        DB::table('product_category')->truncate();
        DB::table('product_sku')->truncate();
        DB::table('purchase_check')->truncate();
        DB::table('purchase_in_item')->truncate();
        DB::table('purchase_in_order')->truncate();
        DB::table('purchase_item')->truncate();
        DB::table('purchase_order')->truncate();
        DB::table('purchase_order_amount')->truncate();
        DB::table('sale_in_item')->truncate();
        DB::table('sale_in_order')->truncate();
        DB::table('sale_item')->truncate();
        DB::table('sale_order')->truncate();
        DB::table('sale_order_amount')->truncate();
        DB::table('sale_out_batch')->truncate();
        DB::table('sale_out_item')->truncate();
        DB::table('sale_out_order')->truncate();
        DB::table('sku_stock')->truncate();
        DB::table('sku_stock_batch')->truncate();
        DB::table('statement_item')->truncate();
        DB::table('statement_order')->truncate();
        DB::table('stock_history')->truncate();
        DB::table('supplier')->truncate();
        DB::table('task')->truncate();
        DB::table('unit')->truncate();


    }
}
