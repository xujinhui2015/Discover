<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddTransferOrderMenu extends Migration
{
    public function up(): void
    {
        $table = config('admin.database.menu_table', 'admin_menu');

        $data = [
            'parent_id' => 16,
            'order' => 21,
            'title' => '库存调拨',
            'icon' => '',
            'uri' => 'transfer-orders',
            'created_at' => '2025-12-09 10:06:52',
            'updated_at' => '2025-12-09 10:06:52',
        ];

        if (Schema::hasColumn($table, 'extension')) {
            $data['extension'] = '';
        }

        if (Schema::hasColumn($table, 'show')) {
            $data['show'] = 1;
        }

        $exists = DB::table($table)
            ->where('parent_id', 16)
            ->where('uri', 'transfer-orders')
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
            ->where('uri', 'transfer-orders')
            ->delete();
    }
}

