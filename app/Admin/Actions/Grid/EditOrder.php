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

use App\Models\BaseModel;
use Dcat\Admin\Grid\RowAction;

class EditOrder extends RowAction
{
    /**
     * @return string
     */
    protected $title = '操作';

    /**
     * @return string
     */
    public function html()
    {
        $showBtn = $this->row('review_status') === BaseModel::REVIEW_STATUS_OK ? 'no' : 'yes';
        $action = $this->resource() . "/" . $this->getKey() . "/edit";
        return <<<HTML
<a class="{$this->getElementClass()} btn btn-xs btn-primary" data-show-btn="{$showBtn}" href="javascript:void(0)" data-action="$action" style="padding: 3px 8px; border-radius: 3px; display: inline-flex; align-items: center; cursor: pointer; transition: all 0.2s ease;">
    {$this->title()}
  </a>
HTML;
    }

    public function script()
    {
        $class = $this->getElementClass();
        $title = admin_trans_label();
        return <<<JS
        $(" .{$class}").on("click",function(){
            var action = $(this).data('action');
            var show_btn = $(this).data('show-btn');
            var option = {
                title:'{$title}',
                type: 2,
                area: ['85%', '90%'], //宽高
                content:[action],
                scrollbar:false,
                end: function(){
                    Dcat.reload();
                },
            };
            if (show_btn == 'yes') {
                option.btn = ['保存'];
                option.btn1 = function(index, layero){
                    var orderInfo = $('#layui-layer-iframe'+index).contents().find('.content .row:eq(0) .col-md-12:eq(0) form:eq(0)');
                    var url = orderInfo.attr('action');
                    Dcat.NP.start();
                    $.ajax({
                        type: "POST",
                        dataType: "json",
                        url: url,
                        data: orderInfo.serialize(),
                        success: function (data) {
                            if (data.status) {
                                Dcat.success(data.message);
                            } else {
                                Dcat.error(data.message);
                            }
                        },
                        error : function(a,b,c) {
                            Dcat.handleAjaxError(a, b, c);
                        },
                        complete:function(a,b) {
                            Dcat.NP.done();
                        }
                    });
                    layer.close(index);
                };
            }
            layer.open(option);
        });
JS;
    }
}
