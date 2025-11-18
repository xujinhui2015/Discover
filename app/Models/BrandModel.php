<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BrandModel extends Model
{
    use SoftDeletes;
    protected $table = 'brand';
    protected $guarded = ['id'];
}
