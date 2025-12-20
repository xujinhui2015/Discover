<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateCustomerOtherNullable extends Migration
{
    public function up()
    {
        Schema::table('customer', function (Blueprint $table) {
            $table->string('other', 500)->nullable()->comment('备注')->change();
        });
    }

    public function down()
    {
        Schema::table('customer', function (Blueprint $table) {
            $table->string('other', 500)->default('')->comment('备注')->change();
        });
    }
}
