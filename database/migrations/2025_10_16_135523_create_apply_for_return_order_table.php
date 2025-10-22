<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateApplyForReturnOrderTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('apply_for_return_order', function (Blueprint $table) {
            $table->id();

            $table->unsignedInteger('apply_for_order_id')->index()->default(0)->comment('关联物料申领单id');
            $table->string('order_no')->default('')->unique()->comment('单号');
            $table->unsignedInteger('user_id')->default(0)->comment('创建人');
            $table->unsignedInteger('apply_id')->default(0)->comment('审核人');
            $table->text('other')->nullable()->comment('备注');
            $table->unsignedTinyInteger('review_status')->default(0)->comment('状态');

            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('apply_for_return_item', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('order_id')->index()->default(0)->comment('关联单据');
            $table->unsignedInteger('sku_id')->default(0)->comment('商品的skuId');
            $table->unsignedTinyInteger('standard')->default(0)->comment('检验标准');
            $table->unsignedDecimal('should_num')->default(0.00)->comment('返仓数量');
            $table->softDeletes();
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
        Schema::dropIfExists('apply_for_return_order');
    }
}
