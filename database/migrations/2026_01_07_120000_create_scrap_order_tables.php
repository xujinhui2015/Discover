<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateScrapOrderTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('scrap_order', function (Blueprint $table) {
            $table->id();
            $table->string('order_no')->default('')->unique()->comment('单号');
            $table->unsignedBigInteger('user_id')->default(0)->comment('创建人');
            $table->unsignedBigInteger('apply_id')->default(0)->comment('审核人');
            $table->text('other')->nullable()->comment('备注');
            $table->unsignedTinyInteger('review_status')->default(0)->comment('单据状态');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('scrap_item', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->index()->default(0)->comment('关联单据');
            $table->unsignedBigInteger('sku_id')->default(0)->comment('商品的skuId');
            $table->unsignedTinyInteger('standard')->default(0)->comment('通用标准');
            $table->unsignedTinyInteger('scrap_type')->default(1)->comment('报废类型');
            $table->unsignedDecimal('should_num', 10, 2)->default(0)->comment('报废数量');
            $table->unsignedDecimal('actual_num', 10, 2)->default(0)->comment('报废数量');
            $table->timestamps();
        });

        Schema::create('scrap_batch', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('item_id')->default(0)->comment('订单明细id');
            $table->unsignedBigInteger('sku_id')->default(0)->comment('SKU ID');
            $table->unsignedDecimal('actual_num', 10, 2)->default(0)->comment('报废数量');
            $table->unsignedBigInteger('stock_batch_id')->default(0)->comment('报废批次库存id');
            $table->unsignedTinyInteger('standard')->default(0)->comment('通用标准');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('scrap_batch');
        Schema::dropIfExists('scrap_item');
        Schema::dropIfExists('scrap_order');
    }
}
