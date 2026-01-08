<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddScrapOrderMenu extends Migration
{
    public function up(): void
    {
        $table = config('admin.database.menu_table', 'admin_menu');
        $createdAt = date('Y-m-d H:i:s');

        $data = [
            'parent_id' => 16,
            'order' => 22,
            'title' => '物料报废单',
            'icon' => '',
            'uri' => 'scrap-orders',
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ];

        if (Schema::hasColumn($table, 'extension')) {
            $data['extension'] = '';
        }

        if (Schema::hasColumn($table, 'show')) {
            $data['show'] = 1;
        }

        $exists = DB::table($table)
            ->where('parent_id', 16)
            ->where('uri', 'scrap-orders')
            ->exists();

        if (! $exists) {
            DB::table($table)->insert($data);
        }
    }

    public function down(): void
    {
        $table = config('admin.database.menu_table', 'admin_menu');

        DB::table($table)
            ->where('parent_id', 16)
            ->where('uri', 'scrap-orders')
            ->delete();
    }
}
