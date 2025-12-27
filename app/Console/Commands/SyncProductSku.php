<?php

namespace App\Console\Commands;

use App\Models\ProductModel;
use App\Models\ProductSkuModel;
use Illuminate\Console\Command;

class SyncProductSku extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'product:sync-sku {--dry-run : 仅显示将要删除的记录，不实际执行删除}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '同步产品SKU，以product_attr为准，删除product_sku中多余的规格';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        
        if ($isDryRun) {
            $this->info('🔍 DRY RUN 模式 - 仅显示将要删除的记录，不会实际删除');
        } else {
            $this->warn('⚠️  即将开始清理product_sku表中多余的SKU记录...');
            if (!$this->confirm('确定要继续吗？')) {
                $this->info('操作已取消');
                return 0;
            }
        }

        $this->info('开始处理...');
        
        $totalDeleted = 0;
        $processedProducts = 0;
        
        // 获取所有有属性的产品
        ProductModel::with(['product_attr', 'sku'])
            ->whereHas('product_attr')
            ->chunk(100, function ($products) use (&$totalDeleted, &$processedProducts, $isDryRun) {
                foreach ($products as $product) {
                    $processedProducts++;
                    
                    // 获取product_attr中定义的所有有效的attr_value_ids组合
                    $validAttrValueIds = collect($product->attr_value_arr)->keys()->toArray();
                    
                    if (empty($validAttrValueIds)) {
                        continue;
                    }
                    
                    // 找出product_sku中不在有效列表中的记录
                    $invalidSkus = $product->sku->filter(function ($sku) use ($validAttrValueIds) {
                        return !in_array($sku->attr_value_ids, $validAttrValueIds);
                    });
                    
                    if ($invalidSkus->isEmpty()) {
                        continue;
                    }
                    
                    // 显示将要删除的SKU
                    $this->line('');
                    $this->info("产品ID: {$product->id} - {$product->name}");
                    $this->comment("  有效的属性组合: " . json_encode($validAttrValueIds, JSON_UNESCAPED_UNICODE));
                    
                    foreach ($invalidSkus as $sku) {
                        $this->warn("  将删除SKU ID: {$sku->id}, attr_value_ids: {$sku->attr_value_ids} ({$sku->attr_value_ids_str})");
                        $totalDeleted++;
                        
                        if (!$isDryRun) {
                            $sku->delete();
                        }
                    }
                }
            });
        
        $this->line('');
        $this->info('处理完成！');
        $this->table(
            ['统计项', '数量'],
            [
                ['处理的产品数', $processedProducts],
                ['删除的SKU记录数', $totalDeleted],
            ]
        );
        
        if ($isDryRun && $totalDeleted > 0) {
            $this->line('');
            $this->info('💡 提示: 使用不带 --dry-run 参数运行命令以实际执行删除操作');
            $this->comment('   php artisan product:sync-sku');
        }
        
        return 0;
    }
}
