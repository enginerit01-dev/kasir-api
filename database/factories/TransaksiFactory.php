<?php

namespace Database\Factories;

use App\Models\Transaksi;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransaksiFactory extends Factory
{
    protected $model = Transaksi::class;

    public function definition(): array
    {
        return [
            'kode_transaksi' => 'TRX' . now()->format('YmdHis') . $this->faker->unique()->numberBetween(100, 999),
            'tanggal' => now(),
            'subtotal' => 0, // Akan dihitung di Seeder
            'total_ppn' => 0,
            'grand_total' => 0,
            'nominal_bayar' => 0,
            'metode_pembayaran' => $this->faker->randomElement(['cash', 'debit', 'qris']),
            'kembalian' => 0,
            'status' => 1,
            'user_id' => null, // Diisi manual saat pemanggilan
            'toko_id' => null, // Diisi manual saat pemanggilan
        ];
    }
}
