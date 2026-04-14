<?php

namespace Tests\Feature;

use App\Models\Produk;
use App\Models\Toko;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TransaksiKasirTest extends TestCase
{
    use RefreshDatabase;

    private function createKasirAndProduk($stok = 10, $harga = 10000)
    {
        $toko = Toko::factory()->create();
        $kategori = \App\Models\KategoriProduk::factory()->create();
        $kasir = User::factory()->create(['role' => 'kasir', 'toko_id' => $toko->id]);
        $produk = Produk::factory()->create([
            'stok' => $stok,
            'harga' => $harga,
            'toko_id' => $toko->id,
            'kategori_id' => $kategori->id,
        ]);
        return [$kasir, $produk, $toko];
    }

    public function test_transaksi_sukses(): void
    {
        [$kasir, $produk, $toko] = $this->createKasirAndProduk();
        Sanctum::actingAs($kasir);
        $payload = [
            'items' => [
                ['produk_id' => $produk->id, 'jumlah' => 2],
            ],
            'metode_pembayaran' => 'cash',
            'nominal_bayar' => 25000,
        ];
        $res = $this->postJson('/api/transaksi', $payload);
        $res->assertCreated();
        $this->assertDatabaseHas('transaksi', [
            'user_id' => $kasir->id,
            'toko_id' => $toko->id,
        ]);
        $this->assertDatabaseHas('detail_transaksi', [
            'produk_id' => $produk->id,
            'jumlah' => 2,
        ]);
        $this->assertEquals(8, $produk->fresh()->stok);
    }

    public function test_transaksi_gagal_stok_kurang(): void
    {
        [$kasir, $produk, $toko] = $this->createKasirAndProduk(1);
        Sanctum::actingAs($kasir);
        $payload = [
            'items' => [
                ['produk_id' => $produk->id, 'jumlah' => 5],
            ],
            'metode_pembayaran' => 'cash',
            'nominal_bayar' => 100000,
        ];
        $res = $this->postJson('/api/transaksi', $payload);
        $res->assertStatus(400);
        $this->assertDatabaseMissing('transaksi', [
            'user_id' => $kasir->id,
            'toko_id' => $toko->id,
        ]);
    }
}
