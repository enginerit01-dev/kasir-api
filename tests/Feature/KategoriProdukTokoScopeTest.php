<?php

namespace Tests\Feature;

use App\Models\KategoriProduk;
use App\Models\Toko;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class KategoriProdukTokoScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_kategori_produk_queries_are_limited_to_the_authenticated_users_toko(): void
    {
        $tokoA = Toko::factory()->create(['nama' => 'Toko A']);
        $tokoB = Toko::factory()->create(['nama' => 'Toko B']);

        $adminA = User::factory()->create(['role' => 'staff']);
        $adminA->tokoTugas()->attach($tokoA->id, ['role' => 'admin', 'is_active' => true]);

        $katA = KategoriProduk::withoutGlobalScopes()->create([
            'kategori' => 'Minuman',
            'toko_id' => $tokoA->id,
        ]);

        $katB = KategoriProduk::withoutGlobalScopes()->create([
            'kategori' => 'Makanan',
            'toko_id' => $tokoB->id,
        ]);

        Sanctum::actingAs($adminA);

        $kategoriList = KategoriProduk::query()->get();

        $this->assertCount(1, $kategoriList);
        $this->assertSame($katA->id, $kategoriList->first()->id);
        $this->assertSame('Toko A', $kategoriList->first()->nama_toko);
    }

    public function test_can_create_same_category_name_in_different_tokos_but_not_in_same_toko(): void
    {
        $tokoA = Toko::factory()->create(['nama' => 'Toko A']);
        $tokoB = Toko::factory()->create(['nama' => 'Toko B']);

        $adminA = User::factory()->create(['role' => 'staff']);
        $adminA->tokoTugas()->attach($tokoA->id, ['role' => 'admin', 'is_active' => true]);

        $adminB = User::factory()->create(['role' => 'staff']);
        $adminB->tokoTugas()->attach($tokoB->id, ['role' => 'admin', 'is_active' => true]);

        // Create in Toko A
        Sanctum::actingAs($adminA);
        $responseA = $this->postJson('/api/kategori-produk', [
            'kategori' => 'Minuman',
        ]);
        $responseA->assertStatus(201);

        // Attempt duplicate in Toko A -> should fail validation
        $responseADup = $this->postJson('/api/kategori-produk', [
            'kategori' => 'Minuman',
        ]);
        $responseADup->assertStatus(422);

        // Create same name in Toko B -> should succeed
        Sanctum::actingAs($adminB);
        $responseB = $this->postJson('/api/kategori-produk', [
            'kategori' => 'Minuman',
        ]);
        $responseB->assertStatus(201);
    }

    public function test_super_admin_sees_all_and_gets_appended_toko_info(): void
    {
        $tokoA = Toko::factory()->create(['nama' => 'Toko A']);
        $tokoB = Toko::factory()->create(['nama' => 'Toko B']);

        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $katA = KategoriProduk::withoutGlobalScopes()->create([
            'kategori' => 'Minuman',
            'toko_id' => $tokoA->id,
        ]);

        $katB = KategoriProduk::withoutGlobalScopes()->create([
            'kategori' => 'Makanan',
            'toko_id' => $tokoB->id,
        ]);

        Sanctum::actingAs($superAdmin);

        // Without query parameter, see all
        $responseAll = $this->getJson('/api/kategori-produk');
        $responseAll->assertStatus(200);
        $dataAll = $responseAll->json('data');

        $this->assertCount(2, $dataAll);
        $this->assertSame('Minuman', $dataAll[0]['kategori']);
        $this->assertSame('Toko A', $dataAll[0]['nama_toko']);
        $this->assertSame($tokoA->id, $dataAll[0]['toko_id']);
        $this->assertSame('Makanan', $dataAll[1]['kategori']);
        $this->assertSame('Toko B', $dataAll[1]['nama_toko']);
        $this->assertSame($tokoB->id, $dataAll[1]['toko_id']);

        // With query parameter, see only selected toko
        $responseFilter = $this->getJson('/api/kategori-produk?toko_id=' . $tokoA->id);
        $responseFilter->assertStatus(200);
        $dataFilter = $responseFilter->json('data');

        $this->assertCount(1, $dataFilter);
        $this->assertSame('Minuman', $dataFilter[0]['kategori']);
        $this->assertSame('Toko A', $dataFilter[0]['nama_toko']);
        $this->assertSame($tokoA->id, $dataFilter[0]['toko_id']);
    }
}
