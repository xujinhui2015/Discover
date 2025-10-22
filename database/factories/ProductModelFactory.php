<?php

namespace Database\Factories;

use App\Models\ProductModel;
use App\Models\UnitModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class ProductModelFactory extends Factory
{
    protected $model = ProductModel::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'py_code' => $this->faker->word(),
            'item_no' => $this->faker->word(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'type' => 1,
            'warning_num' => $this->faker->randomNumber(),

            'unit_id' => 1,
            'brand_id' => 1,
        ];
    }
}
