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

use App\Services\CustomerImportService;
use Dcat\Admin\Widgets\Form;
use Illuminate\Support\Facades\Storage;
use Throwable;

class CustomerImportForm extends Form
{
    public function handle(array $input)
    {
        if (empty($input['file'])) {
            return $this->error('请先上传导入文件');
        }

        try {
            /** @var CustomerImportService $service */
            $service = app(CustomerImportService::class);
            [$stats, $errors] = $service->import($input['file']);
        } catch (Throwable $e) {
            report($e);

            return $this->error($e->getMessage());
        } finally {
            Storage::disk('local')->delete($input['file']);
        }

        $message = sprintf(
            '导入完成：新增 %d 条，更新 %d 条，跳过 %d 条。',
            $stats['created'],
            $stats['updated'],
            $stats['skipped']
        );

        if (! empty($errors)) {
            $message .= ' 部分行导入失败：' . implode('；', array_slice($errors, 0, 5));
        }

        return $this->success($message, route('customers.index'));
    }

    public function form()
    {
        $this->file('file', '导入文件')
            ->disk('local')
            ->dir('imports/customers')
            ->autoUpload()
            ->rules('required|mimes:xlsx,xls,csv')
            ->uniqueName()
            ->required()
            ->help('文件首行需包含表头：' . implode('、', CustomerImportService::TEMPLATE_HEADERS));

        $templateUrl = route('customers.import.template');
        $tips = <<<HTML
<div class="alert alert-info mb-0">
    <p>1. 支持 xlsx/xls/csv，单个文件建议不超过 2MB；</p>
    <p>2. 付款人、客户地址支持多值，使用逗号或分号分隔；</p>
    <p>3. 付款人名称不存在时会自动创建；</p>
    <p>4. 如存在相同客户名称，将在导入时自动覆盖原数据；</p>
    <p>5. <a href="{$templateUrl}" target="_blank">下载导入模板</a></p>
</div>
HTML;

        $this->html($tips)->width(12);
        $this->disableResetButton();
    }
}
