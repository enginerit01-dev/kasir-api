<?php

namespace Database\Factories;

use App\Models\KategoriProduk;
use App\Models\Toko;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KategoriProduk>
 */
class KategoriProdukFactory extends Factory
{
    protected $model = KategoriProduk::class;

    public function definition(): array
    {
        return [
            'kategori' => 'Kategori '.fake()->word(),
        ];
    }
}
