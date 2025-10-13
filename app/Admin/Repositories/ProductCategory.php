<?php

namespace App\Admin\Repositories;

use App\Models\ProductCategoryModel as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class ProductCategory extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;
}
