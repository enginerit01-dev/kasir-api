<?php

namespace App\Services;

use App\Models\Produk;
use App\Models\Transaksi;
use App\Models\DetailTransaksi;
use Illuminate\Support\Facades\DB;
use Exception;

class TransaksiService
{
    /**
     * Proses transaksi kasir
     *
     * @param array $data
     *   $data = [
     *     'items' => [
     *         ['produk_id' => int, 'qty' => int, 'harga' => int],
     *         ...
     *     ],
     *     'bayar' => int,
     *     'ppn' => float (misal 0.11 untuk 11%)
     *   ]
     * @return array
     * @throws Exception
     */
    public function prosesTransaksi(array $data)
    {
        return DB::transaction(function () use ($data) {
            $subtotal = 0;
            $items = [];

            // Ambil produk, validasi stok, hitung subtotal
            foreach ($data['items'] as $item) {
                $produk = Produk::lockForUpdate()->find($item['produk_id']);
                if (!$produk) {
                    throw new Exception('Produk tidak ditemukan');
                }
                if ($produk->stok < $item['qty']) {
                    throw new Exception('Stok produk tidak cukup: ' . $produk->nama);
                }
                $total = $item['qty'] * $item['harga'];
                $subtotal += $total;
                $items[] = [
                    'produk' => $produk,
                    'qty' => $item['qty'],
                    'harga' => $item['harga'],
                    'total' => $total,
                ];
            }

            // Hitung ppn dan grand total
            $ppn = isset($data['ppn']) ? $data['ppn'] : 0;
            $ppn_nominal = $subtotal * $ppn;
            $grand_total = $subtotal + $ppn_nominal;

            // Hitung kembalian
            $bayar = $data['bayar'];
            if ($bayar < $grand_total) {
                throw new Exception('Uang bayar kurang');
            }
            $kembalian = $bayar - $grand_total;

            // Simpan transaksi
            $transaksi = Transaksi::create([
                'subtotal' => $subtotal,
                'ppn' => $ppn_nominal,
                'grand_total' => $grand_total,
                'bayar' => $bayar,
                'kembalian' => $kembalian,
            ]);

            // Simpan detail transaksi & kurangi stok
            foreach ($items as $item) {
                DetailTransaksi::create([
                    'transaksi_id' => $transaksi->id,
                    'produk_id' => $item['produk']->id,
                    'qty' => $item['qty'],
                    'harga' => $item['harga'],
                    'total' => $item['total'],
                ]);
                $item['produk']->decrement('stok', $item['qty']);
            }

            return [
                'transaksi' => $transaksi,
                'items' => $items,
                'kembalian' => $kembalian,
            ];
        });
    }
}
