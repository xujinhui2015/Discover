<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurchaseOutOrderTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('purchase_out_order', function (Blueprint $table) {
            $table->integerIncrements('id');
            $table->string('order_no')->default('')->unique()->comment('订单单号');
            $table->unsignedInteger('supplier_id')->default(0)->comment('供应商id');
            $table->unsignedTinyInteger('status')->default(0)->comment('单据状态');
            $table->string('other')->default('')->comment('备注');
            $table->unsignedInteger('user_id')->default(0)->comment('创建订单用户');
            $table->timestamp('finished_at')->nullable()->comment('订单完成时间');
            $table->unsignedInteger('with_id')->default(0)->comment('相关单据id');
            $table->unsignedTinyInteger('review_status')->default(0)->comment('审核状态');
            $table->timestamp('apply_at')->nullable()->comment('审核时间');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('purchase_out_item', function (Blueprint $table) {
            $table->integerIncrements('id');
            $table->unsignedInteger('order_id')->default(0)->comment('订单id');
            $table->unsignedInteger('sku_id')->default(0)->comment('采购商品的skuid');
            $table->unsignedDecimal('should_num', 10, 2)->default(0)->comment('入库数量');
            $table->unsignedDecimal('actual_num', 10, 2)->default(0)->comment('退货数量');
            $table->decimal('price', 10, 2)->default(0)->comment('价格');
            $table->unsignedInteger('position_id')->default(0)->comment('出库位置');
            $table->string('batch_no', 32)->default('')->comment('批次号');
            $table->decimal('percent', 10, 2)->default(0)->comment('含绒量');
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
        Schema::dropIfExists('purchase_out_item');
        Schema::dropIfExists('purchase_out_order');
    }
}
