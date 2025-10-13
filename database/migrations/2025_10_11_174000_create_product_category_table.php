<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductCategoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_category', function (Blueprint $table) {
            $table->id();

            $table->integer('parent_id')->unsigned()->default(0);
            $table->integer('order')->unsigned()->default(0);
            $table->string('title');
            $table->tinyInteger('status')->default(1)->comment('状态1开启0关闭');

            $table->softDeletes();
            $table->timestamps();
        });

        Schema::table('product', function (Blueprint $table) {
            $table->integer('product_category_id')->unsigned()->default(0);
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_category');
    }
}
