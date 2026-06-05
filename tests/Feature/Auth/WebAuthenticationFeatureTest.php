<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class WebAuthenticationFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_login_and_is_redirected_to_admin_dashboard(): void
    {
        $user = User::query()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.test',
            'password' => 'password123',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'admin',
        ]);

        $response = $this->post(route('login.attempt'), [
            'email' => 'admin@example.test',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_kasir_can_login_and_is_redirected_to_cashier_dashboard(): void
    {
        $user = User::query()->create([
            'name' => 'Kasir User',
            'email' => 'kasir@example.test',
            'password' => 'password123',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        $response = $this->post(route('login.attempt'), [
            'email' => 'kasir@example.test',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('cashier.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_login_is_rejected_and_user_remains_guest(): void
    {
        User::query()->create([
            'name' => 'Invalid Login User',
            'email' => 'invalid@example.test',
            'password' => 'password123',
        ]);

        $response = $this
            ->from(route('login'))
            ->post(route('login.attempt'), [
                'email' => 'invalid@example.test',
                'password' => 'wrong-password',
            ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors([
            'email' => 'Email atau password tidak valid.',
        ]);

        $this->assertGuest();
    }

    public function test_web_login_is_rate_limited_after_repeated_invalid_attempts(): void
    {
        User::query()->create([
            'name' => 'Throttled Login User',
            'email' => 'web-throttle@example.test',
            'password' => 'password123',
        ]);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this
                ->from(route('login'))
                ->post(route('login.attempt'), [
                    'email' => 'web-throttle@example.test',
                    'password' => 'wrong-password',
                ])
                ->assertRedirect(route('login'));
        }

        $this
            ->from(route('login'))
            ->post(route('login.attempt'), [
                'email' => 'web-throttle@example.test',
                'password' => 'wrong-password',
            ])
            ->assertStatus(429);

        $this->assertGuest();
    }

    public function test_user_without_actor_access_is_logged_out_and_redirected_back_to_login(): void
    {
        User::query()->create([
            'name' => 'Unknown Actor User',
            'email' => 'unknown-actor@example.test',
            'password' => 'password123',
        ]);

        $response = $this->post(route('login.attempt'), [
            'email' => 'unknown-actor@example.test',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error', 'Aktor tidak dikenali.');

        $this->assertGuest();
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::query()->create([
            'name' => 'Logout User',
            'email' => 'logout@example.test',
            'password' => 'password123',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'admin',
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('logout'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('success', 'Logout berhasil.');

        $this->assertGuest();
    }
}
