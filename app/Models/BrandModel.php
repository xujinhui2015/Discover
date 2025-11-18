<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BrandModel extends BaseModel
{
    use SoftDeletes;
    protected $table = 'brand';
}
