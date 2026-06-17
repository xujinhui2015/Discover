<?php

/*
 * // +----------------------------------------------------------------------
 * // | erp
 * // +----------------------------------------------------------------------
 * // | Copyright (c) 2006~2020 erp All rights reserved.
 * // +----------------------------------------------------------------------
 * // | Licensed ( LICENSE-1.0.0 )
 * // +----------------------------------------------------------------------
 */

namespace App\Admin\Actions\Grid;

use App\Admin\Forms\MakeProductReplenishForm;
use Dcat\Admin\Grid\RowAction;
use Dcat\Admin\Widgets\Modal;

class MakeProductReplenish extends RowAction
{
    /**
     * @return string
     */
    protected $title = '二次补入库';

    public function render()
    {
        $form = MakeProductReplenishForm::make()->payload(['id' => $this->getKey()]);

        $style = 'display:block; padding:6px 10px; margin:4px 0; border-radius:4px; text-align:center; cursor:pointer; transition:all 0.2s ease; width:100%; box-sizing:border-box; white-space:nowrap;';
        $button = <<<HTML
<a style="{$style}" class="btn btn-sm btn-warning grid-actions-btn" href="javascript:void(0)">{$this->title}</a>
HTML;

        return Modal::make()
            ->lg()
            ->title($this->title)
            ->body($form)
            ->button($button);
    }
}
