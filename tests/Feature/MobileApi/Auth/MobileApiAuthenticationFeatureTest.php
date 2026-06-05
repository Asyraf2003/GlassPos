<?php

declare(strict_types=1);

namespace Tests\Feature\MobileApi\Auth;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class MobileApiAuthenticationFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_login_to_mobile_api_and_receives_token_payload(): void
    {
        $user = $this->createUserWithRole(
            email: 'mobile-admin@example.test',
            role: 'admin',
            name: 'Mobile Admin',
        );

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'mobile-admin@example.test',
            'password' => 'password123',
            'device_name' => 'Redmi 12',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'data' => [
                'token_type' => 'Bearer',
                'actor' => [
                    'id' => (string) $user->getAuthIdentifier(),
                    'name' => 'Mobile Admin',
                    'email' => 'mobile-admin@example.test',
                    'role' => 'admin',
                ],
            ],
        ]);
        $response->assertJsonPath('errors', null);
        $response->assertJsonPath('data.token_type', 'Bearer');
        $this->assertIsString($response->json('data.token'));
        $this->assertNotSame('', $response->json('data.token'));
        $this->assertIsString($response->json('data.expires_at'));
    }

    public function test_kasir_can_login_to_mobile_api_and_receives_token_payload(): void
    {
        $user = $this->createUserWithRole(
            email: 'mobile-kasir@example.test',
            role: 'kasir',
            name: 'Mobile Kasir',
        );

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'mobile-kasir@example.test',
            'password' => 'password123',
            'device_name' => 'Redmi 12',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'data' => [
                'token_type' => 'Bearer',
                'actor' => [
                    'id' => (string) $user->getAuthIdentifier(),
                    'name' => 'Mobile Kasir',
                    'email' => 'mobile-kasir@example.test',
                    'role' => 'kasir',
                ],
            ],
        ]);
        $this->assertIsString($response->json('data.token'));
        $this->assertNotSame('', $response->json('data.token'));
    }

    public function test_invalid_mobile_api_login_is_rejected_with_safe_payload(): void
    {
        $this->createUserWithRole(
            email: 'mobile-invalid@example.test',
            role: 'admin',
            name: 'Mobile Invalid',
        );

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'mobile-invalid@example.test',
            'password' => 'wrong-password',
            'device_name' => 'Redmi 12',
        ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'data' => null,
            'message' => 'Email atau password tidak valid.',
            'errors' => [
                'email' => ['AUTH_FAILED'],
            ],
        ]);
    }

    public function test_mobile_api_login_is_rate_limited_after_repeated_invalid_attempts(): void
    {
        $this->createUserWithRole(
            email: 'mobile-throttle@example.test',
            role: 'admin',
            name: 'Mobile Throttle',
        );

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->postJson('/api/v1/auth/login', [
                'email' => 'mobile-throttle@example.test',
                'password' => 'wrong-password',
                'device_name' => 'Redmi 12',
            ])->assertStatus(422);
        }

        $this->postJson('/api/v1/auth/login', [
            'email' => 'mobile-throttle@example.test',
            'password' => 'wrong-password',
            'device_name' => 'Redmi 12',
        ])->assertStatus(429);
    }

    public function test_user_without_actor_access_is_rejected_for_mobile_api_login(): void
    {
        User::query()->create([
            'name' => 'Mobile Unknown Actor',
            'email' => 'mobile-unknown@example.test',
            'password' => 'password123',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'mobile-unknown@example.test',
            'password' => 'password123',
            'device_name' => 'Redmi 12',
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'data' => null,
            'message' => 'Aktor tidak dikenali.',
            'errors' => [
                'actor' => ['ACTOR_UNKNOWN'],
            ],
        ]);
    }

    public function test_mobile_api_me_requires_bearer_token(): void
    {
        $response = $this->getJson('/api/v1/me');

        $response->assertStatus(401);
        $response->assertJson([
            'success' => false,
            'data' => null,
            'message' => 'Autentikasi diperlukan.',
            'errors' => [
                'token' => ['UNAUTHENTICATED'],
            ],
        ]);
    }

    public function test_mobile_api_me_returns_current_actor_for_valid_token(): void
    {
        $user = $this->createUserWithRole(
            email: 'mobile-me@example.test',
            role: 'admin',
            name: 'Mobile Me',
        );

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => 'mobile-me@example.test',
            'password' => 'password123',
            'device_name' => 'Redmi 12',
        ]);

        $login->assertOk();

        $response = $this
            ->withHeader('Authorization', 'Bearer '.$login->json('data.token'))
            ->getJson('/api/v1/me');

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'data' => [
                'actor' => [
                    'id' => (string) $user->getAuthIdentifier(),
                    'name' => 'Mobile Me',
                    'email' => 'mobile-me@example.test',
                    'role' => 'admin',
                ],
            ],
            'errors' => null,
        ]);
    }

    public function test_mobile_api_logout_revokes_current_token(): void
    {
        $this->createUserWithRole(
            email: 'mobile-logout@example.test',
            role: 'kasir',
            name: 'Mobile Logout',
        );

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => 'mobile-logout@example.test',
            'password' => 'password123',
            'device_name' => 'Redmi 12',
        ]);

        $login->assertOk();

        $token = (string) $login->json('data.token');

        $logout = $this
            ->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/auth/logout');

        $logout->assertOk();
        $logout->assertJson([
            'success' => true,
            'data' => null,
            'message' => 'Logout berhasil.',
            'errors' => null,
        ]);

        $afterLogout = $this
            ->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/me');

        $afterLogout->assertStatus(401);
        $afterLogout->assertJson([
            'success' => false,
            'data' => null,
            'message' => 'Autentikasi diperlukan.',
            'errors' => [
                'token' => ['UNAUTHENTICATED'],
            ],
        ]);
    }

    private function createUserWithRole(string $email, string $role, string $name = 'Mobile User'): User
    {
        $user = User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => 'password123',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => $role,
        ]);

        return $user;
    }
}
