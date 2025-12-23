<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePersonalConfigTable extends Migration
{
    public function up(): void
    {
        Schema::create('personal_config', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('user_id')->index();
            $table->string('config_key', 100);
            $table->text('config_value')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'config_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personal_config');
    }
}
