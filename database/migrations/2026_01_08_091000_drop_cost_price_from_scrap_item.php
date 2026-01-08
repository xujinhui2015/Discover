<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropCostPriceFromScrapItem extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('scrap_item', function (Blueprint $table) {
            $table->dropColumn('cost_price');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('scrap_item', function (Blueprint $table) {
            $table->unsignedDecimal('cost_price', 10, 2)
                ->default(0)
                ->comment('成本总价')
                ->after('actual_num');
        });
    }
}
