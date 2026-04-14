<?php

namespace Tests\Feature;

use App\Models\Toko;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_receive_token(): void
    {
        $toko = Toko::factory()->create();

        $response = $this->postJson('/api/auth/register', [
            'name' => 'Admin Kasir',
            'email' => 'admin@example.com',
            'username' => 'adminkasir',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin',
            'toko_id' => $toko->id,
        ]);

        $response
            ->assertCreated()
            ->assertJsonStructure([
                'message',
                'token',
                'user' => ['id', 'name', 'email', 'username', 'role', 'toko_id', 'toko'],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'admin@example.com',
            'username' => 'adminkasir',
            'role' => 'admin',
        ]);
    }

    public function test_user_can_login_with_username_and_logout(): void
    {
        $user = User::factory()->create([
            'username' => 'kasir1',
            'email' => 'kasir@example.com',
            'password' => 'password123',
            'role' => 'kasir',
        ]);

        $loginResponse = $this->postJson('/api/auth/login', [
            'login' => 'kasir1',
            'password' => 'password123',
        ]);

        $token = $loginResponse->json('token');

        $loginResponse
            ->assertOk()
            ->assertJsonPath('user.id', $user->id);

        $this->assertNotEmpty($token);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('user.username', 'kasir1');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/auth/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Logout berhasil.');
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->create([
            'password' => 'password123',
            'is_active' => false,
        ]);

        $this->postJson('/api/auth/login', [
            'login' => $user->email,
            'password' => 'password123',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('login');
    }
}
