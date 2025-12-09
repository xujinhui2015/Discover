<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransferOrderTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transfer_order', function (Blueprint $table) {
            $table->id();
            $table->string('order_no')->unique()->comment('单号');
            $table->tinyInteger('review_status')->default(0)->comment('状态:0待审核,1已审核');
            $table->unsignedBigInteger('user_id')->comment('创建用户');
            $table->unsignedBigInteger('audit_user_id')->nullable()->comment('审核用户');
            $table->timestamp('apply_at')->nullable()->comment('业务日期');
            $table->timestamp('finished_at')->nullable()->comment('完成时间');
            $table->string('other')->nullable()->comment('备注');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('transfer_item', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->comment('订单ID');
            $table->unsignedBigInteger('sku_id')->comment('SKU ID');
            $table->decimal('num', 10, 3)->default(0)->comment('数量');
            $table->unsignedBigInteger('out_position_id')->comment('调出仓库');
            $table->unsignedBigInteger('in_position_id')->comment('调入仓库');
            $table->string('batch_no')->comment('批次号');
            $table->string('percent')->nullable()->comment('含绒量');
            $table->tinyInteger('standard')->default(0)->comment('检验标准');
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
        Schema::dropIfExists('transfer_item');
        Schema::dropIfExists('transfer_order');
    }
}
