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

use App\Models\AttrModel;
use App\Models\AttrValueModel;
use App\Models\BrandModel;
use App\Models\ProductModel;
use App\Models\UnitModel;
use Dcat\EasyExcel\Excel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;
use Yxx\LaravelQuick\Services\BaseService;

class ProductImportService extends BaseService
{
    public const TEMPLATE_HEADERS = [
        '物料编号',
        '专属编码',
        '物料名称',
        '分类',
        '品牌',
        '单位',
        '预警库存',
        '属性定义',
        '备注',
    ];

    public const TEMPLATE_SAMPLE = [
        [
            '物料编号' => '00000001',
            '专属编码' => '6901234567890',
            '物料名称' => '示例香精',
            '分类'   => '成品',
            '品牌'   => '默认品牌',
            '单位'   => '千克',
            '预警库存' => 0,
            '属性定义' => '香型=花香型,果香型;容量=500ml,1000ml',
            '备注'   => '属性定义按“属性=值1,值2;属性2=...”填写',
        ],
    ];

    protected const REQUIRED_HEADERS = [
        '物料名称',
        '分类',
        '品牌',
        '单位',
        '属性定义',
    ];

    protected ?int $itemNoSeed = null;

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
                    $payload = $this->buildProductPayload($normalized);
                    [$product, $mode] = $this->storeProduct($payload);
                    $this->syncProductRelations($product, $payload['product_attr'], $payload['sku_rows']);
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
            throw new RuntimeException('缺少必要的表头：'.implode('、', $missing));
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

    protected function buildProductPayload(array $row): array
    {
        $name = Arr::get($row, '物料名称');
        if (! $name) {
            throw new RuntimeException('物料名称不能为空');
        }

        $itemNo = Arr::get($row, '物料编号');
        $itemNo = $itemNo ?: $this->generateItemNo();
        $barcode = Arr::get($row, '专属编码');

        $type = $this->resolveProductType(Arr::get($row, '分类'));
        $brandId = $this->resolveBrandId(Arr::get($row, '品牌'));
        $unitId = $this->resolveUnitId(Arr::get($row, '单位'));
        $warning = $this->resolveWarningNum(Arr::get($row, '预警库存'));

        $attrRows = $this->parseAttrDefinitions(Arr::get($row, '属性定义'));
        if (empty($attrRows)) {
            throw new RuntimeException('请输入属性定义，多个属性使用“;”分隔');
        }
        $skuRows = $this->buildSkuRows($attrRows);

        return [
            'item_no'      => $itemNo,
            'barcode'      => $barcode ?: '',
            'name'         => $name,
            'type'         => $type,
            'brand_id'     => $brandId,
            'unit_id'      => $unitId,
            'warning_num'  => $warning,
            'py_code'      => up_pinyin_abbr($name),
            'product_attr' => $attrRows,
            'sku_rows'     => $skuRows,
        ];
    }

    protected function generateItemNo(): string
    {
        if ($this->itemNoSeed === null) {
            $this->itemNoSeed = (int) (ProductModel::withTrashed()->max('id') ?? 0);
        }

        $this->itemNoSeed++;

        return str_pad($this->itemNoSeed, 8, '0', STR_PAD_LEFT);
    }

    protected function resolveProductType($value): int
    {
        if ($value === null || $value === '') {
            return ProductModel::TYPE_NOT_FINISH;
        }

        if (is_numeric($value)) {
            $value = (int) $value;

            if (! array_key_exists($value, ProductModel::TYPE)) {
                throw new RuntimeException('无效的分类值：'.$value);
            }

            return $value;
        }

        $value = trim((string) $value);
        $type = array_search($value, ProductModel::TYPE, true);
        if ($type === false) {
            throw new RuntimeException('无法识别的分类：'.$value);
        }

        return (int) $type;
    }

    protected function resolveBrandId(?string $name): int
    {
        if (! $name) {
            throw new RuntimeException('品牌不能为空');
        }

        /** @var BrandModel|Builder $query */
        $query = BrandModel::withTrashed();
        $brand = $query->firstOrCreate(
            ['name' => $name],
            ['created_at' => now(), 'updated_at' => now()]
        );

        if ($brand->trashed()) {
            $brand->restore();
        }

        return $brand->id;
    }

    protected function resolveUnitId(?string $name): int
    {
        if (! $name) {
            throw new RuntimeException('单位不能为空');
        }

        /** @var UnitModel|Builder $query */
        $query = UnitModel::withTrashed();
        $unit = $query->firstOrCreate(
            ['name' => $name],
            ['created_at' => now(), 'updated_at' => now()]
        );

        if ($unit->trashed()) {
            $unit->restore();
        }

        return $unit->id;
    }

    protected function resolveWarningNum($value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }

        if (! is_numeric($value)) {
            throw new RuntimeException('预警库存必须为数字');
        }

        return (int) $value;
    }

    protected function parseAttrDefinitions(?string $definition): array
    {
        if (! $definition) {
            return [];
        }

        $segments = preg_split('/[;；]/u', $definition);
        $segments = array_filter(array_map('trim', $segments));
        $result = [];

        foreach ($segments as $segment) {
            [$attrName, $values] = array_pad(explode('=', $segment, 2), 2, null);
            $attrName = $attrName ? trim($attrName) : null;
            $values = $values ? trim($values) : null;

            if (! $attrName || ! $values) {
                throw new RuntimeException('属性定义格式应为 “属性=值1,值2”');
            }

            $attr = AttrModel::withoutGlobalScope('status')
                ->withTrashed()
                ->where('name', $attrName)
                ->first();
            if (! $attr) {
                $attr = AttrModel::create(['name' => $attrName, 'status' => 1]);
            } else {
                if ($attr->trashed()) {
                    $attr->restore();
                }
                if ((int)($attr->status ?? 1) !== 1) {
                    $attr->status = 1;
                    $attr->save();
                }
            }

            $valueNames = preg_split('/[,，]/u', $values);
            $valueNames = array_filter(array_map('trim', $valueNames));
            if (empty($valueNames)) {
                throw new RuntimeException('属性【'.$attrName.'】缺少属性值');
            }

            $valueIds = $this->resolveAttrValueIds($attr->id, $valueNames);

            if (isset($result[$attr->id])) {
                $result[$attr->id]['attr_value_ids'] = array_values(array_unique(array_merge(
                    $result[$attr->id]['attr_value_ids'],
                    $valueIds
                )));
            } else {
                $result[$attr->id] = [
                    'attr_id'        => $attr->id,
                    'attr_value_ids' => $valueIds,
                ];
            }
        }

        return array_values($result);
    }

    protected function resolveAttrValueIds(int $attrId, array $valueNames): array
    {
        $ids = [];
        foreach ($valueNames as $value) {
            if (Str::contains($value, ['*', '?'])) {
                throw new RuntimeException('属性值不允许包含通配符：'.$value);
            }

            if (is_numeric($value)) {
                $attrValue = AttrValueModel::withTrashed()->where('id', $value)->first();
            } else {
                $attrValue = AttrValueModel::withTrashed()
                    ->where('attr_id', $attrId)
                    ->where('name', $value)
                    ->first();
            }

            if (! $attrValue) {
                $attrValue = AttrValueModel::withTrashed()->firstOrCreate(
                    ['attr_id' => $attrId, 'name' => $value],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }

            if ((int) $attrValue->attr_id !== $attrId) {
                throw new RuntimeException(sprintf('属性值【%s】不属于当前属性', $value));
            }

            if ($attrValue->trashed()) {
                $attrValue->restore();
            }

            $ids[] = (int) $attrValue->id;
        }

        return array_values(array_unique($ids));
    }

    protected function buildSkuRows(array $attrRows): array
    {
        $valueSets = array_map(function (array $attr) {
            return $attr['attr_value_ids'];
        }, $attrRows);

        if (empty($valueSets)) {
            return [];
        }

        $combinations = crossJoin($valueSets);
        if (empty($combinations)) {
            return [];
        }

        return collect($combinations)->map(function (array $ids) {
            return [
                'attr_value_ids' => implode(',', $ids),
            ];
        })->values()->toArray();
    }

    protected function storeProduct(array $payload): array
    {
        /** @var ProductModel|null $product */
        $product = ProductModel::withTrashed()->where('item_no', $payload['item_no'])->first();
        $mode = 'created';

        if ($product) {
            if ($product->trashed()) {
                $product->restore();
            }

            $product->fill($payload)->save();
            $mode = 'updated';

            $product->product_attr()->delete();
            $product->sku()->delete();
        } else {
            $product = ProductModel::create($payload);
        }

        return [$product, $mode];
    }

    protected function syncProductRelations(ProductModel $product, array $productAttr, array $skuRows): void
    {
        if ($productAttr) {
            $productAttr = array_map(function ($attr) {
                $attr['attr_value_ids'] = array_values(array_unique($attr['attr_value_ids']));

                return $attr;
            }, $productAttr);
            $product->product_attr()->createMany($productAttr);
        }

        if (empty($skuRows)) {
            throw new RuntimeException('无法根据属性定义生成SKU，请检查配置');
        }

        $product->sku()->createMany($skuRows);
    }
}

