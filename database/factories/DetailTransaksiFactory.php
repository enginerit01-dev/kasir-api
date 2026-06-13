<?php

namespace Database\Factories;

use App\Models\DetailTransaksi;
use Illuminate\Database\Eloquent\Factories\Factory;

class DetailTransaksiFactory extends Factory
{
    protected $model = DetailTransaksi::class;

    public function definition(): array
    {
        return [
            'transaksi_id' => null,
            'produk_id' => null,
            'jumlah' => $this->faker->numberBetween(1, 5),
            'harga_saat_transaksi' => 0,
            'subtotal' => 0,
        ];
    }
}
