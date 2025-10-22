<?php

namespace App\Admin\Renderables;

use App\Models\BrandModel;
use App\Models\ProductModel;
use App\Models\UnitModel;
use Dcat\Admin\Grid;
use Dcat\Admin\Grid\LazyRenderable;
use Dcat\Admin\Models\Administrator;

class ProductTable extends LazyRenderable
{
    public function grid(): Grid
    {

        return Grid::make(new ProductModel(), function (Grid $grid) {
            $grid->column('id');
            $grid->column('item_no', '物料编号')->emp();
            $grid->column('name', '物料名称');
            $grid->column('type', '分类')->using(ProductModel::TYPE);
            $grid->column('brand.name', '品牌')->emp();
            $grid->column('unit.name', '单位')->emp();
            $grid->column('warning_num')->emp();

//            $grid->quickSearch(['item_no']);

            $grid->paginate(10);

            $grid->filter(function (Grid\Filter $filter) {
//                $filter->expand(false); // 展开或者折叠
                $filter->like('name', '物料名称')->width(4);
                $filter->like('item_no', '物料编号')->width(4);
                $filter->equal('type', '分类')
                    ->select(ProductModel::TYPE)
                    ->width(4);
                $filter->equal('unit_id', '品牌')
                    ->select(BrandModel::query()->pluck('name', 'id'))
                    ->width(4);
                $filter->equal('brand_id', '单位')
                    ->select(UnitModel::query()->pluck('name', 'id'))
                    ->width(4);
            });
        });
    }
}
