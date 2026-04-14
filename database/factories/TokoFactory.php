<?php

namespace Database\Factories;

use App\Models\Toko;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Toko>
 */
class TokoFactory extends Factory
{
    protected $model = Toko::class;

    public function definition(): array
    {
        return [
            'nama' => 'Toko '.fake()->company(),
            'alamat' => fake()->address(),
            'telepon' => fake()->numerify('08##########'),
            'is_active' => true,
        ];
    }
}
