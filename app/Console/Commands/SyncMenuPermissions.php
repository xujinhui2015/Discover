<?php

namespace App\Console\Commands;

use Dcat\Admin\Models\Menu;
use Dcat\Admin\Models\Permission;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncMenuPermissions extends Command
{
    protected $signature = 'admin:sync-menu-permissions {--dry-run : 仅检测不执行} {--force : 强制覆盖已存在的关联}';

    protected $description = '检测并补充菜单和权限的关联关系';

    public function handle()
    {
        $this->info('开始检测菜单权限关联...');
        $this->newLine();

        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        // 获取所有有 URI 的菜单
        $menus = Menu::query()
            ->whereNotNull('uri')
            ->where('uri', '!=', '')
            ->where('uri', 'not like', 'http%')
            ->orderBy('id')
            ->get();

        $this->info("找到 {$menus->count()} 个菜单项");
        $this->newLine();

        $matched = 0;
        $unmatched = 0;
        $synced = 0;
        $existed = 0;

        $unmatchedMenus = [];

        foreach ($menus as $menu) {
            // 尝试匹配权限
            $permission = $this->findPermissionForMenu($menu);

            if ($permission) {
                $matched++;

                // 检查是否已存在关联
                $exists = DB::table('admin_permission_menu')
                    ->where('menu_id', $menu->id)
                    ->where('permission_id', $permission->id)
                    ->exists();

                if ($exists && !$force) {
                    $existed++;
                    $this->line("<fg=gray>跳过: {$menu->title} ({$menu->uri}) - 已存在关联</>");
                } else {
                    if (!$dryRun) {
                        DB::table('admin_permission_menu')->updateOrInsert(
                            [
                                'menu_id' => $menu->id,
                                'permission_id' => $permission->id,
                            ],
                            [
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]
                        );
                        $synced++;
                        $this->info("✓ 已关联: {$menu->title} ({$menu->uri}) <-> {$permission->name}");
                    } else {
                        $this->comment("○ 待关联: {$menu->title} ({$menu->uri}) <-> {$permission->name}");
                        $synced++;
                    }
                }
            } else {
                $unmatched++;
                $unmatchedMenus[] = [
                    'id' => $menu->id,
                    'title' => $menu->title,
                    'uri' => $menu->uri,
                ];
                $this->warn("✗ 未找到权限: {$menu->title} ({$menu->uri})");
            }
        }

        $this->newLine();
        $this->info('===== 统计信息 =====');
        $this->line("总菜单数: {$menus->count()}");
        $this->line("已匹配: <fg=green>{$matched}</>");
        $this->line("未匹配: <fg=yellow>{$unmatched}</>");

        if (!$dryRun) {
            $this->line("新增关联: <fg=green>{$synced}</>");
            $this->line("已存在: <fg=gray>{$existed}</>");
        } else {
            $this->line("待关联: <fg=cyan>{$synced}</>");
            $this->line("已存在: <fg=gray>{$existed}</>");
        }

        if (!empty($unmatchedMenus)) {
            $this->newLine();
            $this->warn('===== 未匹配的菜单 =====');
            $this->table(
                ['ID', '菜单名称', 'URI'],
                array_map(function ($menu) {
                    return [$menu['id'], $menu['title'], $menu['uri']];
                }, $unmatchedMenus)
            );
        }

        if ($dryRun) {
            $this->newLine();
            $this->comment('这是预览模式，没有实际执行。使用不带 --dry-run 参数执行实际同步。');
        }

        $this->newLine();
        $this->info('完成！');

        return 0;
    }

    /**
     * 为菜单查找匹配的权限
     */
    protected function findPermissionForMenu(Menu $menu): ?Permission
    {
        $uri = trim($menu->uri, '/');

        // 尝试多种匹配模式
        $patterns = [
            "/{$uri}*",           // /products*
            "/{$uri}",            // /products
            "*/{$uri}*",          // */products*
            "admin/{$uri}*",      // admin/products*
        ];

        foreach ($patterns as $pattern) {
            $permission = Permission::query()
                ->where('http_path', $pattern)
                ->first();

            if ($permission) {
                return $permission;
            }
        }

        // 尝试模糊匹配
        $permission = Permission::query()
            ->where('http_path', 'like', "%{$uri}%")
            ->first();

        return $permission;
    }
}
