<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSalePriceAndPurchasePriceToProductTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('product', function (Blueprint $table) {
            $table->decimal('sale_price', 10, 2)->nullable()->comment('销售价')->after('warning_num');
            $table->decimal('purchase_price', 10, 2)->nullable()->comment('采购价')->after('sale_price');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('product', function (Blueprint $table) {
            $table->dropColumn(['sale_price', 'purchase_price']);
        });
    }
}
