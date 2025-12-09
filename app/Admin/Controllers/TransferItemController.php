<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\TransferItem;
use App\Models\TransferItemModel;
use Dcat\Admin\Controllers\AdminController;
use Dcat\Admin\Grid;

class TransferItemController extends AdminController
{
    protected function grid()
    {
        return Grid::make(new TransferItem(['sku', 'out_position', 'in_position', 'order']), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('order.order_no', '单号');
            $grid->column('sku.product.name', '物料名称');
            $grid->column('num', '数量');
            $grid->column('out_position.name', '调出仓库');
            $grid->column('in_position.name', '调入仓库');
            $grid->column('batch_no', '批次号');
            $grid->column('created_at');
            
            $grid->disableActions();
            $grid->disableCreateButton();
        });
    }
}
