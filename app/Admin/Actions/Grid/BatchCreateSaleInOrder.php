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

namespace App\Admin\Actions\Grid;

use Dcat\Admin\Grid;
use Dcat\Admin\Grid\BatchAction;

class BatchCreateSaleInOrder extends BatchAction
{
    /**
     * @return string
     */
    protected $title = '选择单据退货';

    public function html()
    {
        return <<<HTML
<span {$this->formatHtmlAttributes()} id="batch-sale-in-order-create-select-resourc" href="javascript:void(0)"><button class="btn btn-primary dialog-create  btn-mini"><i
class="feather icon-plus"></i> {$this->title()}</button></span>
HTML;
    }

    public function script()
    {
        $url = route('sale-out-orders.index', [Grid::IFRAME_QUERY_NAME => 1]);
        return <<<JS
        $("#batch-sale-in-order-create-select-resourc").on("click",function(){
            var url = "{$url}";
            layer.open({
                type: 2,
                area: ['80%', '90%'], //宽高
                content:[url,'no'],
                end: function(){
                    Dcat.reload();
                }
            })
        });

JS;
    }
}
