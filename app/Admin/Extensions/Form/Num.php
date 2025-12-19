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

use Dcat\Admin\Admin;
use Dcat\Admin\Form\Field;

class Num extends Field\Number
{
    protected function addScript()
    {
        $this->script = <<<JS
(function () {
    var selector = '{$this->getElementClassSelector()}';

    $(selector + ':not(.initialized)')
        .addClass('initialized')
        .bootstrapNumber({
            upClass: 'primary',
            downClass: 'white',
            center: true
        });

    // 数量一般较大，隐藏 +/- 按钮，避免占用输入宽度
    $(selector).each(function () {
        var \$input = $(this);
        if (!\$input.hasClass('num-no-stepper')) {
            return;
        }

        \$input.closest('.input-group').find('.input-group-btn').remove();
    });
})();
JS;
    }

    public function render()
    {
        $this->min(0);
        $this->addElementClass('num-no-stepper');
        $this->defaultAttribute('style', 'width: 12em;flex:none');

        Admin::style(<<<'CSS'
.number-group input.num-no-stepper {
    text-align: left !important;
}
CSS);

        return parent::render();
    }
}
