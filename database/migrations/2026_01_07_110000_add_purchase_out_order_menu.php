<?php

use Dcat\Admin\Models\Menu;
use Illuminate\Database\Migrations\Migration;

class AddPurchaseOutOrderMenu extends Migration
{
    public function up(): void
    {
        if (Menu::query()->where('uri', 'purchase-out-orders')->exists()) {
            return;
        }

        $parent = Menu::query()
            ->where('parent_id', 0)
            ->where('title', '采购管理')
            ->first();

        if (! $parent) {
            return;
        }

        $order = (Menu::query()->where('parent_id', $parent->id)->max('order') ?? $parent->order) + 1;

        Menu::query()->create([
            'parent_id' => $parent->id,
            'order' => $order,
            'title' => '采购退货单',
            'icon' => '',
            'uri' => 'purchase-out-orders',
        ]);

        (new Menu())->flushCache();
    }

    public function down(): void
    {
        Menu::query()->where('uri', 'purchase-out-orders')->delete();
        (new Menu())->flushCache();
    }
}
