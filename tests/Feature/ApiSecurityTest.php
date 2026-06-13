<?php

namespace Tests\Feature;

use App\Models\Toko;
use App\Models\User;
use App\Models\Produk;
use App\Models\KategoriProduk;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->kategori = KategoriProduk::create(['kategori' => 'Umum']);
    }

    /** @test */
    public function test_owner_cannot_access_other_store_products()
    {
        $tokoA = Toko::factory()->create();
        $tokoB = Toko::factory()->create();

        $ownerA = User::factory()->create(['role' => 'owner']);
        $tokoA->update(['owner_id' => $ownerA->id]);

        // Produk milik Toko B
        $produkB = Produk::create([
            'nama' => 'Produk Terlarang',
            'harga' => 1000,
            'stok' => 10,
            'kode_produk' => 'B001',
            'toko_id' => $tokoB->id,
            'kategori_id' => $this->kategori->id
        ]);

        Sanctum::actingAs($ownerA);

        // Coba akses detail produk Toko B
        $response = $this->getJson("/api/produk/{$produkB->id}");

        // Karena Global Scope, model tidak akan ditemukan (404) atau akses ditolak
        $response->assertStatus(404);
    }

    /** @test */
    public function test_admin_cannot_create_super_admin_role()
    {
        $toko = Toko::factory()->create();
        $admin = User::factory()->create(['role' => 'staff']);
        $admin->tokoTugas()->attach($toko->id, ['role' => 'admin', 'is_active' => true]);

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/user', [
            'name' => 'Evil Admin',
            'email' => 'evil@example.com',
            'username' => 'eviladmin',
            'password' => 'password123',
            'role' => 'super_admin', // Mencoba melampaui batas
            'toko_id' => $toko->id
        ]);

        // Harusnya diblokir dengan 403 Forbidden karena admin toko (staff) tidak memiliki akses untuk menambah user
        $response->assertStatus(403);
    }

    /** @test */
    public function test_kasir_cannot_delete_anyone()
    {
        $toko = Toko::factory()->create();
        $admin = User::factory()->create(['role' => 'staff']);
        $admin->tokoTugas()->attach($toko->id, ['role' => 'admin', 'is_active' => true]);

        $kasir = User::factory()->create(['role' => 'staff']);
        $kasir->tokoTugas()->attach($toko->id, ['role' => 'kasir', 'is_active' => true]);

        Sanctum::actingAs($kasir);

        $response = $this->deleteJson("/api/user/{$admin->id}");

        // Middleware 'role:super_admin,owner' di api.php akan memblokir ini
        $response->assertStatus(403);
    }

    /** @test */
    public function test_owner_cannot_create_another_owner()
    {
        $toko = Toko::factory()->create();
        $owner = User::factory()->create(['role' => 'owner']);
        $toko->update(['owner_id' => $owner->id]);

        Sanctum::actingAs($owner);

        $response = $this->postJson('/api/user', [
            'name' => 'Other Owner',
            'email' => 'other@owner.com',
            'username' => 'otherowner',
            'password' => 'password123',
            'role' => 'owner',
            'toko_id' => $toko->id
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function test_cross_toko_data_isolation_on_index()
    {
        $tokoA = Toko::factory()->create();
        $tokoB = Toko::factory()->create();

        $ownerA = User::factory()->create(['role' => 'owner']);
        $tokoA->update(['owner_id' => $ownerA->id]);

        Produk::create([
            'nama' => 'Produk A', 'harga' => 100, 'stok' => 10, 'kode_produk' => 'A1',
            'toko_id' => $tokoA->id, 'kategori_id' => $this->kategori->id
        ]);

        Produk::create([
            'nama' => 'Produk B', 'harga' => 100, 'stok' => 10, 'kode_produk' => 'B1',
            'toko_id' => $tokoB->id, 'kategori_id' => $this->kategori->id
        ]);

        Sanctum::actingAs($ownerA);

        $response = $this->getJson('/api/produk');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.nama', 'Produk A');
    }

    /** @test */
    public function test_super_admin_can_see_everything()
    {
        $tokoA = Toko::factory()->create();
        $tokoB = Toko::factory()->create();
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        Produk::create([
            'nama' => 'A', 'harga' => 10, 'stok' => 1, 'kode_produk' => 'A',
            'toko_id' => $tokoA->id, 'kategori_id' => $this->kategori->id
        ]);
        Produk::create([
            'nama' => 'B', 'harga' => 10, 'stok' => 1, 'kode_produk' => 'B',
            'toko_id' => $tokoB->id, 'kategori_id' => $this->kategori->id
        ]);

        Sanctum::actingAs($superAdmin);

        $response = $this->getJson('/api/produk');
        $response->assertJsonCount(2, 'data');
    }
}
