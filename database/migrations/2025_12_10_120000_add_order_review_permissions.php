<?php

use Dcat\Admin\Models\Permission;
use Dcat\Admin\Models\Role;
use Dcat\Admin\Support\Helper;
use Illuminate\Database\Migrations\Migration;

class AddOrderReviewPermissions extends Migration
{
    protected array $orderControllers = [
        'ApplyForOrder',
        'ApplyForReturnOrder',
        'CostOrder',
        'InitStockOrder',
        'InventoryOrder',
        'MakeProductOrder',
        'PurchaseInOrder',
        'PurchaseOrder',
        'SaleInOrder',
        'SaleOrder',
        'SaleOutOrder',
        'StatementOrder',
        'TransferOrder',
    ];

    public function up(): void
    {
        $parent = $this->upsertParentPermission();
        $permissionIds = [];

        foreach ($this->orderControllers as $index => $controller) {
            $permission = $this->savePermission(
                order_review_permission_slug($controller),
                [
                    'name' => $this->buildPermissionName($controller),
                    'http_method' => 'POST',
                    'http_path' => '',
                    'parent_id' => $parent->id,
                    'order' => $parent->order + $index + 1,
                ]
            );

            $permissionIds[] = $permission->id;
        }

        $this->attachToAdministrator($permissionIds, $parent->id);
    }

    protected function upsertParentPermission(): Permission
    {
        $order = Permission::max('order') ?? 0;

        return $this->savePermission(
            'order-review',
            [
                'name' => '单据审核',
                'http_method' => '',
                'http_path' => '',
                'parent_id' => 0,
                'order' => $order + 1,
            ]
        );
    }

    protected function savePermission(string $slug, array $data): Permission
    {
        $permission = Permission::query()->firstOrNew(['slug' => $slug]);
        $permission->forceFill($data);
        $permission->save();

        return $permission;
    }

    protected function buildPermissionName(string $controller): string
    {
        $slug = Helper::slug($controller);
        $labelKey = "{$slug}.labels.{$controller}";
        $label = trans($labelKey);

        if (! is_string($label) || $label === $labelKey) {
            $label = $controller;
        }

        return $label . '审核';
    }

    protected function attachToAdministrator(array $permissionIds, int $parentId): void
    {
        $role = Role::query()->where('slug', Role::ADMINISTRATOR)->first();

        if (! $role) {
            return;
        }

        $role->permissions()->syncWithoutDetaching(array_merge($permissionIds, [$parentId]));
    }

    public function down(): void
    {
        $slugs = array_map(fn (string $controller): string => order_review_permission_slug($controller), $this->orderControllers);
        $slugs[] = 'order-review';

        Permission::query()->whereIn('slug', $slugs)->delete();
    }
}
