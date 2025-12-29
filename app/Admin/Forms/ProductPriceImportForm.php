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

namespace App\Admin\Forms;

use App\Services\ProductPriceImportService;
use Dcat\Admin\Widgets\Form;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProductPriceImportForm extends Form
{
    public function handle(array $input)
    {
        if (empty($input['file'])) {
            return $this->error('请先上传导入文件');
        }

        try {
            /** @var ProductPriceImportService $service */
            $service = app(ProductPriceImportService::class);
            [$stats, $errors] = $service->import($input['file']);
        } catch (Throwable $e) {
            report($e);

            return $this->error($e->getMessage());
        } finally {
            Storage::disk('local')->delete($input['file']);
        }

        $message = sprintf(
            '导入完成：更新 %d 条，跳过 %d 条（物料编号不存在）。',
            $stats['updated'],
            $stats['skipped']
        );

        if (!empty($errors)) {
            $message .= ' 部分行导入失败：' . implode('；', array_slice($errors, 0, 5));
        }

        return $this->success($message, route('products.index'));
    }

    public function form()
    {
        $this->file('file', '导入文件')
            ->disk('local')
            ->dir('imports/product-prices')
            ->autoUpload()
            ->rules('required|mimes:xlsx,xls,csv')
            ->uniqueName()
            ->required()
            ->help('文件首行需包含表头：物料编号、销售价、采购价');

        $templateUrl = route('products.price.import.template');
        $tips = <<<HTML
<div class="alert alert-info mb-0">
    <p>1. 支持 xlsx/xls/csv，单个文件建议不超过 2MB；</p>
    <p>2. 表头必须包含：<strong>物料编号</strong>、<strong>销售价</strong>、<strong>采购价</strong>；</p>
    <p>3. 销售价和采购价可选填，未填写的字段将不会更新；</p>
    <p>4. 系统会根据物料编号自动匹配产品并更新价格；</p>
    <p>5. 物料编号不存在的行将被跳过；</p>
    <p>6. <a href="{$templateUrl}" target="_blank">下载导入模板</a></p>
</div>
HTML;

        $this->html($tips)->width(12);
        $this->disableResetButton();
    }
}
