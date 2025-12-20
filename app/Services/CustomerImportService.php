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

use App\Models\CustomerAddressModel;
use App\Models\CustomerModel;
use App\Models\DraweeModel;
use Dcat\EasyExcel\Excel;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;
use Yxx\LaravelQuick\Services\BaseService;

class CustomerImportService extends BaseService
{
    public const TEMPLATE_HEADERS = [
        '客户名称',
        '联系人',
        '手机号码',
        '付款方式',
        '付款人',
        '客户地址',
        '备注',
    ];

    public const TEMPLATE_SAMPLE = [
        [
            '客户名称' => '示例客户',
            '联系人'   => '张三',
            '手机号码' => '13800001111',
            '付款方式' => '月结',
            '付款人'   => '示例付款人A,示例付款人B',
            '客户地址' => '示例地址1；示例地址2',
            '备注'     => '付款人、地址支持多值',
        ],
    ];

    protected const REQUIRED_HEADERS = [
        '客户名称',
        '联系人',
    ];

    public function import(string $relativePath): array
    {
        $disk = Storage::disk('local');
        if (! $disk->exists($relativePath)) {
            throw new RuntimeException('导入文件不存在');
        }

        $fullPath = $disk->path($relativePath);

        $sheet = Excel::import($fullPath)->headingRow(1)->first();
        if (! $sheet->valid()) {
            throw new RuntimeException('Excel文件为空或无法读取');
        }

        $headings = $this->normalizeHeadings($sheet->getOriginalHeadings());
        $this->validateHeaders($headings);

        $stats = [
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
        ];
        $errors = [];

        $rows = $sheet->toArray();
        foreach ($rows as $line => $row) {
            $normalized = $this->normalizeRow($row);

            if ($this->isRowEmpty($normalized)) {
                $stats['skipped']++;
                continue;
            }

            try {
                DB::transaction(function () use ($normalized, &$stats) {
                    $payload = $this->buildCustomerPayload($normalized);
                    [$customer, $mode] = $this->storeCustomer($payload);
                    $this->syncCustomerRelations($customer, $payload);
                    $stats[$mode]++;
                });
            } catch (Throwable $e) {
                $errors[] = sprintf('第 %s 行：%s', $line, $e->getMessage());
            }
        }

        return [$stats, $errors];
    }

    protected function validateHeaders(array $headings): void
    {
        $missing = array_diff(self::REQUIRED_HEADERS, $headings);
        if ($missing) {
            throw new RuntimeException('缺少必要的表头：' . implode('、', $missing));
        }
    }

    protected function normalizeHeadings(array $headings): array
    {
        return array_values(array_filter(array_map(function ($heading) {
            return trim((string) $heading);
        }, $headings)));
    }

    protected function normalizeRow(array $row): array
    {
        $normalized = [];
        foreach ($row as $key => $value) {
            $normalized[trim((string) $key)] = is_string($value) ? trim($value) : $value;
        }

        return $normalized;
    }

    protected function isRowEmpty(array $row): bool
    {
        $values = array_filter($row, function ($value) {
            return $value !== null && $value !== '';
        });

        return empty($values);
    }

    protected function buildCustomerPayload(array $row): array
    {
        $name = Arr::get($row, '客户名称');
        if (! $name) {
            throw new RuntimeException('客户名称不能为空');
        }

        $link = Arr::get($row, '联系人');
        if (! $link) {
            throw new RuntimeException('联系人不能为空');
        }

        $payMethod = $this->resolvePayMethod(Arr::get($row, '付款方式'));
        $phone = $this->normalizePhone(Arr::get($row, '手机号码'));
        $other = trim((string) Arr::get($row, '备注', ''));

        $draweeValue = Arr::get($row, '付款人');
        $addressValue = Arr::get($row, '客户地址');

        return [
            'name'              => trim((string) $name),
            'link'              => trim((string) $link),
            'phone'             => $phone,
            'other'             => $other,
            'pay_method'        => $payMethod,
            'drawee_names'      => $this->parseList($draweeValue),
            'address_list'      => $this->parseList($addressValue),
            'has_drawee_input'  => $this->hasInput($draweeValue),
            'has_address_input' => $this->hasInput($addressValue),
        ];
    }

    protected function resolvePayMethod($value): int
    {
        if ($value === null || $value === '') {
            return CustomerModel::PAY_CASH;
        }

        if (is_numeric($value)) {
            $value = (int) $value;
            if (! array_key_exists($value, CustomerModel::PAY)) {
                throw new RuntimeException('无效的付款方式：' . $value);
            }

            return $value;
        }

        $value = trim((string) $value);
        $method = array_search($value, CustomerModel::PAY, true);
        if ($method === false) {
            throw new RuntimeException('无法识别的付款方式：' . $value);
        }

        return (int) $method;
    }

    protected function normalizePhone($value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $phone = trim((string) $value);
        if ($phone === '') {
            return '';
        }

        if (is_numeric($value)) {
            $phone = preg_replace('/\.0$/', '', $phone);
        }

        return $phone;
    }

    protected function parseList($value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        $text = trim((string) $value);
        if ($text === '') {
            return [];
        }

        $items = preg_split('/[;,，；]/u', $text);
        $items = array_filter(array_map('trim', $items));

        return array_values(array_unique($items));
    }

    protected function hasInput($value): bool
    {
        if ($value === null) {
            return false;
        }

        return trim((string) $value) !== '';
    }

    protected function storeCustomer(array $payload): array
    {
        $data = Arr::only($payload, ['name', 'link', 'phone', 'other', 'pay_method']);

        /** @var CustomerModel|null $customer */
        $customer = CustomerModel::withTrashed()
            ->where('name', $payload['name'])
            ->first();

        $mode = 'created';

        if ($customer) {
            if ($customer->trashed()) {
                $customer->restore();
            }

            $customer->fill($data)->save();
            $mode = 'updated';
        } else {
            $customer = CustomerModel::create($data);
        }

        return [$customer, $mode];
    }

    protected function syncCustomerRelations(CustomerModel $customer, array $payload): void
    {
        if ($payload['has_drawee_input']) {
            $draweeIds = $this->resolveDraweeIds($payload['drawee_names']);
            $customer->drawee()->sync($draweeIds);
        }

        if ($payload['has_address_input']) {
            $customer->address()->delete();
            $rows = $this->buildAddressRows($payload['address_list']);
            if ($rows) {
                $customer->address()->createMany($rows);
            }
        }
    }

    protected function resolveDraweeIds(array $names): array
    {
        $ids = [];
        foreach ($names as $name) {
            if ($name === '') {
                continue;
            }

            $drawee = DraweeModel::firstOrCreate(
                ['name' => $name],
                ['created_at' => now(), 'updated_at' => now()]
            );

            $ids[] = (int) $drawee->id;
        }

        return array_values(array_unique($ids));
    }

    protected function buildAddressRows(array $addresses): array
    {
        $rows = [];
        foreach ($addresses as $address) {
            if ($address === '') {
                continue;
            }

            $rows[] = [
                'address' => $address,
                'other'   => '',
            ];
        }

        return $rows;
    }
}
