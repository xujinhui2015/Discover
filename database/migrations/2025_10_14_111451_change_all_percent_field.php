<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeAllPercentField extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $tables = [
            'check_product',
            'purchase_in_item',
            'purchase_item',
            'sku_stock_batch',
            'stock_history',
            'sku_stock',
            'sale_in_item',
            'sale_out_item',
            'sale_item',
            'sale_out_batch',
            'task',
            'apply_for_item',
            'apply_for_batch',
            'make_product_item',
            'init_stock_item',
        ];
        foreach ($tables as $table) {
            $this->settingField($table);
        }

    }

    private function settingField($table): void
    {
        Schema::table($table, function (Blueprint $table) {
            $table->decimal('percent', 10, 2)->default(0)->nullable()->comment('含绒量')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
