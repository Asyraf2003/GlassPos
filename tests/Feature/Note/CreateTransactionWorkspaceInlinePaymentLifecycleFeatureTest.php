<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class CreateTransactionWorkspaceInlinePaymentLifecycleFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_workspace_service_only_full_cash_payment_builds_lifecycle_baseline_records(): void
    {
        $this->loginAsKasir();

        $user = User::query()->create([
            'name' => 'Kasir Create Lifecycle',
            'email' => 'create-lifecycle@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        $response = $this->actingAs($user)->post(route('notes.workspace.store'), [
            'note' => [
                'customer_name' => 'Lifecycle Create Customer',
                'customer_phone' => '081234567890',
                'transaction_date' => '2026-05-24',
            ],
            'items' => [[
                'entry_mode' => 'service',
                'part_source' => 'none',
                'pricing_mode' => 'manual_split',
                'package_total_rupiah' => null,
                'service' => [
                    'name' => 'Servis Lifecycle Baseline',
                    'price_rupiah' => 85000,
                    'notes' => '',
                ],
                'product_lines' => [[
                    'product_id' => '',
                    'qty' => '',
                    'unit_price_rupiah' => '',
                ]],
                'external_purchase_lines' => [[
                    'label' => '',
                    'qty' => '',
                    'unit_cost_rupiah' => '',
                ]],
            ]],
            'inline_payment' => [
                'decision' => 'pay_full',
                'payment_method' => 'cash',
                'paid_at' => '2026-05-24',
                'amount_received_rupiah' => 100000,
            ],
        ]);

        $response->assertRedirect(route('cashier.notes.index'));

        $note = DB::table('notes')
            ->where('customer_name', 'Lifecycle Create Customer')
            ->first();

        $this->assertNotNull($note);
        $this->assertSame(85000, (int) $note->total_rupiah);
        $this->assertSame('closed', (string) $note->note_state);
        $this->assertSame('system', (string) $note->closed_by_actor_id);

        $workItem = DB::table('work_items')
            ->where('note_id', (string) $note->id)
            ->first();

        $this->assertNotNull($workItem);
        $this->assertSame(85000, (int) $workItem->subtotal_rupiah);

        $this->assertDatabaseHas('work_item_service_details', [
            'work_item_id' => (string) $workItem->id,
            'service_name' => 'Servis Lifecycle Baseline',
            'service_price_rupiah' => 85000,
            'part_source' => 'none',
        ]);

        $payment = DB::table('customer_payments')->first();

        $this->assertNotNull($payment);
        $this->assertSame(85000, (int) $payment->amount_rupiah);
        $this->assertSame('cash', (string) $payment->payment_method);
        $this->assertSame('2026-05-24', (string) $payment->paid_at);

        $this->assertDatabaseHas('customer_payment_cash_details', [
            'customer_payment_id' => (string) $payment->id,
            'amount_paid_rupiah' => 85000,
            'amount_received_rupiah' => 100000,
            'change_rupiah' => 15000,
        ]);

        $this->assertDatabaseHas('payment_component_allocations', [
            'customer_payment_id' => (string) $payment->id,
            'note_id' => (string) $note->id,
            'work_item_id' => (string) $workItem->id,
            'component_type' => 'service_fee',
            'component_ref_id' => (string) $workItem->id,
            'component_amount_rupiah_snapshot' => 85000,
            'allocated_amount_rupiah' => 85000,
            'allocation_priority' => 1,
        ]);

        $this->assertDatabaseHas('note_mutation_events', [
            'note_id' => (string) $note->id,
            'mutation_type' => 'note_closed',
            'actor_id' => 'system',
            'actor_role' => 'system',
            'reason' => 'AUTO_CLOSE_ON_FULL_PAYMENT',
            'related_customer_payment_id' => (string) $payment->id,
        ]);

        $this->assertDatabaseHas('note_history_projection', [
            'note_id' => (string) $note->id,
            'note_state' => 'closed',
            'customer_name' => 'Lifecycle Create Customer',
            'customer_name_normalized' => 'lifecycle create customer',
            'customer_phone' => '081234567890',
            'total_rupiah' => 85000,
            'allocated_rupiah' => 85000,
            'refunded_rupiah' => 0,
            'net_paid_rupiah' => 85000,
            'outstanding_rupiah' => 0,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'payment_allocated',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'transaction_workspace_created',
        ]);
    }
}
