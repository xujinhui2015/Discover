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
use App\Admin\Extensions\Grid\ColumnSelector;
use App\Admin\Repositories\Product;
use App\Models\AttrModel;
use App\Models\BrandModel;
use App\Models\ProductCategoryModel;
use App\Models\ProductModel;
use App\Models\UnitModel;
use App\Repositories\BrandRepository;
use App\Repositories\ProductRepository;
use App\Repositories\UnitRepository;
use Dcat\Admin\Admin;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Grid\Tools;
use Dcat\Admin\Controllers\AdminController;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
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
            // 定义列配置（用于列选择器）
            $columnConfig = [
                ['name' => 'id', 'label' => 'ID'],
                ['name' => 'item_no', 'label' => '物料编号'],
                ['name' => 'barcode', 'label' => '专属编码'],
                ['name' => 'name', 'label' => '物料名称'],
                ['name' => 'type', 'label' => '分类'],
                ['name' => 'brand', 'label' => '品牌'],
                ['name' => 'luxury_brand_series', 'label' => '对应大牌'],
                ['name' => 'product_image', 'label' => '产品图片'],
                ['name' => 'unit', 'label' => '单位'],
                ['name' => 'warning_num', 'label' => '预警库存'],
                ['name' => 'sale_price', 'label' => '销售价'],
                ['name' => 'purchase_price', 'label' => '采购价'],
            ];
            
            $grid->column('id')->setHeaderAttributes(['class' => 'column-id'])->sortable();
            $grid->column('item_no')->setHeaderAttributes(['class' => 'column-item_no'])->emp();
            $grid->column('barcode', '专属编码')
                ->setHeaderAttributes(['class' => 'column-barcode'])
                ->display(function ($value) {
                    $value = (string) $value;
                    $value = trim($value);
                    if ($value === '') {
                        return '';
                    }

                    $previewLimit = 10;
                    $preview = mb_substr($value, 0, $previewLimit, 'UTF-8');
                    $isOverflow = mb_strlen($value, 'UTF-8') > $previewLimit;
                    if ($isOverflow) {
                        $preview .= '…';
                    }

                    $escapedValue = e($value);
                    $escapedPreview = e($preview);

                    $toggleHtml = '';
                    if ($isOverflow) {
                        $toggleHtml = '<button type="button" class="barcode-toggle" data-barcode="' . $escapedValue . '" aria-label="查看专属编码">'
                            . '<i class="fa fa-eye"></i>'
                            . '</button>';
                    }

                    return <<<HTML
<div class="barcode-cell">
    <span class="barcode-preview" title="{$escapedValue}">{$escapedPreview}</span>
    {$toggleHtml}
</div>
HTML;
                })
                ->emp();
            $grid->column('name')->setHeaderAttributes(['class' => 'column-name'])->emp();
//            $grid->column('py_code')->emp();
            $grid->column('type', '分类')->setHeaderAttributes(['class' => 'column-type'])->using(ProductModel::TYPE);
            $grid->column('brand.name', '品牌')->setHeaderAttributes(['class' => 'column-brand'])->emp();
            $grid->column('luxury_brand_series', '对应大牌')->setHeaderAttributes(['class' => 'column-luxury_brand_series'])->emp();
            $grid->column('product_image', '产品图片')->setHeaderAttributes(['class' => 'column-product_image'])
                ->display(function ($value) {
                    return $value ?: 'public/default.png';
                })
                ->image('', 64, 64);
            $grid->column('unit.name', '单位')->setHeaderAttributes(['class' => 'column-unit'])->emp();
            $grid->column('warning_num')->setHeaderAttributes(['class' => 'column-warning_num'])->emp();
            $grid->column('sale_price')->setHeaderAttributes(['class' => 'column-sale_price'])->emp();
            $grid->column('purchase_price')->setHeaderAttributes(['class' => 'column-purchase_price'])->emp();
//            $grid->column('created_at');
//            $grid->column('updated_at')->sortable();

            $grid->batchActions([
                new BatchDeleteProduct(),
            ]);

            $grid->tools(function (Grid\Tools $tools) use ($columnConfig) {
                $tools->append(new ImportProductTool());
                $tools->append(new ImportProductPrice());
                $tools->append(new ColumnSelector($columnConfig));
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

            Admin::style(<<<CSS
.barcode-cell{display:flex;align-items:center;gap:8px;}
.barcode-preview{max-width:220px;display:inline-block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.barcode-toggle{padding:0;border:none;background:transparent;line-height:1;cursor:pointer;color:#333;}
.barcode-toggle:focus{outline:none;}
.barcode-drawer-mask{position:fixed;inset:0;background:rgba(0,0,0,.35);opacity:0;visibility:hidden;transition:all .2s ease;z-index:1998;}
.barcode-drawer{position:fixed;top:0;right:0;height:100%;width:420px;max-width:92vw;background:#fff;box-shadow:-6px 0 18px rgba(0,0,0,.12);transform:translateX(100%);transition:transform .25s ease;z-index:1999;display:flex;flex-direction:column;}
.barcode-drawer.is-open{transform:translateX(0);}
.barcode-drawer-mask.is-open{opacity:1;visibility:visible;}
.barcode-drawer__header{padding:16px 18px;border-bottom:1px solid #eee;display:flex;align-items:center;justify-content:space-between;}
.barcode-drawer__title{font-weight:600;font-size:15px;color:#333;}
.barcode-drawer__header-actions{display:flex;align-items:center;gap:8px;}
.barcode-drawer__body{padding:16px 18px;overflow:auto;flex:1;}
.barcode-drawer__list{margin:0;padding-left:18px;}
.barcode-drawer__list li{margin:6px 0;line-height:1.6;color:#333;word-break:break-all;}
@media (max-width: 576px){.barcode-drawer{width:92vw;}}
CSS);

            Admin::script(<<<JS
(function () {
    if (window.__barcodeDrawerInit) {
        return;
    }
    window.__barcodeDrawerInit = true;

    var drawerHtml = ''
        + '<div class="barcode-drawer-mask" id="barcode-drawer-mask"></div>'
        + '<div class="barcode-drawer" id="barcode-drawer">'
        + '  <div class="barcode-drawer__header">'
        + '    <div class="barcode-drawer__title">专属编码</div>'
        + '    <div class="barcode-drawer__header-actions">'
        + '      <button type="button" class="btn btn-sm btn-outline-primary" data-action="copy-all">复制全部</button>'
        + '      <button type="button" class="btn btn-sm btn-light" data-action="close">关闭</button>'
        + '    </div>'
        + '  </div>'
        + '  <div class="barcode-drawer__body">'
        + '    <ul class="barcode-drawer__list" id="barcode-drawer-list"></ul>'
        + '  </div>'
        + '</div>';

    document.body.insertAdjacentHTML('beforeend', drawerHtml);

    var drawer = document.getElementById('barcode-drawer');
    var mask = document.getElementById('barcode-drawer-mask');
    var list = document.getElementById('barcode-drawer-list');
    var currentButton = null;
    var currentRawText = '';

    function splitLines(text) {
        return text.split(/[；;]+/)
            .map(function (item) { return item.trim(); })
            .filter(function (item) { return item.length; });
    }

    function formatLine(text) {
        return text.replace(/\s*\/\s*/g, ' / ');
    }

    function renderList(text) {
        var items = splitLines(text).map(formatLine);
        if (!items.length) {
            list.innerHTML = '';
            return;
        }
        list.innerHTML = items.map(function (item) {
            var safe = item.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            return '<li>' + safe + '</li>';
        }).join('');
    }

    function openDrawer(rawText, button) {
        currentButton = button || null;
        currentRawText = rawText || '';
        renderList(currentRawText);
        drawer.classList.add('is-open');
        mask.classList.add('is-open');
        if (currentButton) {
            var icon = currentButton.querySelector('i');
            if (icon) {
                icon.className = 'fa fa-eye-slash';
            }
        }
    }

    function closeDrawer() {
        drawer.classList.remove('is-open');
        mask.classList.remove('is-open');
        if (currentButton) {
            var icon = currentButton.querySelector('i');
            if (icon) {
                icon.className = 'fa fa-eye';
            }
        }
        currentButton = null;
    }

    function copyText(text) {
        if (!text) {
            if (window.Dcat && Dcat.error) {
                Dcat.error('请先选中内容');
            } else {
                alert('请先选中内容');
            }
            return;
        }
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text);
        } else {
            var temp = document.createElement('textarea');
            temp.value = text;
            document.body.appendChild(temp);
            temp.select();
            document.execCommand('copy');
            document.body.removeChild(temp);
        }
    }

    document.addEventListener('click', function (event) {
        var target = event.target;
        var toggle = target.closest('.barcode-toggle');
        if (toggle) {
            var rawText = toggle.getAttribute('data-barcode') || '';
            openDrawer(rawText, toggle);
            return;
        }

        var actionButton = target.closest('[data-action]');
        if (actionButton) {
            var action = actionButton.getAttribute('data-action');
            if (action === 'close') {
                closeDrawer();
                return;
            }
            if (action === 'copy-all') {
                copyText(currentRawText);
                return;
            }
        }

        if (target === mask) {
            closeDrawer();
        }
    });
})();
JS);
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
                $row->width(6)->text('luxury_brand_series', '对应大牌')
                    ->help('填写该产品对标的品牌系列，如：香奈儿-粉邂逅');
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

            $form->row(function (Form\Row $row) {
                $row->width()->image('product_image', '产品图片')
                    ->help('请使用PS预先处理好产品图片的大小，推荐尺寸：800px * 800px，大小不超过2M')
                    ->maxSize(2048)
                    ->uniqueName()
                    ->autoUpload();
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

                // 处理 product_attr：检查新创建的记录是否可以复用软删除记录
                $currentAttrs = $product->product_attr()->get();
                foreach ($currentAttrs as $attr) {
                    // 查找同 product_id + attr_id 的软删除记录
                    $trashedAttr = \App\Models\ProductAttrModel::onlyTrashed()
                        ->where('product_id', $id)
                        ->where('attr_id', $attr->attr_id)
                        ->latest('id')
                        ->first();

                    if ($trashedAttr) {
                        // 恢复软删除记录并更新 attr_value_ids
                        $trashedAttr->restore();
                        $trashedAttr->attr_value_ids = $attr->attr_value_ids;
                        $trashedAttr->save();
                        // 删除新创建的记录（用恢复的记录替代）
                        $attr->forceDelete();
                    }
                }

                // 获取现有（未删除）SKU 的 attr_value_ids
                $existingSkuKeys = $product->sku->pluck('attr_value_ids')->toArray();

                // 计算需要新增的 attr_value_ids 组合
                $toCreate = collect($product->attr_value_arr)->keys()->diff($existingSkuKeys);

                foreach ($toCreate as $attrValueIds) {
                    $attrValueIds = (string) $attrValueIds;
                    // 优先尝试恢复最后删除的 SKU（按 ID 降序取最新）
                    $trashedSku = \App\Models\ProductSkuModel::onlyTrashed()
                        ->where('product_id', $id)
                        ->where('attr_value_ids', $attrValueIds)
                        ->latest('id')
                        ->first();

                    if ($trashedSku) {
                        $trashedSku->restore();
                    } else {
                        $product->sku()->create(['attr_value_ids' => $attrValueIds]);
                    }
                }
            });

            $form->saving(function (Form $form) {
                $inputs = request()->input();

                if (isset($inputs['product_attr']) && is_array($inputs['product_attr'])) {
                    $attrIds = [];
                    foreach ($inputs['product_attr'] as $attrInput) {
                        if (isset($attrInput['_remove_']) && $attrInput['_remove_'] == 1) {
                            continue;
                        }

                        $attrId = $attrInput['attr_id'] ?? null;
                        if ($attrId !== null && $attrId !== '') {
                            $attrIds[] = (int) $attrId;
                        }
                    }

                    if ($attrIds) {
                        $attrIdCounts = array_count_values($attrIds);
                        foreach ($attrIdCounts as $count) {
                            if ($count > 1) {
                                return $form->error('属性不可重复，请检查属性设置');
                            }
                        }
                    }
                }

                $id = $form->getKey();
                if (!$id) {
                    return; // 新建时不处理
                }

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

                                // 统一转为整型比较，防止类型不一致
                                $oldValues = array_map('intval', $oldValues);
                                $newValues = array_map('intval', $newValues);

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
                                $deletedAttrValueIds = array_merge($deletedAttrValueIds, array_map('intval', $vals));
                            }
                        }
                    }
                    // 执行 product_attr 软删除
                    \App\Models\ProductAttrModel::whereIn('id', $toDeleteAttrIds)->delete();
                }

                // 3. 删除包含这些属性值的 SKU
                if (!empty($deletedAttrValueIds)) {
                    $deletedAttrValueIds = array_unique($deletedAttrValueIds);
                    // 统一转整型
                    $deletedAttrValueIds = array_map('intval', $deletedAttrValueIds);

                    $product = ProductModel::find($id);
                    if ($product) {
                        $skus = $product->sku; // 获取未删除的 SKU
                        $skusToDelete = [];
                        foreach ($skus as $sku) {
                            $skuAttrValueIds = array_map('intval', explode(',', $sku->attr_value_ids));
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
