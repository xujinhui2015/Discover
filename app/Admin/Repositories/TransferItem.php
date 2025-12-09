<?php

namespace App\Admin\Repositories;

use App\Models\TransferItemModel as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class TransferItem extends EloquentRepository
{
    /**
     * Model.
     *
     * @var string
     */
    protected $eloquentClass = Model::class;
}
