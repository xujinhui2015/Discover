<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropShouldNumFromScrapItem extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasColumn('scrap_item', 'should_num')) {
            Schema::table('scrap_item', function (Blueprint $table) {
                $table->dropColumn('should_num');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (! Schema::hasColumn('scrap_item', 'should_num')) {
            Schema::table('scrap_item', function (Blueprint $table) {
                $table->unsignedDecimal('should_num', 10, 2)
                    ->default(0)
                    ->comment('报废数量')
                    ->after('standard');
            });
        }
    }
}
