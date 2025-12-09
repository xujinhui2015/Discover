<?php

namespace App\Admin\Controllers;

use App\Admin\Actions\Grid\EditOrder;
use App\Admin\Extensions\Form\Order\OrderController;
use App\Admin\Repositories\TransferOrder;
use App\Models\PositionModel;
use App\Models\ProductModel;
use App\Models\ProductSkuModel;
use App\Models\SkuStockBatchModel;
use App\Models\TransferOrderModel;
use Dcat\Admin\Admin;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Models\Administrator;

class TransferOrderController extends OrderController
{
    public function edit($id, Content $content)
    {
        $this->order = $this->oredr_model::findOrFail($id);

        // 待审核状态时，只显示表单编辑（hasMany），不显示下方的 Grid
        if ($this->order->review_status === TransferOrderModel::REVIEW_STATUS_WAIT) {
            return $content
                ->title($this->title())
                ->description($this->description()['edit'] ?? trans('admin.edit'))
                ->body($this->form()->edit($id))
                ->full();
        }

        // 已审核状态时，使用父类默认行为（显示表单 + Grid）
        return parent::edit($id, $content);
    }

    protected function grid()
    {
        return Grid::make(new TransferOrder(['user', 'out_position', 'in_position']), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('order_no', '单号');
            $grid->column('out_position.name', '调出仓库');
            $grid->column('in_position.name', '调入仓库');
            $grid->column('user.name', '创建用户');
            $grid->column('other', '备注')->emp();
            $grid->column('review_status', '状态')->using(TransferOrderModel::REVIEW_STATUS)->label(TransferOrderModel::REVIEW_STATUS_COLOR);
            $grid->column('created_at', '创建时间');
            $grid->disableQuickEditButton();
            $grid->actions(function (Grid\Displayers\Actions $actions) {
                 $actions->append(EditOrder::make());
            });
        });
    }

    protected function setForm(Form &$form): void
    {
        $positions = PositionModel::pluck('name', 'id');

        $form->row(function (Form\Row $row) {
            $row->width(6)->text('order_no', '单号')->default(build_order_no('DB'))->required()->readOnly();
            $row->width(6)->text('created_at', '业务日期')->default(now())->required()->readOnly();
        });
        $form->row(function (Form\Row $row) use ($positions) {
            $row->width(6)->select('out_position_id', '调出仓库')->options($positions)->required();
            $row->width(6)->select('in_position_id', '调入仓库')->options($positions)->required();
        });
        $form->row(function (Form\Row $row) {
             $users = Administrator::query()->latest()->pluck('name', 'id');
             $row->width(6)->select('user_id', '创建人')->options($users)->default(Admin::user()->id)->required();
             $row->width(6)->text('other', '备注')->saveAsString();
        });

        // 通用保存校验（创建/编辑均执行）
        $form->saving(function (Form $form) {
            $outPositionId = $form->input('out_position_id');
            $inPositionId = $form->input('in_position_id');
            $items = $form->input('items') ?: [];

            if (! $outPositionId || ! $inPositionId) {
                return $form->error('请先选择调出仓库和调入仓库');
            }

            if ($outPositionId && $inPositionId && (int) $outPositionId === (int) $inPositionId) {
                return $form->error('调入仓库不能与调出仓库相同');
            }

            foreach ($items as $index => &$item) {
                $row = is_numeric($index) ? ((int) $index + 1) : str_replace('new_', '', (string) $index);

                $skuId = $item['sku_id'] ?? null;
                $batchNo = $item['batch_no'] ?? null;
                $numRaw = $item['num'] ?? null;
                $num = $numRaw === null ? null : (float) str_replace(',', '', $numRaw);

                $item['out_position_id'] = $outPositionId;
                $item['in_position_id'] = $inPositionId;

                $batch = null;
                if ($skuId && $batchNo) {
                    $batch = SkuStockBatchModel::query()
                        ->where('sku_id', $skuId)
                        ->where('batch_no', $batchNo)
                        ->where('position_id', $outPositionId)
                        ->first(['num', 'position_id']);
                }

                if ($skuId && $batchNo && $num !== null) {
                    if (! $batch) {
                        return $form->error("第{$row}行：所选批次不存在或库存不足");
                    }

                    if ($num - (float) $batch->num > 0.0001) {
                        return $form->error("第{$row}行：数量不可大于批次数量（可用 {$batch->num}）");
                    }
                }
            }
            unset($item);
            request()->merge(['items' => $items]);
        });
    }

    protected function creating(Form &$form): void
    {
        $form->row(function (Form\Row $row) {
            $this->buildItemFormRows($row);
        });

        $this->appendBatchAssets($form);
    }

    protected function editing(Form &$form): void
    {
        $form->row(function (Form\Row $row) {
            $row->reviewicon('review_status', '审核状态');
        });

        // 待审核状态时，允许像新增时一样编辑物料信息
        if ($this->order && $this->order->review_status === TransferOrderModel::REVIEW_STATUS_WAIT) {
            $form->row(function (Form\Row $row) {
                $this->buildItemFormRows($row);
            });

            $this->appendBatchAssets($form);
        }

        $form->disableFooter();
        $form->disableHeader();
        $form->disableAjaxSubmit();
    }

    protected function buildItemFormRows(Form\Row $row): void
    {
        $row->hasMany('items', '', function (Form\NestedForm $table) {
            $table->select('product_id', '物料名称')->options(ProductModel::pluck('name', 'id'))->loadpku(route('api.product.find'))->required();
            $table->ipt('unit', '单位')->rem(3)->default('-')->disable();
            $table->select('sku_id', '属性选择')->options(function ($id) {
                if (! $id) {
                    return [];
                }

                $sku = ProductSkuModel::find($id);

                if (! $sku) {
                    return [];
                }

                return [$sku->id => $sku->attr_value_ids_str];
            })->required()->addElementClass('transfer-sku');

            $table->select('batch_no', '批次号')->options(function ($value) {
                if (! $value) {
                    return [];
                }

                return [$value => $value];
            })->required()->addElementClass('batch-select')->addElementClass('transfer-batch');

            // Using standard constant from InitStockOrderModel
            $table->select('standard', '通用标准')->options(\App\Models\InitStockOrderModel::STANDARD)->default(0);

            $table->tableDecimal('num', '数量')->default(0.00)->required()->addElementClass('num-input');
        })->useTable()->width(12)->enableHorizontal();
    }

    protected function appendBatchAssets(Form $form): void
    {
        // 保证错误提示在弹窗上方显示；hasMany 行宽过多时提供横向滚动
        Admin::style('.toast-container{z-index:2147483647!important;} .has-many-items{overflow-x:auto;} .has-many-items .table-has-many{min-width:1200px;}');

        $formSelector = '#'.$form->getElementId();
        $batchUrl = route('api.sku.batches');

        Admin::script(
            <<<JS
(function () {
    var formSelector = "{$formSelector}";
    var batchUrl = "{$batchUrl}";
    var skuSelector = formSelector + ' .transfer-sku';
    var batchSelector = formSelector + ' .transfer-batch';
    var outPositionSelector = formSelector + ' .field_out_position_id';
    var productSelector = formSelector + ' .field_product_id';

    // 记录初始选中值，避免加载时被重置
    $(skuSelector).each(function () {
        var currentVal = $(this).val();
        if (currentVal) {
            $(this).attr('data-value', currentVal);
        }
    });
    $(batchSelector).each(function () {
        var currentVal = $(this).val();
        if (currentVal) {
            $(this).attr('data-value', currentVal);
        }
    });

    function findOutPositionId() {
        return $(outPositionSelector).val() || '';
    }

    function refreshBatchOptions(skuSelectEl) {
        var skuId = skuSelectEl.val();
        var currentRow = skuSelectEl.closest('.fields-group');
        var batchSelectEl = currentRow.find('.transfer-batch');

        if (! batchSelectEl.length) {
            return;
        }

        var outPositionId = findOutPositionId();

        if (! skuId || ! outPositionId) {
            batchSelectEl.attr('data-value', '');
            batchSelectEl.find('option').remove();
            batchSelectEl.val(null).trigger('change');
            return;
        }

        $.ajax(batchUrl + '?q=' + skuId + '&out_position_id=' + outPositionId).then(function (data) {
            batchSelectEl.find('option').remove();
            batchSelectEl.select2({
                data: $.map(data, function (d) {
                    return d;
                })
            });

            var current = batchSelectEl.attr('data-value') || batchSelectEl.val();
            var value = null;

            if (current) {
                value = String(current).split(',');
            } else if (data.length > 0) {
                value = [String(data[0].id)];
                batchSelectEl.attr('data-value', data[0].id);
            }

            batchSelectEl.val(value).trigger('change');
        });
    }

    function handleBatchSelect(selectEl, data) {
        var currentRow = selectEl.closest('tr');

        if (data.num !== undefined && data.num !== null) {
            currentRow.find('.num-input').val(data.num);
        }
    }

    $(document).off('change', skuSelector);
    $(document).on('change', skuSelector, function () {
        var skuSelect = $(this);
        var rowEl = skuSelect.closest('.fields-group');
        var prevSku = skuSelect.data('prev-sku-id');
        var currentSku = skuSelect.val();

        // SKU 变化时清空批次选择的缓存值，避免沿用旧物料的批次
        if (prevSku && prevSku !== currentSku) {
            rowEl.find('.transfer-batch').attr('data-value', '').find('option').remove();
        }

        skuSelect.data('prev-sku-id', currentSku);
        refreshBatchOptions(skuSelect);
    });

    $(document).off('change', outPositionSelector);
    $(document).on('change', outPositionSelector, function () {
        $(batchSelector).each(function () {
            $(this).attr('data-value', '').find('option').remove();
            $(this).val(null).trigger('change');
        });

        $(skuSelector).trigger('change');
    });

    // 物料变化时清空批次，避免沿用旧物料的批次
    $(document).off('change', productSelector);
    $(document).on('change', productSelector, function () {
        var rowEl = $(this).closest('.fields-group');
        rowEl.find('.transfer-batch').attr('data-value', '').find('option').remove();
        rowEl.find('.transfer-batch').val(null).trigger('change');
    });

    $(document).off('select2:select change', formSelector + ' .batch-select');
    $(document).on('select2:select change', formSelector + ' .batch-select', function (e) {
        var data = (e.params && e.params.data) ? e.params.data : ($(this).select2('data')[0] || {});
        handleBatchSelect($(this), data);
    });

    $(skuSelector).trigger('change');
})();
JS
        );
    }

    public function setItems(Grid &$grid): void
    {
        $grid->column('sku.product.name', '物料名称');
        $grid->column('sku.product.unit.name', '单位');
        $grid->column('sku.product.type_str', '分类');
        $grid->column('sku_id', '属性')->display(function () {
             return $this->sku['attr_value_ids_str'] ?? '';
        });
        $grid->column('standard', '通用标准')->display(function () {
             return \App\Models\InitStockOrderModel::STANDARD[$this->standard] ?? $this->standard;
        });
        $grid->column('out_position.name', '调出仓库');
        $grid->column('in_position.name', '调入仓库');
        $grid->column('batch_no', '批次号');
        $grid->column('num', '数量');
    }
}
