<?php

namespace Database\Factories;

use App\Models\Produk;
use App\Models\Toko;
use App\Models\KategoriProduk;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Produk>
 */
class ProdukFactory extends Factory
{
    protected $model = Produk::class;

    public function definition(): array
    {
        return [
            'nama' => 'Produk '.fake()->word(),
            'kode_produk' => 'PRD'.fake()->unique()->numerify('###'),
            'stok' => fake()->numberBetween(1, 100),
            'harga' => fake()->numberBetween(1000, 100000),
            'toko_id' => Toko::factory(),
            'kategori_id' => KategoriProduk::factory(),
        ];
    }
}
