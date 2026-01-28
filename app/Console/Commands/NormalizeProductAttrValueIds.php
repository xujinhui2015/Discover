<?php

namespace App\Console\Commands;

use App\Models\ProductAttrModel;
use Illuminate\Console\Command;

class NormalizeProductAttrValueIds extends Command
{
    protected $signature = 'product:normalize-attr-value-ids {--dry-run : 只统计不写入}';

    protected $description = '将 product_attr.attr_value_ids 统一为整型数组';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $updated = 0;

        ProductAttrModel::query()
            ->select(['id', 'attr_value_ids'])
            ->orderBy('id')
            ->chunkById(200, function ($rows) use ($dryRun, &$updated) {
                foreach ($rows as $row) {
                    $original = $row->attr_value_ids;
                    if (!is_array($original)) {
                        continue;
                    }

                    $normalized = array_map('intval', $original);
                    if ($original === $normalized) {
                        continue;
                    }

                    $updated++;
                    if (! $dryRun) {
                        $row->attr_value_ids = $normalized;
                        $row->save();
                    }
                }
            });

        if ($dryRun) {
            $this->info("可归一化记录数: {$updated}");
        } else {
            $this->info("已归一化记录数: {$updated}");
        }

        return self::SUCCESS;
    }
}
