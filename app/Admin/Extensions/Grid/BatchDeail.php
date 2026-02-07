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
        $apiUrl = route('api.item.actual-num');
        $script = <<<JS
            $(".{$this->selector}").off("click").on("click",function() {
                var url = $(this).attr('data-url');
                var btn = $(this);
                layer.open({
                    type: 2,
                    area: ['90%', '90%'],
                    content:url,
                    end: function(){
                        try {
                            var urlObj = new URL(url, location.origin);
                            var itemId = urlObj.searchParams.get('item_id');
                            var pathname = urlObj.pathname.replace(/\/$/, '');
                            var seg = pathname.substring(pathname.lastIndexOf('/') + 1);
                            var table = seg.replace(/-batchs$/, '').replace(/-/g, '_') + '_item';
                            console.log('[BatchDeail] end触发', {itemId: itemId, table: table, url: url});
                            if (!itemId) return;
                            $.ajax({
                                url: '{$apiUrl}',
                                type: 'GET',
                                dataType: 'json',
                                data: {item_id: itemId, table: table},
                                success: function(data) {
                                    console.log('[BatchDeail] 接口返回', data);
                                    var tr = btn.closest('tr');
                                    var thead = tr.closest('table').find('thead tr:last');
                                    thead.find('th').each(function(i) {
                                        var text = $(this).text().trim();
                                        if (text === '实领数量') {
                                            tr.find('td').eq(i).text(data.actual_num);
                                        } else if (text === '成本总价') {
                                            tr.find('td').eq(i).text(data.cost_price);
                                        }
                                    });
                                },
                                error: function(xhr) {
                                    console.error('[BatchDeail] 接口请求失败', xhr.status, xhr.responseText);
                                }
                            });
                        } catch(e) {
                            console.error('[BatchDeail] end回调异常', e);
                        }
                    }
                })
            })
JS;
        Admin::script($script);
    }
}
