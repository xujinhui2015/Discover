<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLuxuryBrandSeriesAndProductImageToProductTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('product', function (Blueprint $table) {
            $table->string('luxury_brand_series', 200)->nullable()->comment('对应大牌系列，如：香奈儿-粉邂逅')->after('brand_id');
            $table->string('product_image', 500)->nullable()->comment('产品图片路径')->after('luxury_brand_series');
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
            $table->dropColumn(['luxury_brand_series', 'product_image']);
        });
    }
}
