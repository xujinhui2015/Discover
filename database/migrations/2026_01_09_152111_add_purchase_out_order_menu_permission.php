<?php

use Dcat\Admin\Models\Permission;
use Dcat\Admin\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Str;

class AddPurchaseOutOrderMenuPermission extends Migration
{
    public function up(): void
    {
        $parent = Permission::query()->where('name', '采购管理')->first();

        if (!$parent) {
            return;
        }

        $maxOrder = Permission::query()
            ->where('parent_id', $parent->id)
            ->max('order');

        $order = ($maxOrder ?? $parent->order) + 1;

        $permission = Permission::query()->firstOrNew([
            'slug' => Str::uuid()->toString(),
        ]);

        if (!$permission->exists) {
            $permission->forceFill([
                'name' => '采购退货单',
                'http_method' => null,
                'http_path' => '/purchase-out-orders*',
                'parent_id' => $parent->id,
                'order' => $order,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $permission->save();

            $adminRole = Role::query()->where('slug', Role::ADMINISTRATOR)->first();
            if ($adminRole) {
                $adminRole->permissions()->syncWithoutDetaching([$permission->id]);
            }
        }
    }

    public function down(): void
    {
        Permission::query()
            ->where('http_path', '/purchase-out-orders*')
            ->where('parent_id', function ($query) {
                $query->select('id')
                    ->from('admin_permissions')
                    ->where('name', '采购管理')
                    ->limit(1);
            })
            ->delete();
    }
}
