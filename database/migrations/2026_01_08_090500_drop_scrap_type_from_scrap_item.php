<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropScrapTypeFromScrapItem extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('scrap_item', function (Blueprint $table) {
            $table->dropColumn('scrap_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('scrap_item', function (Blueprint $table) {
            $table->unsignedTinyInteger('scrap_type')
                ->default(1)
                ->comment('报废类型')
                ->after('standard');
        });
    }
}
