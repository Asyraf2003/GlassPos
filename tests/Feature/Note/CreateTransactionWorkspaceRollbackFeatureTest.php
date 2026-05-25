<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use App\Ports\Out\AuditLogPort;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

final class CreateTransactionWorkspaceRollbackFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_workspace_rolls_back_note_item_payment_allocation_projection_and_audit_when_inline_payment_audit_fails(): void
    {
        $this->loginAsKasir();

        $this->app->instance(AuditLogPort::class, new class () implements AuditLogPort {
            public function record(string $event, array $context = []): void
            {
                if ($event === 'payment_allocated') {
                    throw new RuntimeException('force rollback after inline payment writes');
                }
            }
        });

        $user = User::query()->create([
            'name' => 'Kasir Create Rollback',
            'email' => 'create-rollback@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        $this->withoutExceptionHandling();

        try {
            $this->actingAs($user)->post(route('notes.workspace.store'), [
                'note' => [
                    'customer_name' => 'Rollback Create Customer',
                    'customer_phone' => '081234567899',
                    'transaction_date' => '2026-05-24',
                ],
                'items' => [[
                    'entry_mode' => 'service',
                    'part_source' => 'none',
                    'pricing_mode' => 'manual_split',
                    'package_total_rupiah' => null,
                    'service' => [
                        'name' => 'Servis Rollback Baseline',
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

            self::fail('Expected forced rollback exception was not thrown.');
        } catch (RuntimeException $e) {
            self::assertSame('force rollback after inline payment writes', $e->getMessage());
        }

        $this->assertDatabaseMissing('notes', [
            'customer_name' => 'Rollback Create Customer',
        ]);

        $this->assertDatabaseCount('notes', 0);
        $this->assertDatabaseCount('work_items', 0);
        $this->assertDatabaseCount('work_item_service_details', 0);
        $this->assertDatabaseCount('customer_payments', 0);
        $this->assertDatabaseCount('customer_payment_cash_details', 0);
        $this->assertDatabaseCount('payment_component_allocations', 0);
        $this->assertDatabaseCount('note_mutation_events', 0);
        $this->assertDatabaseCount('note_history_projection', 0);
        $this->assertDatabaseCount('audit_logs', 0);
    }
}
