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

namespace App\Admin\Extensions\Grid;

use Dcat\Admin\Admin;
use Dcat\Admin\Grid\Displayers\AbstractDisplayer;

class BatchDeail extends AbstractDisplayer
{
    protected $selector = 'grid-filed-batch-detail';

    public function display($params = null)
    {
        if ($params instanceof \Closure) {
            $url = $params($this);
        } else {
            $url = $params;
        }
        $this->addScript();
        return "<a class='{$this->selector} btn btn-primary btn-sm' style=' padding: 2px 6px;line-height: 1;white-space: nowrap;font-size: 14px;' data-url='{$url}' href='javascript:void(0)' >选择</a>";
    }

    protected function addScript()
    {
        $script = <<<JS
            $(".{$this->selector}").on("click",function() {
                var url = $(this).attr('data-url');
                layer.open({
                    type: 2,
                    area: ['70%', '90%'], //宽高
                    content:url,
                    end: function(){
                        Dcat.reload();
                    }
                })
            })
JS;
        Admin::script($script);
    }
}
