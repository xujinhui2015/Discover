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

use App\Admin\Actions\Grid\BatchStockSelectSave;
use App\Admin\Actions\Grid\ProductCheck;
use App\Admin\Extensions\Grid\ProductCheckDetails;
use App\Admin\Repositories\SkuStockBatch;
use App\Models\AttrModel;
use App\Models\BrandModel;
use App\Models\PositionModel;
use App\Models\ProductModel;
use App\Models\SkuStockBatchModel;
use Dcat\Admin\Grid;
use Dcat\Admin\Controllers\AdminController;
use Illuminate\Database\Eloquent\Builder;

class SkuStockBatchController extends AdminController
{
    public $title = "批次库存";

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new SkuStockBatch(['sku.product']), function (Grid $grid) {
//            $grid->model()->where('num', ">", 0);
            $grid->column('id')->sortable();
            $grid->column('sku.product.item_no', '物料编号');
            $grid->column('sku.product.name', '物料名称');
            $grid->column('sku.product.unit.name', '单位');
            $grid->column('sku.product.type_str', '分类');
            $grid->column('sku.product.brand.name', '品牌');
            $grid->column('sku.attr_value_ids_str', '属性');
//            $grid->column('percent', '含绒量(%)');
            $grid->column('standard_str', '通用标准');
            $grid->column('batch_no');
            $grid->column('num');
            $grid->column('cost_price', "成本价格");
            $grid->column("cost_price_total", "合计成本")->display(function () {
                return bcmul($this->num, $this->cost_price, 2);
            });
            $grid->column('position.name', '库位');

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
                $filter->group('num', function ($group) {
                    $group->gt('大于');
                    $group->lt('小于');
                    $group->nlt('不小于');
                    $group->ngt('不大于');
                    $group->equal('等于');
                })->width(3);
//                $filter->like('percent', "含绒量")->decimal()->width(3);
                $filter->equal('standard', "通用标准")->select(SkuStockBatchModel::STANDARD)->width(3);
                $filter->like('batch_no', "批次号")->width(3);
                $filter->equal('position_id', "库位")->select(PositionModel::query()->latest()->pluck('name', 'id'))->width(3);
            });
            $grid->column("_id", "检验记录")->expand(ProductCheckDetails::make());
            $grid->actions(function (Grid\Displayers\Actions $actions) {
                if ($this->num > 0) {
                    $actions->append(new ProductCheck());
                }
                return $actions;
            });
            $grid->disableRowSelector();
            $grid->disableQuickEditButton();
            // $grid->disableActions();
            $grid->disableCreateButton();
        });
    }

    protected function iFrameGrid()
    {
        return Grid::make(new SkuStockBatch(['sku.product']), function (Grid $grid) {
            if (request()->get('table') === 'InventoryOrder') {
                $grid->model()->orderBy('id', 'desc');
            } else {
                $grid->model()->where([
                    'sku_id' => request()->input('sku_id'),
                    'standard' => request()->input('standard'),
//                    'percent' => request()->input('percent'),
                ])->where('num', ">", 0)->orderBy('id', 'desc');
            }
            $grid->column('id')->sortable();
            $grid->column('sku.product.item_no', '物料编号');
            $grid->column('sku.product.name', '物料名称');
            $grid->column('sku.product.unit.name', '单位');
            $grid->column('sku.product.type_str', '分类');
            $grid->column('sku.product.brand.name', '品牌');
            $grid->column('sku.attr_value_ids_str', '属性');
            $grid->column('standard', '通用标准');
//            $grid->column('percent', '含绒量（%）');
            $grid->column('batch_no');
            $grid->column('num');
            $grid->column('position.name', '库位');

            $grid->filter(function (Grid\Filter $filter) {
                $filter->where('keyword', function (Builder $query) {
                    $value = $this->getValue();
                    $query->whereHasIn('sku.product', function (Builder $query) use ($value) {
                        $query->where(function (Builder $query) use ($value) {
                            $query->orWhere('name', 'like', "%" . $value . '%');
                            $query->orWhere('item_no', 'like', "%" . $value . '%');
                        });
                    })->orWhere('batch_no', 'like', "%" . $value . '%');
                }, '搜索')->placeholder('物料名称/编号/批次号')->width(3);

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
                    ->multipleSelect(BrandModel::query()->pluck('name', 'id'))
                    ->width(3);
            });
            $grid->tools(BatchStockSelectSave::make());
            $grid->disableActions();
            $grid->disableCreateButton();
        });
    }
}
