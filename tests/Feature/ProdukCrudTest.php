<?php

namespace Tests\Feature;

use App\Models\Produk;
use App\Models\Toko;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProdukCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_crud_produk(): void
    {
        $toko = Toko::factory()->create();
        $kategori = \App\Models\KategoriProduk::factory()->create();
        $admin = User::factory()->create(['role' => 'admin', 'toko_id' => $toko->id]);
        Sanctum::actingAs($admin);

        // CREATE
        $create = $this->postJson('/api/produk', [
            'nama' => 'Produk A',
            'stok' => 10,
            'harga' => 10000,
            'kategori_id' => $kategori->id,
            'kode_produk' => 'PRD001',
        ]);
        $create->assertCreated();
        $produkId = $create->json('id');

        // READ
        $read = $this->getJson('/api/produk/'.$produkId);
        $read->assertOk()->assertJsonPath('nama', 'Produk A');

        // UPDATE
        $update = $this->putJson('/api/produk/'.$produkId, [
            'nama' => 'Produk B',
            'stok' => 20,
            'harga' => 15000,
            'kategori_id' => $kategori->id,
            'kode_produk' => 'PRD001',
        ]);
        $update->assertOk()->assertJsonPath('nama', 'Produk B');

        // DELETE
        $delete = $this->deleteJson('/api/produk/'.$produkId);
        $delete->assertNoContent();
        $this->assertDatabaseMissing('produk', ['id' => $produkId]);
    }
}
