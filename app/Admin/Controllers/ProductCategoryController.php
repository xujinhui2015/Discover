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

namespace App\Admin\Controllers;

use App\Admin\Actions\Grid\BatchCreateProSave;
use App\Admin\Repositories\Product;
use App\Admin\Repositories\ProductCategory;
use App\Models\AttrModel;
use App\Models\BrandModel;
use App\Models\ProductCategoryModel;
use App\Models\ProductModel;
use App\Repositories\BrandRepository;
use App\Repositories\ProductRepository;
use App\Repositories\UnitRepository;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Controllers\AdminController;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Layout\Row;
use Dcat\Admin\Tree;

class ProductCategoryController extends AdminController
{

    public function index(Content $content): Content
    {
        return $content->header('物料分类')
            ->body(function (Row $row) {
                $tree = new Tree(new ProductCategory());

                $tree->branch(function ($branch) {
                    return $branch['title'];
                });

                $row->column(12, $tree);

                $tree->disableCreateButton();
                $tree->disableDeleteButton();
                $tree->disableEditButton();

            });
    }

    protected function form()
    {
        return Form::make(new ProductCategory(), function (Form $form) {
            $form->select('parent_id')
                ->options(ProductCategoryModel::selectOptions())
                ->default(0)
                ->required();
            $form->text('title')->required();


        });
    }
}
