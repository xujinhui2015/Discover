<?php

use Dcat\Admin\Models\Permission;
use Dcat\Admin\Models\Role;
use Illuminate\Database\Migrations\Migration;

class AddTransferOrderReviewPermission extends Migration
{
    public function up(): void
    {
        $parent = Permission::query()->firstOrNew(['slug' => 'order-review']);
        $isParentNew = ! $parent->exists;

        if ($isParentNew) {
            $parent->forceFill([
                'name' => '单据审核',
                'http_method' => '',
                'http_path' => '',
                'parent_id' => 0,
                'order' => (Permission::max('order') ?? 0) + 1,
            ]);
            $parent->save();
        }

        $slug = order_review_permission_slug('TransferOrder');
        $permission = Permission::query()->firstOrNew(['slug' => $slug]);
        $isNew = ! $permission->exists;

        if ($isNew) {
            $order = (Permission::query()->where('parent_id', $parent->id)->max('order') ?? $parent->order) + 1;

            $permission->forceFill([
                'name' => '库存调拨审核',
                'http_method' => 'POST',
                'http_path' => '',
                'parent_id' => $parent->id,
                'order' => $order,
            ]);
            $permission->save();
        }

        $role = Role::query()->where('slug', Role::ADMINISTRATOR)->first();
        if ($role) {
            $role->permissions()->syncWithoutDetaching([$parent->id, $permission->id]);
        }
    }

    public function down(): void
    {
        $slug = order_review_permission_slug('TransferOrder');
        $permission = Permission::query()->where('slug', $slug)->first();

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

