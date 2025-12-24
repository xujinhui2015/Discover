<?php

namespace App\Console\Commands;

use App\Models\PositionModel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FixDuplicatePositionCommand extends Command
{
    protected $signature = 'tool:fix-duplicate-position';

    protected $description = '清理 position 重复数据并修复关联表 position_id';

    public function handle(): int
    {
        $name = '包材仓';

        $positions = DB::table('position')
            ->where('name', $name)
            ->orderBy('id')
            ->get(['id', 'deleted_at']);

        if ($positions->count() <= 1) {
            $this->info("未发现需要处理的重复记录: {$name}");

            return Command::SUCCESS;
        }

        $keep = $positions->firstWhere('deleted_at', null) ?? $positions->first();

        if (! $keep) {
            $this->error('未找到可保留的记录。');

            return Command::FAILURE;
        }

        $duplicateIds = $positions
            ->pluck('id')
            ->reject(fn ($id) => (int) $id === (int) $keep->id)
            ->values();

        $this->info("将保留 position_id={$keep->id} (name={$name})");
        $this->line('将合并/删除的ID: '.$duplicateIds->implode(', '));

        if (! $this->confirm('确认要更新关联表并删除重复记录吗?')) {
            $this->info('已取消');

            return Command::SUCCESS;
        }

        DB::transaction(function () use ($keep, $duplicateIds) {
            $targets = [
                ['purchase_in_item', 'position_id'],
                ['sku_stock_batch', 'position_id'],
                ['sale_item', 'position_id'],
                ['sale_out_batch', 'position_id'],
                ['sale_in_item', 'position_id'],
                ['sale_out_item', 'position_id'],
                ['make_product_item', 'position_id'],
                ['init_stock_item', 'position_id'],
                ['stock_history', 'in_position_id'],
                ['stock_history', 'out_position_id'],
                ['transfer_order', 'in_position_id'],
                ['transfer_order', 'out_position_id'],
            ];

            foreach ($targets as [$table, $column]) {
                if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                    continue;
                }

                DB::table($table)
                    ->whereIn($column, $duplicateIds)
                    ->update([$column => $keep->id]);
            }

            PositionModel::query()
                ->whereIn('id', $duplicateIds)
                ->delete();
        });

        $this->info('处理完成。');

        return Command::SUCCESS;
    }
}
