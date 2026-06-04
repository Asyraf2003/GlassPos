<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class CreateTransactionWorkspaceFullCashFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_can_store_workspace_with_full_cash_payment_and_redirect_to_history(): void
    {
        $this->loginAsKasir();
        $user = User::query()->create(['name' => 'Kasir Aktif', 'email' => 'fullcash@example.test', 'password' => 'password']);
        DB::table('actor_accesses')->insert(['actor_id' => (string) $user->getAuthIdentifier(), 'role' => 'kasir']);

        $response = $this->actingAs($user)->post(route('notes.workspace.store'), [
            'idempotency_key' => 'create-workspace-full-cash-idem-001',
            'note' => ['customer_name' => 'Budi', 'customer_phone' => '08123', 'transaction_date' => '2026-03-15'],
            'items' => [[
                'entry_mode' => 'service',
                'part_source' => 'none',
                'pay_now' => 0,
                'service' => ['name' => 'Servis A', 'price_rupiah' => 150000, 'notes' => ''],
                'product_lines' => [['product_id' => '', 'qty' => '', 'unit_price_rupiah' => '']],
                'external_purchase_lines' => [['label' => '', 'qty' => '', 'unit_cost_rupiah' => '']],
            ]],
            'inline_payment' => [
                'decision' => 'pay_full',
                'payment_method' => 'cash',
                'paid_at' => '2026-03-15',
                'amount_received_rupiah' => 200000,
            ],
        ]);

        $response->assertRedirect(route('cashier.notes.index'));

        $this->assertDatabaseHas('customer_payments', [
            'amount_rupiah' => 150000,
            'payment_method' => 'cash',
            'paid_at' => '2026-03-15',
        ]);

        $paymentId = (string) DB::table('customer_payments')->value('id');
        $noteId = (string) DB::table('notes')->value('id');

        $this->assertDatabaseHas('customer_payment_cash_details', [
            'customer_payment_id' => $paymentId,
            'amount_paid_rupiah' => 150000,
            'amount_received_rupiah' => 200000,
            'change_rupiah' => 50000,
        ]);

        $this->assertDatabaseHas('payment_component_allocations', [
            'customer_payment_id' => $paymentId,
            'note_id' => $noteId,
            'allocated_amount_rupiah' => 150000,
        ]);

        $this->assertDatabaseMissing('payment_allocations', [
            'customer_payment_id' => $paymentId,
            'note_id' => $noteId,
        ]);
    }
}
