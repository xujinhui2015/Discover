<?php

use Dcat\Admin\Models\Permission;
use Dcat\Admin\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Str;

class AddTransferOrderPermission extends Migration
{
    public function up(): void
    {
        $parent = Permission::query()->firstWhere('name', '库存管理');
        $permission = Permission::query()->firstOrNew(['slug' => 'transfer-orders']);

        $permission->forceFill([
            'name' => '库存调拨',
            'slug' => (string)Str::uuid(),
            'http_method' => '',
            'http_path' => '/transfer-orders*',
            'parent_id' => $parent->id,
            'order' => (Permission::max('order') ?? 0) + 1,
        ]);
        $permission->save();

        $role = Role::query()->where('slug', Role::ADMINISTRATOR)->first();
        if ($role) {
            $role->permissions()->syncWithoutDetaching([$permission->id]);
        }
    }

    public function down(): void
    {
        $permission = Permission::query()->where('slug', 'transfer-orders')->first();
        if (! $permission) {
            return;
        }

        $role = Role::query()->where('slug', Role::ADMINISTRATOR)->first();
        if ($role) {
            $role->permissions()->detach($permission->id);
        }

        $permission->delete();
    }
}
