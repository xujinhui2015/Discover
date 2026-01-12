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

use App\Admin\Actions\Grid\BatchCreateProSave;
use App\Admin\Actions\Grid\BatchDeleteProduct;
use App\Admin\Actions\Grid\ImportProduct as ImportProductTool;
use App\Admin\Actions\Grid\ImportProductPrice;
use App\Admin\Repositories\Product;
use App\Models\AttrModel;
use App\Models\BrandModel;
use App\Models\ProductCategoryModel;
use App\Models\ProductModel;
use App\Models\UnitModel;
use App\Repositories\BrandRepository;
use App\Repositories\ProductRepository;
use App\Repositories\UnitRepository;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Grid\Tools;
use Dcat\Admin\Controllers\AdminController;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;


class ProductController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new Product(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('item_no')->emp();
            $grid->column('barcode', '专属编码')->emp();
            $grid->column('name')->emp();
//            $grid->column('py_code')->emp();
            $grid->column('type', '分类')->using(ProductModel::TYPE);
            $grid->column('brand.name', '品牌')->emp();
            $grid->column('unit.name', '单位')->emp();
            $grid->column('warning_num')->emp();
            $grid->column('sale_price')->emp();
            $grid->column('purchase_price')->emp();
//            $grid->column('created_at');
//            $grid->column('updated_at')->sortable();

            $grid->batchActions([
                new BatchDeleteProduct(),
            ]);

            $grid->tools(function (Grid\Tools $tools) {
                $tools->append(new ImportProductTool());
                $tools->append(new ImportProductPrice());
            });

            $grid->filter(function (Grid\Filter $filter) {
                $filter->like('name', '物料名称')->width(4);
                $filter->like('item_no', '物料编号')->width(4);
                $filter->equal('type', '分类')
                    ->select(ProductModel::TYPE)
                    ->width(4);
                $filter->equal('brand_id', '品牌')
                    ->select(BrandModel::query()->pluck('name', 'id'))
                    ->width(4);
            });
        });
    }

    /**
     * @return Grid
     */
    public function iFrameGrid()
    {
        return Grid::make(new Product(), function (Grid $grid) {
            $grid->setName('product_select');

            $grid->model()->whereHas('sku');
            $grid->column('id')->sortable();
            $grid->column('item_no');
            $grid->column('barcode', '专属编码')->emp();
            $grid->column('name');
//            $grid->column('py_code');
            $grid->column('type', '分类')->using(ProductModel::TYPE);
            $grid->column('brand.name', '品牌')->emp();
            $grid->column('unit.name', '单位')->emp();
//            $grid->column('created_at');
//            $grid->column('updated_at')->sortable();
            $grid->disableCreateButton();
            $grid->disableActions();

            $grid->tools(BatchCreateProSave::make());

            $grid->filter(function (Grid\Filter $filter) {
                $filter->where('keyword', function (Builder $builder) {
                    $keyword = trim((string) $this->input);
                    if ($keyword === '') {
                        return;
                    }

                    $like = "%$keyword%";

                    $builder->where(function (Builder $query) use ($like) {
                        $query->where('name', 'like', $like)
                            ->orWhere('py_code', 'like', $like)
                            ->orWhere('item_no', 'like', $like);
                    });
                }, '搜索')
                    ->placeholder('名称/拼音码/物料编号')
                    ->width(4);

                $filter->equal('type', '分类')
                    ->select(ProductModel::TYPE)
                    ->width(4);

                $filter->equal('brand_id', '品牌')
                    ->select(BrandModel::query()->pluck('name', 'id'))
                    ->width(4);

                // 属性筛选
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
                }, '属性')->width(4);
                $attrIdFilter->select(AttrModel::query()->pluck('name', 'id'))
                    ->load('product_select_attr_value_id', 'api/get-attr-value');

                // 属性值筛选
                $attrValueFilter = $filter->where('attr_value_id', function (Builder $query) {
                    $attrValueId = (string) $this->getValue();
                    if ($attrValueId === '') {
                        return;
                    }

                    $query->whereHasIn('sku', function (Builder $query) use ($attrValueId) {
                        $query->whereRaw("CONCAT(',', IFNULL(attr_value_ids, ''), ',') LIKE ?", ["%,{$attrValueId},%"]);
                    });
                }, '属性值')
                    ->width(4);
                $attrValueFilter->select([])->placeholder('请选择属性值');

                $filter->expand(false);
            });
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(new Product(['product_attr']), function (Form $form) {
            $form->row(function (Form\Row $row) use ($form) {
                $row->width(6)->text('item_no')
                    ->default(ProductRepository::buildItemNo())
                    ->creationRules([
                        Rule::unique('product', 'item_no')->whereNull('deleted_at'),
                    ])
                    ->updateRules([
                        Rule::unique('product', 'item_no')
                            ->ignore($form->getKey())
                            ->whereNull('deleted_at'),
                    ])
                    ->help('用于商家内部管理所使用的自定义编码')
                    ->required();

                $row->width(6)->text('barcode', '专属编码');


            });

            $form->row(function (Form\Row $row) {
                $row->width(6)->text('name')->required();

                $brands = BrandRepository::pluck('name', 'id');;
                $row->width(6)->select('brand_id', '品牌')
                    ->options($brands)
                    ->default(head($brands->keys()->toArray()) ?? '')
                    ->required();
            });

            $form->row(function (Form\Row $row) use ($form) {
                $row->width(6)->select('type', '分类')
                    ->options(ProductModel::TYPE)
                    ->default(ProductModel::TYPE_NOT_FINISH)
                    ->required();

                $units = UnitRepository::pluck('name', 'id');
                $row->width(6)->select('unit_id', '单位')
                    ->options($units)
                    ->default(head($units->keys()->toArray()) ?? '')
                    ->required();
            });

            $form->row(function (Form\Row $row) use ($form) {
                $row->width(6)->text('warning_num')
                    ->default(0)
                    ->help('填0则不预警')
                    ->required();
            });

            $form->row(function (Form\Row $row) use ($form) {
                $row->width(6)->text('sale_price', '销售价')
                    ->attribute('type', 'number')
                    ->attribute('step', '0.01')
                    ->attribute('min', '0')
                    ->help('非必填,用于客户要货单预填价格');

                $row->width(6)->text('purchase_price', '采购价')
                    ->attribute('type', 'number')
                    ->attribute('step', '0.01')
                    ->attribute('min', '0')
                    ->help('非必填,用于采购订购单预填价格');
            });

//            $form->row(function (Form\Row $row) use ($form) {
//                $row->width(6)->number('warning_num')
//                    ->default(0)
//                    ->help('填0则不预警')
//                    ->required();
//            });

            $form->row(function (Form\Row $row) use ($form) {
                $row->hasMany('product_attr', '', function (Form\NestedForm $table) {
                    $table->select('attr_id', '属性')->options(AttrModel::pluck('name', 'id'))->required()->load('attr_value_ids', route('api.attrvalue.find'));
                    $table->multipleSelect('attr_value_ids', '属性值')->options();
                })->width(12)->enableHorizontal()->useTable();
            });
            $form->saved(function (Form $form, $result) {
                $id = $form->getKey();
                $product = ProductModel::findOrFail($id);
                $attr = collect($product->attr_value_arr)->keys()->diff($product->sku->pluck('attr_value_ids'))->map(function (string $val) {
                    return ['attr_value_ids' => $val];
                })->values()->toArray();
                $attr && $product->sku()->createMany($attr);
            });

            $form->saving(function (Form $form) {
                $id = $form->getKey();
                if (!$id) {
                    return; // 新建时不处理
                }

                $inputs = request()->input();

                // 待删除的属性值 ID 集合
                $deletedAttrValueIds = [];

                // 1. 获取现有 product_attr 记录
                $existingAttrs = \App\Models\ProductAttrModel::where('product_id', $id)->get()->keyBy('id');
                $existingIds = $existingAttrs->keys()->toArray();

                $submittedIds = [];
                if (isset($inputs['product_attr']) && is_array($inputs['product_attr'])) {
                    foreach ($inputs['product_attr'] as $attrInput) {
                        // 检查是否被标记为 _remove_
                        if (isset($attrInput['_remove_']) && $attrInput['_remove_'] == 1) {
                            continue;
                        }

                        if (isset($attrInput['id']) && $attrInput['id']) {
                            $attrId = (int)$attrInput['id'];
                            $submittedIds[] = $attrId;

                            // 2. 处理修改的 product_attr (减少属性值)
                            if (isset($existingAttrs[$attrId])) {
                                $oldValues = $existingAttrs[$attrId]->attr_value_ids ?? [];
                                $newValues = $attrInput['attr_value_ids'] ?? [];

                                // 确保都是数组进行比较
                                if (!is_array($newValues)) {
                                    $newValues = [];
                                }

                                // 统一转为字符串比较，防止类型不一致
                                $oldValues = array_map('strval', $oldValues);
                                $newValues = array_map('strval', $newValues);

                                // 计算减少的值
                                $removedValues = array_diff($oldValues, $newValues);
                                if (!empty($removedValues)) {
                                    $deletedAttrValueIds = array_merge($deletedAttrValueIds, $removedValues);
                                }
                            }
                        }
                    }
                }

                // 计算需要完全删除的 product_attr ID
                $toDeleteAttrIds = array_diff($existingIds, $submittedIds);

                // 收集被删除的 product_attr 中的所有属性值
                if (!empty($toDeleteAttrIds)) {
                    foreach ($toDeleteAttrIds as $delId) {
                        if (isset($existingAttrs[$delId])) {
                            $vals = $existingAttrs[$delId]->attr_value_ids;
                            if (is_array($vals)) {
                                $deletedAttrValueIds = array_merge($deletedAttrValueIds, $vals);
                            }
                        }
                    }
                    // 执行 product_attr 软删除
                    \App\Models\ProductAttrModel::whereIn('id', $toDeleteAttrIds)->delete();
                }

                // 3. 删除包含这些属性值的 SKU
                if (!empty($deletedAttrValueIds)) {
                    $deletedAttrValueIds = array_unique($deletedAttrValueIds);
                    // 统一转字符串
                    $deletedAttrValueIds = array_map('strval', $deletedAttrValueIds);

                    $product = ProductModel::find($id);
                    if ($product) {
                        $skus = $product->sku; // 获取未删除的 SKU
                        $skusToDelete = [];
                        foreach ($skus as $sku) {
                            $skuAttrValueIds = explode(',', $sku->attr_value_ids);
                            // 如果 SKU 的属性值中有任何一个在已删除列表中，则该 SKU 无效
                            if (array_intersect($skuAttrValueIds, $deletedAttrValueIds)) {
                                $skusToDelete[] = $sku->id;
                            }
                        }

                        if (!empty($skusToDelete)) {
                            \App\Models\ProductSkuModel::whereIn('id', $skusToDelete)->delete();
                        }
                    }
                }
            });
        });
    }
}
