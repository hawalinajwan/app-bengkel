<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_token_and_user(): void
    {
        $user = User::factory()->create([
            'email' => 'kasir@bengkel.test',
            'password' => 'password',
            'role' => 'kasir',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'kasir@bengkel.test',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonPath('user.email', $user->email)
            ->assertJsonPath('user.role', 'kasir')
            ->assertJsonStructure(['token', 'user' => ['id', 'name', 'email', 'role']]);
    }

    public function test_login_with_wrong_password_returns_422(): void
    {
        User::factory()->create(['email' => 'kasir@bengkel.test', 'password' => 'password']);

        $this->postJson('/api/auth/login', [
            'email' => 'kasir@bengkel.test',
            'password' => 'salah',
        ])->assertStatus(422)->assertJsonStructure(['message', 'errors' => ['email']]);
    }

    public function test_me_requires_token(): void
    {
        $this->getJson('/api/auth/me')
            ->assertStatus(401)
            ->assertJsonPath('message', 'Belum login.');
    }

    public function test_logout_revokes_current_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)->postJson('/api/auth/logout')->assertOk();

        // guard Sanctum menyimpan user di memori selama satu proses test
        $this->app['auth']->forgetGuards();

        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->withToken($token)->getJson('/api/auth/me')->assertStatus(401);
    }
}
