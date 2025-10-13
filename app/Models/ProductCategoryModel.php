<?php

namespace App\Models;

use Dcat\Admin\Traits\ModelTree;
use Illuminate\Database\Eloquent\Model;

class ProductCategoryModel extends Model
{
    use ModelTree;

    protected $table = 'product_category';
}
