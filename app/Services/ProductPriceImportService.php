<?php

/*
 * // +----------------------------------------------------------------------
 * // | erp
 * // +----------------------------------------------------------------------
 * // | Copyright (c) 2006~2020 erp All rights reserved.
 * // +----------------------------------------------------------------------
 * // | Licensed ( LICENSE-1.0.0 )
 * // +----------------------------------------------------------------------
 * // | Author: yxx <1365831278@qq.com>
 * // +----------------------------------------------------------------------
 */

namespace App\Services;

use App\Models\ProductModel;
use Dcat\EasyExcel\Excel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProductPriceImportService
{
    /**
     * 导入价格
     *
     * @param string $filePath 文件路径
     * @return array [统计信息, 错误信息列表]
     */
    public function import(string $filePath): array
    {
        $fullPath = Storage::disk('local')->path($filePath);

        $data = Excel::import($fullPath)
            ->headingRow(1)
            ->first()
            ->toArray();

        $stats = [
            'updated' => 0,
            'skipped' => 0,
        ];

        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($data as $index => $row) {
                $rowNumber = $index + 2; // Excel 行号（表头为第1行）

                // 验证必填字段
                if (empty($row['物料编号'])) {
                    $errors[] = "第{$rowNumber}行：物料编号不能为空";
                    $stats['skipped']++;
                    continue;
                }

                $itemNo = trim($row['物料编号']);

                // 查找产品
                $product = ProductModel::where('item_no', $itemNo)->first();

                if (!$product) {
                    $errors[] = "第{$rowNumber}行：物料编号 {$itemNo} 不存在";
                    $stats['skipped']++;
                    continue;
                }

                // 准备更新数据
                $updateData = [];

                // 处理销售价
                if (isset($row['销售价']) && trim($row['销售价']) !== '') {
                    $salePrice = trim($row['销售价']);
                    if (!is_numeric($salePrice) || $salePrice < 0) {
                        $errors[] = "第{$rowNumber}行：销售价格式不正确";
                        $stats['skipped']++;
                        continue;
                    }
                    $updateData['sale_price'] = $salePrice;
                }

                // 处理采购价
                if (isset($row['采购价']) && trim($row['采购价']) !== '') {
                    $purchasePrice = trim($row['采购价']);
                    if (!is_numeric($purchasePrice) || $purchasePrice < 0) {
                        $errors[] = "第{$rowNumber}行：采购价格式不正确";
                        $stats['skipped']++;
                        continue;
                    }
                    $updateData['cost_price'] = $purchasePrice;
                }

                // 如果有需要更新的数据，执行更新
                if (!empty($updateData)) {
                    $product->update($updateData);
                    $stats['updated']++;

                    Log::info("价格导入：物料编号 {$itemNo} 价格已更新", $updateData);
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return [$stats, $errors];
    }
}
