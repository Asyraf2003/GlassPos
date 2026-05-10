<?php

declare(strict_types=1);

namespace Tests\Feature\IdentityAccess;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class DisableAdminTransactionCapabilityFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_disable_admin_transaction_capability(): void
    {
        $this->seedActiveTargetAdmin('target-admin-1');

        $response = $this->postJson(route('identity-access.admin-transaction-capability.disable'), [
            'target_actor_id' => 'target-admin-1',
            'performed_by_actor_id' => 'spoofed-actor',
        ]);

        $response->assertStatus(401);
        $this->assertDatabaseHas('admin_transaction_capability_states', [
            'actor_id' => 'target-admin-1',
            'active' => 1,
        ]);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_kasir_cannot_disable_admin_transaction_capability(): void
    {
        $this->seedActiveTargetAdmin('target-admin-1');
        $kasir = $this->createUserWithRole('kasir-toggle-disable@example.test', 'kasir');

        $response = $this->actingAs($kasir)->postJson(route('identity-access.admin-transaction-capability.disable'), [
            'target_actor_id' => 'target-admin-1',
            'performed_by_actor_id' => 'spoofed-actor',
        ]);

        $response->assertRedirect(route('cashier.dashboard'));
        $this->assertDatabaseHas('admin_transaction_capability_states', [
            'actor_id' => 'target-admin-1',
            'active' => 1,
        ]);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_admin_disable_uses_authenticated_actor_for_audit_not_payload(): void
    {
        $this->seedActiveTargetAdmin('target-admin-1');
        $admin = $this->createUserWithRole('admin-toggle-disable@example.test', 'admin');

        $response = $this->actingAs($admin)->postJson(route('identity-access.admin-transaction-capability.disable'), [
            'target_actor_id' => 'target-admin-1',
            'performed_by_actor_id' => 'spoofed-actor',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('admin_transaction_capability_states', [
            'actor_id' => 'target-admin-1',
            'active' => 0,
        ]);

        $audit = DB::table('audit_logs')
            ->where('event', 'admin_transaction_capability_disabled')
            ->first();

        self::assertNotNull($audit);
        $context = json_decode((string) $audit->context, true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('target-admin-1', $context['target_actor_id']);
        self::assertSame((string) $admin->getAuthIdentifier(), $context['performed_by_actor_id']);
        self::assertNotSame('spoofed-actor', $context['performed_by_actor_id']);
    }

    private function seedActiveTargetAdmin(string $actorId): void
    {
        DB::table('actor_accesses')->insert([
            'actor_id' => $actorId,
            'role' => 'admin',
        ]);

        DB::table('admin_transaction_capability_states')->insert([
            'actor_id' => $actorId,
            'active' => true,
        ]);
    }

    private function createUserWithRole(string $email, string $role): User
    {
        $user = User::query()->create([
            'name' => $email,
            'email' => $email,
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => $role,
        ]);

        return $user;
    }
}
