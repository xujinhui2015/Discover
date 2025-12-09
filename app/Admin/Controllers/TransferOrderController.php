<?php

namespace App\Admin\Controllers;

use App\Admin\Actions\Grid\EditOrder;
use App\Admin\Extensions\Form\Order\OrderController;
use App\Admin\Repositories\TransferOrder;
use App\Models\PositionModel;
use App\Models\ProductModel;
use App\Models\TransferOrderModel;
use Dcat\Admin\Admin;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Models\Administrator;
use Illuminate\Support\Fluent;

class TransferOrderController extends OrderController
{
    protected function grid()
    {
        return Grid::make(new TransferOrder(['user']), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('order_no', '单号');
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
        $form->row(function (Form\Row $row) {
            $row->width(6)->text('order_no', '单号')->default(build_order_no('DB'))->required()->readOnly();
            $row->width(6)->text('created_at', '业务日期')->default(now())->required()->readOnly();
        });
        $form->row(function (Form\Row $row) {
             $users = Administrator::query()->latest()->pluck('name', 'id');
             $row->width(6)->select('user_id', '创建人')->options($users)->default(Admin::user()->id)->required();
             $row->width(6)->text('other', '备注')->saveAsString();
        });
    }

    protected function creating(Form &$form): void
    {
        $form->row(function (Form\Row $row) {
            $row->hasMany('items', '明细', function (Form\NestedForm $table) {
                $table->select('product_id', '物料名称')->options(ProductModel::pluck('name', 'id'))->loadpku(route('api.product.find'))->required();
                $table->ipt('unit', '单位')->rem(3)->default('-')->disable();
                $table->select('sku_id', '属性选择')->options()->load('batch_no', route('api.sku.batches'))->required();

                $table->select('batch_no', '批次号')->options()->required()->addElementClass('batch-select');

                // Using standard constant from InitStockOrderModel
                $table->select('standard', '通用标准')->options(\App\Models\InitStockOrderModel::STANDARD)->default(0);

                $table->select('out_position_id', '调出仓库')->options(PositionModel::pluck('name', 'id'))->required()->readonly()->addElementClass('out-position-select');
                $table->tableDecimal('num', '数量')->default(0.00)->required()->addElementClass('num-input');
                $table->select('in_position_id', '调入仓库')->options(PositionModel::pluck('name', 'id'))->required();
            })->useTable()->width(12)->enableHorizontal();
        });

        Admin::script(
            <<<JS
(function () {
    function lockOutPositionSelect(outPositionSelect) {
        if (! outPositionSelect || ! outPositionSelect.length) {
            return;
        }

        // Prevent manual opening while keeping value submitted
        outPositionSelect.off('select2:opening.lock').on('select2:opening.lock', function (event) {
            event.preventDefault();
        });

        var selection = outPositionSelect.next('.select2');
        if (selection.length) {
            selection.find('.select2-selection').css('pointer-events', 'none');
        }
    }

    function handleBatchSelect(selectEl, data) {
        var currentRow = selectEl.closest('tr');

        if (data.num !== undefined && data.num !== null) {
            currentRow.find('.num-input').val(data.num);
        }

        var outPositionSelect = currentRow.find('.out-position-select');
        if (outPositionSelect.length && data.position_id !== undefined && data.position_id !== null) {
            outPositionSelect.val(data.position_id).trigger('change');
        }

        lockOutPositionSelect(outPositionSelect);
    }

    $(document).on('select2:select change', '.batch-select', function (e) {
        var data = (e.params && e.params.data) ? e.params.data : ($(this).select2('data')[0] || {});
        handleBatchSelect($(this), data);
    });

    // 初始化时锁定调出仓库下拉，避免手动修改
    $(function () {
        $('.out-position-select').each(function () {
            lockOutPositionSelect($(this));
        });
    });
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
