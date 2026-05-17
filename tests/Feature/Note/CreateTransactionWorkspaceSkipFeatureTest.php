<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class CreateTransactionWorkspaceSkipFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_can_store_workspace_and_redirect_to_history_when_skipping_payment(): void
    {
        $this->loginAsKasir();
        $user = User::query()->create(['name' => 'Kasir Aktif', 'email' => 'skip@example.test', 'password' => 'password']);
        DB::table('actor_accesses')->insert(['actor_id' => (string) $user->getAuthIdentifier(), 'role' => 'kasir']);

        $response = $this->actingAs($user)->post(route('notes.workspace.store'), [
            'note' => ['customer_name' => 'Budi', 'customer_phone' => '08123', 'transaction_date' => '2026-03-15'],
            'items' => [[
                'entry_mode' => 'service', 'part_source' => 'none', 'pay_now' => 0,
                'service' => ['name' => 'Servis A', 'price_rupiah' => 50000, 'notes' => ''],
                'product_lines' => [['product_id' => '', 'qty' => '', 'unit_price_rupiah' => '']],
                'external_purchase_lines' => [['label' => '', 'qty' => '', 'unit_cost_rupiah' => '']],
            ]],
            'inline_payment' => ['decision' => 'skip', 'payment_method' => null, 'paid_at' => '2026-03-15'],
        ]);

        $response->assertRedirect(route('cashier.notes.index'));

        $noteId = (string) DB::table('notes')->value('id');

        $this->assertDatabaseHas('notes', [
            'id' => $noteId,
            'customer_name' => 'Budi',
            'total_rupiah' => 50000,
        ]);

        $this->assertDatabaseCount('customer_payments', 0);
        $this->assertDatabaseMissing('payment_component_allocations', [
            'note_id' => $noteId,
        ]);
        $this->assertDatabaseMissing('payment_allocations', [
            'note_id' => $noteId,
        ]);
    }
}
