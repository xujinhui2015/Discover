<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateTaskTableDecimalPrecision extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('task', function (Blueprint $table) {
            // 修改 plan_num 和 finish_num 字段精度为 10 位整数，2 位小数
            $table->decimal('plan_num', 10, 2)->unsigned()->default(0)->comment('计划数量')->change();
            $table->decimal('finish_num', 10, 2)->unsigned()->default(0)->comment('完成数量')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('task', function (Blueprint $table) {
            // 回滚时恢复原字段类型
            $table->unsignedDecimal('plan_num')->default(0)->comment('计划数量')->change();
            $table->unsignedDecimal('finish_num')->default(0)->comment('完成数量')->change();
        });
    }
}
