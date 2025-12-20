<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateCustomerPhoneLength extends Migration
{
    public function up()
    {
        Schema::table('customer', function (Blueprint $table) {
            $table->string('phone', 32)->default('')->comment('手机号码')->change();
        });
    }

    public function down()
    {
        Schema::table('customer', function (Blueprint $table) {
            $table->string('phone', 11)->default('')->comment('手机号码')->change();
        });
    }
}
