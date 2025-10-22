<?php

namespace App\Admin\Extensions\Form;

use Dcat\Admin\Form;
use Dcat\Admin\Admin;

class SelectTable
{
    public static function macro(): void
    {
        static::loadpku();
    }

    protected static function loadpku(): void
    {
        // 这里改为 SelectTable 宏
        Form\Field\SelectTable::macro('loadpku', function ($sourceUrl) {
            $sourceUrl  = admin_url($sourceUrl);
            $unitClass  = static::FIELD_CLASS_PREFIX . 'unit';
            $skuIdClass = static::FIELD_CLASS_PREFIX . 'sku_id';
            $typeClass  = static::FIELD_CLASS_PREFIX . 'type';
            $brandClass = static::FIELD_CLASS_PREFIX . 'brand';

            $script = <<<JS

        $(document).off('change', "{$this->getElementClassSelector()}");
        $(document).on('change', "{$this->getElementClassSelector()}", function () {
     var unit = $(this).closest('.fields-group').find(".$unitClass");
     var sku_id = $(this).closest('.fields-group').find(".$skuIdClass");
     var type = $(this).closest('.fields-group').find(".$typeClass");
     var brand = $(this).closest('.fields-group').find(".$brandClass");

console.log(1111);
    if (String(this.value) !== '0' && ! this.value) {
        return;
    }
    console.log(2222);

    $.ajax("$sourceUrl?q="+this.value).then(function (data) {
        unit.val(data.data.unit);
        type.val(data.data.type_str);
        brand.val(data.data.brand_str);
        sku_id.find("option").remove();

        $(sku_id).select2({
            data: $.map(data.data.product_attr, function (d) {
                d.id = d.id;
                d.text = d.text;
                return d;
            })
        }).val(sku_id.attr('data-value')).trigger('change');
    });
});
$("{$this->getElementClassSelector()}").trigger('change');
JS;

            Admin::script($script);

            return $this;
        });
    }
}
