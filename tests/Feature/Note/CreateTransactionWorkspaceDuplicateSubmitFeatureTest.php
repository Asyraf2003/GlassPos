<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use App\Ports\Out\AuditLogPort;
use App\Ports\Out\UuidPort;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

final class CreateTransactionWorkspaceDuplicateSubmitFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_workspace_create_page_renders_hidden_idempotency_key_for_normal_submit(): void
    {
        $this->loginAsKasir();

        $this->app->instance(UuidPort::class, new class () implements UuidPort {
            public function generate(): string
            {
                return 'workspace-create-idempotency-render-key';
            }
        });

        $response = $this->get(route('cashier.notes.workspace.create'));

        $response->assertOk();
        $response->assertSee('name="idempotency_key"', false);
        $response->assertSee('value="workspace-create-idempotency-render-key"', false);
    }

    public function test_create_workspace_submit_without_idempotency_key_is_rejected_without_creating_financial_rows(): void
    {
        $this->loginAsKasir();

        $user = User::query()->create([
            'name' => 'Kasir Duplicate Submit',
            'email' => 'create-duplicate-submit@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        $payload = [
            'note' => [
                'customer_name' => 'Duplicate Submit Customer',
                'customer_phone' => '081234567898',
                'transaction_date' => '2026-05-24',
            ],
            'items' => [[
                'entry_mode' => 'service',
                'part_source' => 'none',
                'pricing_mode' => 'manual_split',
                'package_total_rupiah' => null,
                'service' => [
                    'name' => 'Servis Duplicate Submit',
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
        ];

        $response = $this->actingAs($user)
            ->from(route('cashier.notes.workspace.create'))
            ->post(route('notes.workspace.store'), $payload);

        $response->assertRedirect(route('cashier.notes.workspace.create'));
        $response->assertSessionHasErrors(['idempotency_key']);

        $this->assertSame(
            0,
            DB::table('notes')->where('customer_name', 'Duplicate Submit Customer')->count(),
            'Create workspace must reject direct submits that bypass the form idempotency key.'
        );

        $this->assertSame(0, DB::table('work_items')->count());
        $this->assertSame(0, DB::table('work_item_service_details')->count());
        $this->assertSame(0, DB::table('customer_payments')->count());
        $this->assertSame(0, DB::table('customer_payment_cash_details')->count());
        $this->assertSame(0, DB::table('payment_component_allocations')->count());
        $this->assertSame(0, DB::table('note_history_projection')->count());
    }

    public function test_duplicate_create_workspace_submit_with_same_idempotency_key_and_same_payload_should_not_create_duplicate_note(): void
    {
        $this->loginAsKasir();

        $user = User::query()->create([
            'name' => 'Kasir Idempotent Submit',
            'email' => 'create-idempotent-submit@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        $payload = [
            'idempotency_key' => 'idem-create-workspace-same-payload-001',
            'note' => [
                'customer_name' => 'Idempotent Submit Customer',
                'customer_phone' => '081234567897',
                'transaction_date' => '2026-05-24',
            ],
            'items' => [[
                'entry_mode' => 'service',
                'part_source' => 'none',
                'pricing_mode' => 'manual_split',
                'package_total_rupiah' => null,
                'service' => [
                    'name' => 'Servis Idempotent Submit',
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
        ];

        $firstResponse = $this->actingAs($user)->post(route('notes.workspace.store'), $payload);
        $secondResponse = $this->actingAs($user)->post(route('notes.workspace.store'), $payload);

        $firstResponse->assertRedirect(route('cashier.notes.index'));
        $secondResponse->assertRedirect(route('cashier.notes.index'));

        $this->assertSame(
            1,
            DB::table('notes')->where('customer_name', 'Idempotent Submit Customer')->count(),
            'Same actor, operation, idempotency key, and payload must replay or no-op without creating a duplicate note.'
        );

        $this->assertSame(1, DB::table('work_items')->count());
        $this->assertSame(1, DB::table('work_item_service_details')->count());
        $this->assertSame(1, DB::table('customer_payments')->count());
        $this->assertSame(1, DB::table('customer_payment_cash_details')->count());
        $this->assertSame(1, DB::table('payment_component_allocations')->count());
        $this->assertSame(1, DB::table('note_history_projection')->count());
    }


    public function test_duplicate_create_workspace_submit_with_same_idempotency_key_and_different_payload_is_rejected_without_creating_second_note(): void
    {
        $this->loginAsKasir();

        $user = User::query()->create([
            'name' => 'Kasir Idempotent Conflict',
            'email' => 'create-idempotent-conflict@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        $payload = [
            'idempotency_key' => 'idem-create-workspace-conflict-001',
            'note' => [
                'customer_name' => 'Idempotent Conflict Customer',
                'customer_phone' => '081234567896',
                'transaction_date' => '2026-05-24',
            ],
            'items' => [[
                'entry_mode' => 'service',
                'part_source' => 'none',
                'pricing_mode' => 'manual_split',
                'package_total_rupiah' => null,
                'service' => [
                    'name' => 'Servis Idempotent Conflict',
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
        ];

        $changedPayload = $payload;
        $changedPayload['note']['customer_name'] = 'Idempotent Conflict Customer Changed';

        $firstResponse = $this->actingAs($user)->post(route('notes.workspace.store'), $payload);
        $secondResponse = $this->actingAs($user)
            ->from(route('notes.workspace.store'))
            ->post(route('notes.workspace.store'), $changedPayload);

        $firstResponse->assertRedirect(route('cashier.notes.index'));
        $secondResponse->assertRedirect();
        $secondResponse->assertSessionHasErrors(['workspace']);

        $this->assertSame(1, DB::table('notes')->count());
        $this->assertSame(
            1,
            DB::table('notes')->where('customer_name', 'Idempotent Conflict Customer')->count()
        );
        $this->assertSame(
            0,
            DB::table('notes')->where('customer_name', 'Idempotent Conflict Customer Changed')->count()
        );

        $this->assertSame(1, DB::table('work_items')->count());
        $this->assertSame(1, DB::table('customer_payments')->count());
        $this->assertSame(1, DB::table('payment_component_allocations')->count());
        $this->assertSame(1, DB::table('note_history_projection')->count());
    }

    public function test_create_workspace_idempotency_failed_attempt_after_inline_payment_writes_can_retry_same_key_same_payload_without_duplicate_rows(): void
    {
        $this->loginAsKasir();

        $this->app->instance(AuditLogPort::class, new class () implements AuditLogPort {
            public function record(string $event, array $context = []): void
            {
                if ($event === 'payment_allocated') {
                    throw new RuntimeException('force idempotent retry rollback after inline payment writes');
                }
            }
        });

        $user = User::query()->create([
            'name' => 'Kasir Idempotent Retry',
            'email' => 'create-idempotent-retry@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        $payload = [
            'idempotency_key' => 'idem-create-workspace-failed-retry-001',
            'note' => [
                'customer_name' => 'Idempotent Retry Customer',
                'customer_phone' => '081234567895',
                'transaction_date' => '2026-05-24',
            ],
            'items' => [[
                'entry_mode' => 'service',
                'part_source' => 'none',
                'pricing_mode' => 'manual_split',
                'package_total_rupiah' => null,
                'service' => [
                    'name' => 'Servis Idempotent Retry',
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
        ];

        $this->withoutExceptionHandling();

        try {
            $this->actingAs($user)->post(route('notes.workspace.store'), $payload);

            self::fail('Expected forced idempotent retry rollback exception was not thrown.');
        } catch (RuntimeException $e) {
            self::assertSame(
                'force idempotent retry rollback after inline payment writes',
                $e->getMessage()
            );
        }

        $this->assertDatabaseCount('notes', 0);
        $this->assertDatabaseCount('work_items', 0);
        $this->assertDatabaseCount('work_item_service_details', 0);
        $this->assertDatabaseCount('customer_payments', 0);
        $this->assertDatabaseCount('customer_payment_cash_details', 0);
        $this->assertDatabaseCount('payment_component_allocations', 0);
        $this->assertDatabaseCount('note_mutation_events', 0);
        $this->assertDatabaseCount('note_history_projection', 0);
        $this->assertDatabaseCount('audit_logs', 0);
        $this->assertDatabaseCount('idempotency_records', 0);

        $this->app->instance(AuditLogPort::class, new class () implements AuditLogPort {
            public function record(string $event, array $context = []): void
            {
                // no-op audit port for retry success path
            }
        });

        $retryResponse = $this->actingAs($user)->post(route('notes.workspace.store'), $payload);

        $retryResponse->assertRedirect(route('cashier.notes.index'));

        $this->assertSame(
            1,
            DB::table('notes')->where('customer_name', 'Idempotent Retry Customer')->count(),
            'Retrying the same idempotency key and same payload after a rolled-back failed attempt must create exactly one successful note.'
        );

        $this->assertSame(1, DB::table('work_items')->count());
        $this->assertSame(1, DB::table('work_item_service_details')->count());
        $this->assertSame(1, DB::table('customer_payments')->count());
        $this->assertSame(1, DB::table('customer_payment_cash_details')->count());
        $this->assertSame(1, DB::table('payment_component_allocations')->count());
        $this->assertSame(1, DB::table('note_history_projection')->count());

        $this->assertSame(
            1,
            DB::table('idempotency_records')
                ->where('actor_id', (string) $user->getAuthIdentifier())
                ->where('operation', 'create_transaction_workspace')
                ->where('idempotency_key', 'idem-create-workspace-failed-retry-001')
                ->where('status', 'succeeded')
                ->count(),
            'A rolled-back failed attempt must not leave a stale idempotency record, and the retry must persist exactly one succeeded record.'
        );
    }


}
