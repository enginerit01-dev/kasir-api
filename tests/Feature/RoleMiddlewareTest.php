<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['auth:sanctum', 'role:admin'])->get('/api/test-admin-only', function () {
            return response()->json([
                'message' => 'Admin route accessed.',
            ]);
        });

        Route::middleware(['auth:sanctum', 'role:admin,kasir'])->get('/api/test-shared-role', function () {
            return response()->json([
                'message' => 'Shared route accessed.',
            ]);
        });
    }

    public function test_admin_can_access_admin_route(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/test-admin-only')
            ->assertOk()
            ->assertJsonPath('message', 'Admin route accessed.');
    }

    public function test_kasir_cannot_access_admin_route(): void
    {
        $user = User::factory()->create([
            'role' => 'kasir',
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/test-admin-only')
            ->assertForbidden()
            ->assertJsonPath('message', 'Anda tidak memiliki akses ke resource ini.');
    }

    public function test_kasir_can_access_shared_role_route(): void
    {
        $user = User::factory()->create([
            'role' => 'kasir',
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/test-shared-role')
            ->assertOk()
            ->assertJsonPath('message', 'Shared route accessed.');
    }
}
