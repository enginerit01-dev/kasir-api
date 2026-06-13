<?php

namespace Database\Seeders;

use App\Models\Transaksi;
use App\Models\DetailTransaksi;
use App\Models\Produk;
use App\Models\PengaturanToko;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TransaksiKhususSeeder extends Seeder
{
    public function run(): void
    {
        $tokoId = '01kty4zga9yfpqet6ttseswex1';
        $kasirId = '01kty5q7ny9tgmzwwqarfe0md9';

        // Ambil produk yang sudah ada di toko tersebut
        $produks = Produk::where('toko_id', $tokoId)->get();

        if ($produks->isEmpty()) {
            $this->command->error("Produk tidak ditemukan untuk toko ini ($tokoId). Sila buat produk terlebih dahulu.");
            return;
        }

        $pengaturan = PengaturanToko::where('toko_id', $tokoId)->first();
        $ppnPercent = $pengaturan?->ppn ?? 0;

        // Buat 10 transaksi secara acak
        for ($i = 0; $i < 10; $i++) {
            DB::transaction(function () use ($tokoId, $kasirId, $produks, $ppnPercent) {
                $transaksi = Transaksi::factory()->create([
                    'toko_id' => $tokoId,
                    'user_id' => $kasirId,
                    'tanggal' => now()->subHours(rand(1, 72)), // Sebar waktu dalam 3 hari terakhir
                ]);

                $subtotal = 0;
                // Setiap transaksi memiliki 1 sampai 4 jenis produk berbeda
                $items = $produks->random(rand(1, min(4, $produks->count())));

                foreach ($items as $produk) {
                    $qty = rand(1, 3);
                    $itemTotal = $produk->harga * $qty;

                    DetailTransaksi::create([
                        'transaksi_id' => $transaksi->id,
                        'produk_id' => $produk->id,
                        'jumlah' => $qty,
                        'harga_saat_transaksi' => $produk->harga,
                        'subtotal' => $itemTotal,
                    ]);

                    // Sinkronisasi stok (mengurangi stok produk)
                    $produk->decrement('stok', $qty);
                    $subtotal += $itemTotal;
                }

                $totalPpn = (int) round($subtotal * $ppnPercent / 100);
                $grandTotal = $subtotal + $totalPpn;
                $bayar = (int) ceil($grandTotal / 5000) * 5000; // Simulasi uang pas atau lebih

                $transaksi->update([
                    'subtotal' => $subtotal,
                    'total_ppn' => $totalPpn,
                    'grand_total' => $grandTotal,
                    'nominal_bayar' => $bayar,
                    'kembalian' => $bayar - $grandTotal,
                ]);
            });
        }

        $this->command->info("10 Transaksi berhasil dibuat untuk Toko: $tokoId dan Kasir: $kasirId");
    }
}
