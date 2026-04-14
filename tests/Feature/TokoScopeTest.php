<?php

namespace Tests\Feature;

use App\Models\DetailTransaksi;
use App\Models\KategoriProduk;
use App\Models\PengaturanToko;
use App\Models\Produk;
use App\Models\Toko;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TokoScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_queries_are_limited_to_the_authenticated_users_toko(): void
    {
        $tokoA = Toko::factory()->create();
        $tokoB = Toko::factory()->create();

        $adminA = User::factory()->create([
            'role' => 'admin',
            'toko_id' => $tokoA->id,
        ]);

        $userA = User::factory()->create([
            'toko_id' => $tokoA->id,
        ]);

        User::factory()->create([
            'toko_id' => $tokoB->id,
        ]);

        Sanctum::actingAs($adminA);

        $users = User::query()->pluck('id');

        $this->assertCount(2, $users);
        $this->assertTrue($users->contains($adminA->id));
        $this->assertTrue($users->contains($userA->id));
    }

    public function test_produk_queries_are_limited_to_the_authenticated_users_toko(): void
    {
        $tokoA = Toko::factory()->create();
        $tokoB = Toko::factory()->create();
        $kategori = KategoriProduk::create([
            'kategori' => 'Minuman',
        ]);

        $adminA = User::factory()->create([
            'role' => 'admin',
            'toko_id' => $tokoA->id,
        ]);

        $produkA = Produk::create([
            'nama' => 'Es Teh',
            'harga' => 5000,
            'stok' => 20,
            'kategori_id' => $kategori->id,
            'kode_produk' => 'A-001',
            'is_active' => true,
            'toko_id' => $tokoA->id,
        ]);

        Produk::create([
            'nama' => 'Kopi',
            'harga' => 7000,
            'stok' => 15,
            'kategori_id' => $kategori->id,
            'kode_produk' => 'B-001',
            'is_active' => true,
            'toko_id' => $tokoB->id,
        ]);

        Sanctum::actingAs($adminA);

        $produk = Produk::query()->get();

        $this->assertCount(1, $produk);
        $this->assertSame($produkA->id, $produk->first()->id);
    }

    public function test_scoped_models_default_new_records_to_the_authenticated_users_toko(): void
    {
        $toko = Toko::factory()->create();
        $kategori = KategoriProduk::create([
            'kategori' => 'Snack',
        ]);
        $admin = User::factory()->create([
            'role' => 'admin',
            'toko_id' => $toko->id,
        ]);

        Sanctum::actingAs($admin);

        $produk = Produk::create([
            'nama' => 'Keripik',
            'harga' => 10000,
            'stok' => 10,
            'kategori_id' => $kategori->id,
            'kode_produk' => 'SNACK-01',
            'is_active' => true,
        ]);

        $pengaturan = PengaturanToko::create([
            'ppn' => 11,
            'catatan' => 'Default toko aktif',
        ]);

        $this->assertSame($toko->id, $produk->toko_id);
        $this->assertSame($toko->id, $pengaturan->toko_id);
    }

    public function test_detail_transaksi_queries_follow_transaksi_toko_scope(): void
    {
        $tokoA = Toko::factory()->create();
        $tokoB = Toko::factory()->create();
        $kategori = KategoriProduk::create([
            'kategori' => 'Makanan',
        ]);

        $adminA = User::factory()->create([
            'role' => 'admin',
            'toko_id' => $tokoA->id,
        ]);

        $kasirB = User::factory()->create([
            'role' => 'kasir',
            'toko_id' => $tokoB->id,
        ]);

        $produkA = Produk::withoutGlobalScopes()->create([
            'nama' => 'Nasi Goreng',
            'harga' => 20000,
            'stok' => 8,
            'kategori_id' => $kategori->id,
            'kode_produk' => 'MAKAN-01',
            'is_active' => true,
            'toko_id' => $tokoA->id,
        ]);

        $produkB = Produk::withoutGlobalScopes()->create([
            'nama' => 'Mie Goreng',
            'harga' => 18000,
            'stok' => 9,
            'kategori_id' => $kategori->id,
            'kode_produk' => 'MAKAN-02',
            'is_active' => true,
            'toko_id' => $tokoB->id,
        ]);

        $transaksiA = Transaksi::withoutGlobalScopes()->create([
            'kode_transaksi' => 'TRX-A',
            'tanggal' => now(),
            'subtotal' => 20000,
            'total_ppn' => 2200,
            'grand_total' => 22200,
            'nominal_bayar' => 25000,
            'metode_pembayaran' => 'cash',
            'kembalian' => 2800,
            'status' => 1,
            'user_id' => $adminA->id,
            'toko_id' => $tokoA->id,
        ]);

        $transaksiB = Transaksi::withoutGlobalScopes()->create([
            'kode_transaksi' => 'TRX-B',
            'tanggal' => now(),
            'subtotal' => 18000,
            'total_ppn' => 1980,
            'grand_total' => 19980,
            'nominal_bayar' => 20000,
            'metode_pembayaran' => 'cash',
            'kembalian' => 20,
            'status' => 1,
            'user_id' => $kasirB->id,
            'toko_id' => $tokoB->id,
        ]);

        $detailA = DetailTransaksi::create([
            'jumlah' => 1,
            'harga_saat_transaksi' => 20000,
            'subtotal' => 20000,
            'transaksi_id' => $transaksiA->id,
            'produk_id' => $produkA->id,
        ]);

        DetailTransaksi::create([
            'jumlah' => 1,
            'harga_saat_transaksi' => 18000,
            'subtotal' => 18000,
            'transaksi_id' => $transaksiB->id,
            'produk_id' => $produkB->id,
        ]);

        Sanctum::actingAs($adminA);

        $detail = DetailTransaksi::query()->get();

        $this->assertCount(1, $detail);
        $this->assertSame($detailA->id, $detail->first()->id);
    }
}
