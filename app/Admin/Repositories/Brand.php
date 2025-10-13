<?php

namespace App\Admin\Repositories;

use App\Models\BrandModel as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class Brand extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;
}
