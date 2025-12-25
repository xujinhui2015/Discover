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

namespace App\Admin\Extensions\Form;

use Dcat\Admin\Form;
use Dcat\Admin\Admin;

class Select
{
    public static function macro(): void
    {
        static::loadpku();
        static::with_order();
    }

    protected static function with_order(): void
    {
        Form\Field\Select::macro('with_order', function () {
            $controller = admin_controller_name();
            $url = route('api.with.order');
            $script = <<<JS
$(document).off('change', "{$this->getElementClassSelector()}");
$(document).on('change', "{$this->getElementClassSelector()}", function () {

            if (String(this.value) !== '0' && ! this.value) {
                return;
            }
            var order_no = $('input[name="order_no"]').val();
            var with_order_id = this.value;
            var with_order_order = $(this).find('option:selected').text();


            data = {
                _token: Dcat.token,
                order_no: order_no,
                with_order_id: with_order_id,
                func: "{$controller}",
            }

            Dcat.confirm('确认关联单据'+with_order_order+'吗？', null, function () {
                Dcat.NP.start();
                $.ajax({
                        url: "{$url}",
                        type: "POST",
                        data: data,
                        success: function (data) {
                            if (data.code == 200) {
                                Dcat.success("订单关联成功！");
                                Dcat.reload();
                            } else {
                                Dcat.error(data.message);
                            }
                        },
                        error:function(a,b,c) {
                            Dcat.handleAjaxError(a, b, c);
                        },
                        complete:function(a,b) {
                            Dcat.NP.done();
                        }
                    });

            });
});
JS;

            Admin::script($script);

            return $this;
        });
    }

    protected static function loadpku(): void
    {
        // 加载pku动态选择
        Form\Field\Select::macro('loadpku', function ($sourceUrl) {
            $sourceUrl  = admin_url($sourceUrl);
            $unitClass  = static::FIELD_CLASS_PREFIX . 'unit';
            $skuIdClass = static::FIELD_CLASS_PREFIX . 'sku_id';
            $typeClass  = static::FIELD_CLASS_PREFIX . 'type';
            $brandClass  = static::FIELD_CLASS_PREFIX . 'brand';
            $priceClass  = static::FIELD_CLASS_PREFIX . 'price';

            $script = <<<JS
$(document).off('change', "{$this->getElementClassSelector()}");
$(document).on('change', "{$this->getElementClassSelector()}", function () {
     var unit = $(this).closest('.fields-group').find(".$unitClass");
     var sku_id = $(this).closest('.fields-group').find(".$skuIdClass");
     var type = $(this).closest('.fields-group').find(".$typeClass");
     var brand = $(this).closest('.fields-group').find(".$brandClass");
     var price = $(this).closest('.fields-group').find(".$priceClass");


    if (String(this.value) !== '0' && ! this.value) {
        return;
    }

    $.ajax("$sourceUrl?q="+this.value).then(function (data) {
        unit.val(data.data.unit);
        type.val(data.data.type_str);
        brand.val(data.data.brand_str);
        sku_id.find("option").remove();

        // 预填价格：根据当前页面判断是采购价还是销售价
        if (price.length > 0) {
            var currentUrl = window.location.href;
            console.log('当前URL:', currentUrl);
            console.log('价格字段:', price);
            console.log('返回数据:', data.data);

            // 只在字段为空或为默认值0.00时才预填
            var currentPrice = price.val();
            if (!currentPrice || currentPrice === '' || currentPrice === '0' || currentPrice === '0.00') {
                if (currentUrl.indexOf('purchase-orders') > -1 && data.data.purchase_price) {
                    console.log('预填采购价:', data.data.purchase_price);
                    price.val(data.data.purchase_price);
                } else if ((currentUrl.indexOf('sale-item') > -1 || currentUrl.indexOf('sale-order') > -1) && data.data.sale_price) {
                    console.log('预填销售价:', data.data.sale_price);
                    price.val(data.data.sale_price);
                }
            }
        }

        // $(sku_id).select2({
        //     data: $.map(data.data.product_attr, function (d) {
        //         d.id = d.id;
        //         d.text = d.text;
        //         return d;
        //     })
        // }).val(sku_id.attr('data-value')).trigger('change');

        $(sku_id).select2({
        data: $.map(data.data.product_attr, function (d) {
                return {
                    id: d.id,
                    text: d.text
                };
            })
        });

        // 如果有 data-value，则选 data-value；否则选第一个
        let firstId = data.data.product_attr.length > 0 ? data.data.product_attr[0].id : null;
        let currentSkuValue = $(sku_id).val();
        let value = sku_id.attr('data-value') || currentSkuValue || firstId;

        if (value) {
            $(sku_id).val(value).trigger('change');
        }

    });
});
$("{$this->getElementClassSelector()}").trigger('change');
JS;

            Admin::script($script);

            return $this;
        });
    }
}
