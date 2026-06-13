<?php

namespace Tests\Feature;

use App\Models\DetailTransaksi;
use App\Models\KategoriProduk;
use App\Models\Produk;
use App\Models\Toko;
use App\Models\Transaksi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SuperAdminStoreQueryTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private Toko $tokoA;
    private Toko $tokoB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create(['role' => 'super_admin']);
        $this->tokoA = Toko::factory()->create(['nama' => 'Toko A']);
        $this->tokoB = Toko::factory()->create(['nama' => 'Toko B']);
    }

    public function test_super_admin_can_create_category_with_specific_toko_id(): void
    {
        Sanctum::actingAs($this->superAdmin);

        $response = $this->postJson('/api/kategori-produk', [
            'kategori' => 'Gadget',
            'toko_id' => $this->tokoA->id,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('kategori_produk', [
            'kategori' => 'Gadget',
            'toko_id' => $this->tokoA->id,
        ]);
    }

    public function test_super_admin_can_create_product_with_specific_toko_id(): void
    {
        Sanctum::actingAs($this->superAdmin);

        $kategori = KategoriProduk::withoutGlobalScopes()->create([
            'kategori' => 'Elektronik',
            'toko_id' => $this->tokoA->id,
        ]);

        $response = $this->postJson('/api/produk', [
            'nama' => 'Laptop ASUS',
            'kategori_id' => $kategori->id,
            'harga' => 15000000,
            'stok' => 5,
            'kode_produk' => 'LP-001',
            'is_active' => true,
            'toko_id' => $this->tokoA->id,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('produk', [
            'nama' => 'Laptop ASUS',
            'toko_id' => $this->tokoA->id,
        ]);
    }

    public function test_super_admin_dashboard_without_toko_id_returns_all_stores(): void
    {
        // Create transactions in Toko A
        Transaksi::withoutGlobalScopes()->create([
            'kode_transaksi' => 'TRX-A1',
            'tanggal' => now(),
            'subtotal' => 10000,
            'total_ppn' => 1100,
            'grand_total' => 11100,
            'nominal_bayar' => 15000,
            'metode_pembayaran' => 'cash',
            'kembalian' => 3900,
            'status' => 1,
            'user_id' => $this->superAdmin->id,
            'toko_id' => $this->tokoA->id,
        ]);

        // Create transactions in Toko B
        Transaksi::withoutGlobalScopes()->create([
            'kode_transaksi' => 'TRX-B1',
            'tanggal' => now(),
            'subtotal' => 20000,
            'total_ppn' => 2200,
            'grand_total' => 22200,
            'nominal_bayar' => 25000,
            'metode_pembayaran' => 'cash',
            'kembalian' => 2800,
            'status' => 1,
            'user_id' => $this->superAdmin->id,
            'toko_id' => $this->tokoB->id,
        ]);

        Sanctum::actingAs($this->superAdmin);

        $response = $this->getJson('/api/dashboard');

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertCount(2, $data);

        // Find Toko A summary
        $tokoASummary = collect($data)->firstWhere('toko_id', $this->tokoA->id);
        $this->assertNotNull($tokoASummary);
        $this->assertSame('Toko A', $tokoASummary['nama_toko']);
        $this->assertSame(11100, $tokoASummary['total_omzet']);
        $this->assertSame(1, $tokoASummary['total_transaksi']);

        // Find Toko B summary
        $tokoBSummary = collect($data)->firstWhere('toko_id', $this->tokoB->id);
        $this->assertNotNull($tokoBSummary);
        $this->assertSame('Toko B', $tokoBSummary['nama_toko']);
        $this->assertSame(22200, $tokoBSummary['total_omzet']);
        $this->assertSame(1, $tokoBSummary['total_transaksi']);
    }

    public function test_super_admin_dashboard_with_toko_id_returns_only_single_store(): void
    {
        Transaksi::withoutGlobalScopes()->create([
            'kode_transaksi' => 'TRX-A1',
            'tanggal' => now(),
            'subtotal' => 10000,
            'total_ppn' => 1100,
            'grand_total' => 11100,
            'nominal_bayar' => 15000,
            'metode_pembayaran' => 'cash',
            'kembalian' => 3900,
            'status' => 1,
            'user_id' => $this->superAdmin->id,
            'toko_id' => $this->tokoA->id,
        ]);

        Sanctum::actingAs($this->superAdmin);

        $response = $this->getJson('/api/dashboard?toko_id=' . $this->tokoA->id);

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertArrayHasKey('toko_id', $data);
        $this->assertSame($this->tokoA->id, $data['toko_id']);
        $this->assertSame('Toko A', $data['nama_toko']);
        $this->assertSame(11100, $data['total_omzet']);
    }

    public function test_super_admin_laporan_keuangan_without_toko_id_returns_all_stores(): void
    {
        Transaksi::withoutGlobalScopes()->create([
            'kode_transaksi' => 'TRX-A1',
            'tanggal' => now(),
            'subtotal' => 10000,
            'total_ppn' => 1100,
            'grand_total' => 11100,
            'nominal_bayar' => 15000,
            'metode_pembayaran' => 'cash',
            'kembalian' => 3900,
            'status' => 1,
            'user_id' => $this->superAdmin->id,
            'toko_id' => $this->tokoA->id,
        ]);

        Transaksi::withoutGlobalScopes()->create([
            'kode_transaksi' => 'TRX-B1',
            'tanggal' => now(),
            'subtotal' => 20000,
            'total_ppn' => 2200,
            'grand_total' => 22200,
            'nominal_bayar' => 25000,
            'metode_pembayaran' => 'cash',
            'kembalian' => 2800,
            'status' => 1,
            'user_id' => $this->superAdmin->id,
            'toko_id' => $this->tokoB->id,
        ]);

        Sanctum::actingAs($this->superAdmin);

        $response = $this->getJson('/api/laporan/keuangan');

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertCount(2, $data);
        $this->assertSame('Toko A', $data[0]['nama_toko']);
        $this->assertSame('Toko B', $data[1]['nama_toko']);
    }

    public function test_super_admin_laporan_keuangan_with_toko_id_returns_only_single_store(): void
    {
        Transaksi::withoutGlobalScopes()->create([
            'kode_transaksi' => 'TRX-A1',
            'tanggal' => now(),
            'subtotal' => 10000,
            'total_ppn' => 1100,
            'grand_total' => 11100,
            'nominal_bayar' => 15000,
            'metode_pembayaran' => 'cash',
            'kembalian' => 3900,
            'status' => 1,
            'user_id' => $this->superAdmin->id,
            'toko_id' => $this->tokoA->id,
        ]);

        Transaksi::withoutGlobalScopes()->create([
            'kode_transaksi' => 'TRX-B1',
            'tanggal' => now(),
            'subtotal' => 20000,
            'total_ppn' => 2200,
            'grand_total' => 22200,
            'nominal_bayar' => 25000,
            'metode_pembayaran' => 'cash',
            'kembalian' => 2800,
            'status' => 1,
            'user_id' => $this->superAdmin->id,
            'toko_id' => $this->tokoB->id,
        ]);

        Sanctum::actingAs($this->superAdmin);

        $response = $this->getJson('/api/laporan/keuangan?toko_id=' . $this->tokoA->id);

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertCount(1, $data);
        $this->assertSame('Toko A', $data[0]['nama_toko']);
        $this->assertSame($this->tokoA->id, $data[0]['toko_id']);
    }
}
