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

namespace App\Admin\Controllers;

use App\Admin\Renderables\SkuStockBatchTable;
use App\Admin\Repositories\SkuStock;
use App\Models\AttrModel;
use App\Models\BrandModel;
use App\Models\ProductModel;
use App\Models\SkuStockModel;
use Dcat\Admin\Grid;
use Dcat\Admin\Controllers\AdminController;
use Illuminate\Database\Eloquent\Builder;

class SkuStockController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new SkuStock(['sku.product']), function (Grid $grid) {
//            $grid->model()->where('num', ">", 0);
            $grid->column('id')->sortable();
            $grid->column('sku.product.item_no', '物料编号');
            $grid->column('sku.product.name', '物料名称');
            $grid->column('sku.product.barcode', '专属编码');
            $grid->column('sku.product.unit.name', '单位');
            $grid->column('sku.product.type_str', '分类');
            $grid->column('sku.product.brand.name', '品牌');
            $grid->column('sku.attr_value_ids_str', '属性');
//            $grid->column('percent', '含绒量(%)');
            $grid->column('standard_str', '通用标准');
            $grid->column('num')->display(function ($num) {
                $color = SkuStockModel::WARNING_STATUS_COLOR[$this->warning_status];

                return "<span style='color: $color;font-weight: bold;'>$num</span>";
            });
            $grid->column('sku.product.warning_num', '预警库存')->display(function ($warningNum) {
                return $warningNum > 0 ? $warningNum : '-';
            });
            $grid->column('warning_status', '预警状态')->display(function ($warningStatus) {
                return SkuStockModel::WARNING_STATUS_STYLE[$warningStatus];
            });
            $grid->column('batch_num', '批次库存')->expand(function () {
                return SkuStockBatchTable::make([
                    'sku_id' => $this->sku_id,
//                    'percent' => $this->percent
                ]);
            });
//            $grid->column('created_at');
//            $grid->column('updated_at')->sortable();
            $grid->disableRowSelector();
            $grid->filter(function (Grid\Filter $filter) {
                $filter->where("product_name", function (Builder $query) {
                    $query->whereHasIn("sku.product", function (Builder $query) {
                        $query->where(function (Builder $query) {
                            $query->orWhere("name", "like", "%" . $this->getValue()."%");
                            $query->orWhere("py_code", "like", "%" . $this->getValue()."%");
                            $query->orWhere('item_no', 'like', "%" . $this->getValue()."%");
                        });
                    });
                }, "关键字")->placeholder("物料名称，拼音码，编号")->width(3);
                $filter->where('item_no', function (Builder $query) {
                    $value = trim((string) $this->getValue());
                    if ($value === '') {
                        return;
                    }

                    $query->whereHasIn('sku.product', function (Builder $query) use ($value) {
                        $query->where('item_no', 'like', '%' . $value . '%');
                    });
                }, '物料编号')->width(3);

                $attrIdFilter = $filter->where('attr_id', function (Builder $query) {
                    $attrId = (string) $this->getValue();
                    if ($attrId === '') {
                        return;
                    }

                    $query->whereHasIn('sku', function (Builder $query) use ($attrId) {
                        $query->whereExists(function ($query) use ($attrId) {
                            $query->selectRaw('1')
                                ->from('attr_value')
                                ->where('attr_id', $attrId)
                                ->whereRaw('FIND_IN_SET(attr_value.id, product_sku.attr_value_ids)');
                        });
                    });
                }, '属性')->width(3);
                $attrIdFilter->select(AttrModel::query()->pluck('name', 'id'))
                    ->load('attr_value_id', 'api/get-attr-value');

                $attrValueFilter = $filter->where('attr_value_id', function (Builder $query) {
                    $attrValueId = (string) $this->getValue();
                    if ($attrValueId === '') {
                        return;
                    }

                    $query->whereHasIn('sku', function (Builder $query) use ($attrValueId) {
                        $query->whereRaw("CONCAT(',', IFNULL(attr_value_ids, ''), ',') LIKE ?", ["%,{$attrValueId},%"]);
                    });
                }, '属性值')
                    ->width(3);
                $attrValueFilter->select([])->placeholder('请选择属性值');

                $filter->where('type', function (Builder $query) {
                    $query->whereHasIn('sku.product', function (Builder $query) {
                        $query->where('type', $this->getValue());
                    });
                }, '分类')
                    ->select(ProductModel::TYPE)
                    ->width(3);

                $filter->where('brand_id', function (Builder $query) {
                    $query->whereHasIn('sku.product', function (Builder $query) {
                        $query->where('brand_id', $this->getValue());
                    });
                }, '品牌')
                    ->select(BrandModel::query()->pluck('name', 'id'))
                    ->width(3);

//                $filter->group('num', function ($group) {
//                    $group->gt('大于');
//                    $group->lt('小于');
//                    $group->nlt('不小于');
//                    $group->ngt('不大于');
//                    $group->equal('等于');
//                })->width(3);
//                $filter->like('percent', "含绒量")->decimal()->width(3);
                $filter
                    ->where('warning_status', function (Builder $query) {
                        $query->warningStatus($this->input);
                    }, '预警状态')
                    ->select(SkuStockModel::WARNING_STATUS)
                    ->width(3);

            });

            $grid->export()->rows(function (array $rows) {
                return array_map(function ($row) {
                    $product = $row['sku']['product'] ?? [];
                    $unit = $product['unit'] ?? [];
                    $brand = $product['brand'] ?? [];
                    $warningNum = $product['warning_num'] ?? 0;
                    $warningStatus = $row['warning_status_str']
                        ?? SkuStockModel::WARNING_STATUS[$row['warning_status']];

                    return [
                        '物料编号' => $product['item_no'] ?? '',
                        '物料名称' => $product['name'] ?? '',
                        '专属编码' => $product['barcode'] ?? '',
                        '单位' => $unit['name'] ?? '',
                        '分类' => $product['type_str'] ?? '',
                        '品牌' => $brand['name'] ?? '',
                        '属性' => $row['sku']['attr_value_ids_str'] ?? '',
                        '通用标准' => $row['standard_str'] ?? '',
                        '库存数量' => $row['num'] ?? 0,
                        '预警库存' => $warningNum > 0 ? $warningNum : '-',
                        '预警状态' => $warningStatus,
                    ];
                }, $rows);
            })->extension('xlsx');

            $grid->disableActions();
            $grid->disableCreateButton();
        });
    }
}
