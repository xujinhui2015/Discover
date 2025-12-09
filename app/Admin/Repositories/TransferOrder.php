<?php
namespace App\Admin\Repositories;
use App\Models\TransferOrderModel as Model;
use Dcat\Admin\Repositories\EloquentRepository;

class TransferOrder extends EloquentRepository
{
    protected $eloquentClass = Model::class;
}
