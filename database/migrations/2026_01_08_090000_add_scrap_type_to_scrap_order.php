<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddScrapTypeToScrapOrder extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('scrap_order', function (Blueprint $table) {
            $table->unsignedTinyInteger('scrap_type')
                ->default(1)
                ->comment('报废类型')
                ->after('apply_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('scrap_order', function (Blueprint $table) {
            $table->dropColumn('scrap_type');
        });
    }
}
