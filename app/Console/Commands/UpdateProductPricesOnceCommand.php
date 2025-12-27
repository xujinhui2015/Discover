<?php

namespace App\Console\Commands;

use App\Models\ProductModel;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateProductPricesOnceCommand extends Command
{
    protected $signature = 'product:update-prices-once {--dry-run : 模拟运行，不实际更新}';
    protected $description = '一次性批量更新产品销售价（从storage/price文件夹的Excel数据）';

    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        
        if ($isDryRun) {
            $this->warn('【模拟模式】不会实际更新数据库');
        }

        // 所有需要更新的数据：物料编号 => 销售价
        $priceData = [
            'EA-021A' => 8.3,
            '2385A' => 8.8,
            '2385B' => 7.3,
            '2089A' => 8.3,
            '2089B' => 6.3,
            '2203' => 6.3,
            '2203B' => 4,
            '2203C' => 6.3,
            '2203D' => 4,
            '2261' => 6.5,
            '878A' => 15.8,
            '878B' => 11.8,
            '878C' => 7.8,
            'EA-008A' => 5.3,
            'EA-2812' => 13.8,
            'EA-2921' => 6.8,
            'EA-2922' => 6.8,
            'EA-91A' => 7.8,
            'EA-21304' => 8.9,
            'EA-307' => 9.9,
            'EA-1613' => 16.9,
            'EA-1614' => 16.9,
            'EA-1701' => 7.8,
            'EA-1801' => 9.9,
            'EA-1802' => 9.9,
            'EA-1803' => 9.9,
            'EA-1804' => 9.9,
            'EA-1805' => 9.9,
            'EA-1806' => 9.9,
            'EA-1807' => 14.8,
            'EA-1808(A)' => 7.3,
            'EA-1808(B)' => 7.3,
            'EA-1808(C)' => 7.3,
            'EA-1809' => 13.8,
            'EB-1811' => 12.3,
            'EA-1812（A）' => 11,
            'EA-1812（B）' => 11,
            'EA-1814' => 9.8,
            'EA-1816(A)' => 10.8,
            'EA-1816(B)' => 13.8,
            'EA-1816(C)' => 15.8,
            'EA-1819' => 9.8,
            'EA-1817' => 11.3,
            'EA-21801（A）' => 13.8,
            'EA-21801（B）' => 13.8,
            'EA-21801（C）' => 13.8,
            'EA-21804（A）' => 12.8,
            'EA-21804（B）' => 12.8,
            'EA-21804（C）' => 12.8,
            'EA-21806（A）' => 12.8,
            'EA-21806（B）' => 12.8,
            'EA-21806（C）' => 12.8,
            'EA-21802（A）' => 12.8,
            'EA-21802（B）' => 12.8,
            'EA-21802（C）' => 12.8,
            'EA-21802（D）' => 12.8,
            'EA-21805' => 8.8,
            'EA-21803(A)' => 13.8,
            'EA-21803(B)' => 13.8,
            'EA-21803(C)' => 13.8,
            'EA-21803(D)' => 13.8,
            'EA-21807' => 10.8,
            'EA-21808（A）' => 12.8,
            'EA-21808（B）' => 12.8,
            'EA-1820' => 7.8,
            'EA-23801A' => 9.8,
            'EA-23801B' => 9.8,
            'EA-23802A' => 10,
            'EA-23802B' => 10,
            'EA-23806' => 14.8,
            'EA-23805A' => 11.8,
            'EA-23805B' => 11.8,
            'EA-23803A' => 12.8,
            'EA-23803B' => 12.8,
            'EA-1815（A）' => 6.8,
            'EA-1815（B）' => 6.8,
            'EA-243001' => 10.8,
            'OMJ-21203' => 14.8,
            'OMJ-21212B' => 14.8,
            'OMJ-21213B' => 14.8,
            'OMJ-802A' => 5.3,
            'OMJ-802B' => 5.3,
            'OMJ-806A' => 8.8,
            'OMJ-810A' => 5.8,
            'OMJ-810B' => 5.8,
            'OMJ-910A' => 18.8,
            'OMJ-910B' => 18.8,
            'OMJ-21315' => 13.8,
            'OMJ-21318' => 18.8,
            'OMJ-111' => 8.9,
            'OMJ-112' => 8.9,
            'OMJ-1601' => 13.9,
            'OMJ-1602' => 13.9,
            'OMJ-1603' => 13.9,
            'OMJ-1604' => 13.9,
            'OMJ-3804(A)' => 11.8,
            'OMJ-3804(B)' => 11.8,
            'OMJ-3810' => 11.8,
            'OMJ-3806(A)' => 24.8,
            'OMJ-3806(B)' => 24.8,
            'OMJ-3806(C)' => 24.8,
            'OMJ-3807(A)' => 14.8,
            'OMJ-3807(B)' => 14.8,
            'OMJ-3807(C)' => 14.8,
            'OMJ-3807(D)' => 14.8,
            'OMJ-1902' => 14.8,
            'OMJ-21307' => 13.8,
            'OMJ-21308' => 11.8,
            'OMJ-21307（B）' => 13.8,
            'OMJ-21307（C）' => 13.8,
            'OMJ-21307（D）' => 13.8,
            'OMJ-8909（A）' => 27.8,
            'OMJ-8909（B）' => 27.8,
            'OMJ-8909（C）' => 27.8,
            'OMJ-3811（A）' => 13.8,
            'OMJ-3811（B）' => 13.8,
            'OMJ-3811（C）' => 13.8,
            'OMJ-3811（D）' => 13.8,
            'OMJ-21705（A）' => 13.8,
            'OMJ-21705（B）' => 13.8,
            'OMJ-21705（C）' => 13.8,
            'OMJ-21702（A）' => 13.8,
            'OMJ-21702（B）' => 15.8,
            'OMJ-21702（C）' => 13.8,
            'OMJ-21702（D）' => 15.8,
            'OMJ-21702（E）' => 13.8,
            'OMJ-21702（F）' => 15.8,
            'OMJ-21703(A)' => 13.8,
            'OMJ-21703(B)' => 13.8,
            'OMJ-21703(C)' => 13.8,
            'OMJ-21703(D)' => 13.8,
            'OMJ-21704(A)' => 13.8,
            'OMJ-21704(B)' => 13.8,
            'OMJ-21704(C)' => 13.8,
            'OMJ-21708(A)' => 12.8,
            'OMJ-21701（A）' => 17.3,
            'OMJ-21701（B）' => 14.8,
            'OMJ-21701（C）' => 17.3,
            'OMJ-21701（D）' => 14.8,
            'OMJ-21706' => 14.3,
            'OMJ-23805（A)' => 15.8,
            'OMJ-23805（B)' => 15.8,
            'OMJ-23805（C)' => 15.8,
            'OMJ-23805（D)' => 15.8,
            'OMJ-23804A' => 12.8,
            'OMJ-23804B' => 12.8,
            'OMJ-23804C' => 12.8,
            'OMJ-23804D' => 12.8,
            'OMJ-23806A' => 9.8,
            'OMJ-23806B' => 9.8,
            'OMJ-23806C' => 9.8,
            'OMJ-23803B' => 12.8,
            'OMJ-23803C' => 12.8,
            'OMJ-23802A' => 8.8,
            'OMJ-23802B' => 8.8,
            'OMJ-23802C' => 8.8,
            'OMJ-23807A' => 11.8,
            'OMJ-23807B' => 11.8,
            'OMJ-23807C' => 11.8,
            'OMJ-23801A' => 8.8,
            'OMJ-23801B' => 8.8,
            'OMJ-24011' => 12.8,
            'OMJ-24010（B)' => 12.8,
            'OMJ-25101(A)' => 15.8,
            'OMJ-25101(B)' => 15.8,
            'OMJ-25101(C)' => 15.8,
            'EB-2905A' => 13.8,
            'EB-2905B' => 11.8,
            'EB-2905C' => 13.8,
            'EB-2905D' => 11.8,
            'EB-2908' => 5.3,
            'EB-21311' => 16.8,
            'EB-21312' => 13.8,
            'EB-1623' => 8.8,
            'EB-1624' => 7.8,
            'EB-2703' => 25.8,
            'EB-2705' => 16.9,
            'EB-2706' => 16.9,
            'EB-2801' => 11.8,
            'EB-2802' => 11.8,
            'EB-2803' => 11.8,
            'EB-2804' => 11.8,
            'EB-2805' => 11.8,
            'EB-2806' => 11.8,
            'EB-2807(A)' => 8.8,
            'EB-2807(B)' => 8.8,
            'EB-2807(C)' => 8.8,
            'EB-2807(D)' => 8.8,
            'EB-2809' => 7.8,
            'EB-2808（A）' => 15.9,
            'EB-2808（B）' => 15.9,
            'EB-2808（C）' => 15.9,
            'EB-8907（A）' => 29.8,
            'EB-8907（B）' => 29.8,
            'EB-2816(A)' => 25.8,
            'EB-2816(B)' => 25.8,
            'EB-2816(C)' => 25.8,
            'EB-21603（A）' => 13.8,
            'EB-21603（B）' => 13.8,
            'EB-21603（C）' => 13.8,
            'EB-21602(C)' => 7.3,
            'EB-21604(A)' => 9.8,
            'EB-21604(B)' => 9.8,
            'EB-21604(C)' => 9.8,
            'EB-21601（A）' => 16.8,
            'EB-21601（B）' => 14.8,
            'EB-23102A' => 7.8,
            'EB-23102B' => 7.8,
            'EB-23104' => 11.8,
            'DKL-2804（A）' => 6.8,
            'DKL-2804（B）' => 8.8,
            'DKL-2801' => 9.8,
            'DKL-2802' => 11.8,
            'DKL-23909A' => 11.8,
            'DKL-21909B' => 11.8,
            'DKL-23905A' => 12.8,
            'DKL-21905B' => 12.8,
            'DKL-21905C' => 12.8,
            'DKL-23901A' => 14.8,
            'DKL-21901B' => 14.8,
            'DKL-23908（A）' => 12.8,
            'DKL-23908（B）' => 12.8,
            'DKL-23903（A）' => 15.8,
            'DKL-23903（B）' => 15.8,
            'DKL-23903（C）' => 15.8,
            'DKL-23904（A）' => 14.8,
            'DKL-23904（B）' => 14.8,
            'DKL-23904（C）' => 14.8,
            'DKL-23904（D）' => 14.8,
            'DKL-24003' => 14.8,
            'DKL-24001A' => 18.9,
            'DKL-24001B' => 18.9,
            'DKL-24001C' => 18.9,
            'DKL-24002' => 13.8,
        ];

        $this->info("准备更新 " . count($priceData) . " 个产品的销售价格\n");

        $stats = [
            'success' => 0,
            'not_found' => 0,
            'skipped' => 0,
        ];

        $notFoundItems = [];

        DB::beginTransaction();
        
        try {
            foreach ($priceData as $itemNo => $salePrice) {
                $product = ProductModel::where('item_no', $itemNo)->first();

                if (!$product) {
                    $this->warn("✗ 未找到物料: {$itemNo}");
                    $stats['not_found']++;
                    $notFoundItems[] = $itemNo;
                    continue;
                }

                // 检查价格是否相同
                if ($product->sale_price == $salePrice) {
                    $this->line("○ {$itemNo}: 价格未变化 ({$salePrice})，跳过");
                    $stats['skipped']++;
                    continue;
                }

                if ($isDryRun) {
                    $this->info("✓ [模拟] {$itemNo}: {$product->sale_price} → {$salePrice}");
                } else {
                    $oldPrice = $product->sale_price;
                    $product->sale_price = $salePrice;
                    $product->save();
                    $this->info("✓ {$itemNo}: {$oldPrice} → {$salePrice}");
                }
                
                $stats['success']++;
            }

            if ($isDryRun) {
                DB::rollBack();
                $this->warn("\n【模拟模式】所有更改已回滚");
            } else {
                DB::commit();
                $this->info("\n所有更改已提交");
            }

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("\n更新失败: " . $e->getMessage());
            return 1;
        }

        $this->info("\n==================== 统计 ====================");
        $this->info("总计: " . count($priceData));
        $this->info("成功: {$stats['success']}");
        $this->info("跳过(价格未变): {$stats['skipped']}");
        $this->info("未找到: {$stats['not_found']}");

        if (!empty($notFoundItems)) {
            $this->warn("\n未找到的物料编号:");
            foreach ($notFoundItems as $item) {
                $this->warn("  - {$item}");
            }
        }

        if ($isDryRun) {
            $this->comment("\n如需实际更新，请运行: php artisan product:update-prices-once");
        }

        return 0;
    }
}
