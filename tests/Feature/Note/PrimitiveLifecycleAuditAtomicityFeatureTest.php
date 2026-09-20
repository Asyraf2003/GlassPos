<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Adapters\Out\Audit\DatabaseAuditLogAdapter;
use App\Adapters\Out\Audit\DatabaseAuditOutboxWriterAdapter;
use App\Application\Payment\UseCases\RecordAndAllocateNotePaymentHandler;
use App\Ports\Out\AuditEventWriterPort;
use App\Ports\Out\AuditLogPort;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\Support\BuildsPrimitiveLifecycleFixture;
use Tests\TestCase;

final class PrimitiveLifecycleAuditAtomicityFeatureTest extends TestCase
{
    use BuildsPrimitiveLifecycleFixture;

    // Real commits, no outer test transaction or historical migrate:rollback cleanup.
    // The isolated disposable database is dropped externally after verification.
    use DatabaseTruncation;

    protected function tearDown(): void
    {
        // Committed rows must not leak into subsequent RefreshDatabase consumers.
        RefreshDatabaseState::$migrated = false;
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_successful_record_and_allocate_payment_requires_durable_audit_capture(): void
    {
        $this->preparePrimitiveFixture();
        $cashier = $this->loginAsKasir();
        $this->post(route('notes.workspace.store'), $this->primitiveWorkspace($this->primitiveItems(), 'audit-payment-create'))
            ->assertRedirect()->assertSessionHasNoErrors();
        $noteId = (string) DB::table('notes')->value('id');
        $revisionId = (string) DB::table('notes')->value('current_revision_id');
        $this->assertDatabaseHas('notes', ['id' => $noteId, 'note_state' => 'open', 'total_rupiah' => 395933]);
        $this->assertDatabaseHas('note_history_projection', ['note_id' => $noteId, 'net_paid_rupiah' => 0, 'outstanding_rupiah' => 395933]);

        // Production bindings remain intact: resolving the global writer alone is not proof it is called.
        self::assertInstanceOf(DatabaseAuditLogAdapter::class, app(AuditLogPort::class));
        self::assertInstanceOf(DatabaseAuditOutboxWriterAdapter::class, app(AuditEventWriterPort::class));
        $before = $this->captureRuntimeRows();
        $legacyIds = DB::table('audit_logs')->pluck('id')->all();
        Carbon::setTestNow('2026-09-16 10:11:12');
        $payload = array_replace($this->primitivePayment('audit-payment-full', 395933, 'cash', 400003), [
            '_actor_id' => (string) $cashier->getAuthIdentifier(), 'note_id' => $noteId,
        ]);

        // No surrounding test transaction: the real runner must commit the accepted payment.
        self::assertSame(0, DB::transactionLevel());
        $result = app(RecordAndAllocateNotePaymentHandler::class)->handle(
            $noteId, 395933, '2026-09-15', $payload['selected_row_ids'], 'cash', 400003, $payload,
        );
        self::assertTrue($result->isSuccess(), $result->message() ?? 'Payment must succeed before assessing audit capture.');
        self::assertSame(0, DB::transactionLevel());
        $paymentId = $result->data()['payment_id'];
        $after = $this->captureRuntimeRows();
        $deltas = [];
        foreach ($before as $table => $rows) {
            $deltas[$table] = ['before' => count($rows), 'after' => count($after[$table]),
                'delta' => count($after[$table]) - count($rows), 'changed' => $rows !== $after[$table]];
        }

        $this->assertDatabaseCount('customer_payments', 1);
        $this->assertDatabaseHas('customer_payments', ['id' => $paymentId, 'amount_rupiah' => 395933, 'payment_method' => 'cash']);
        self::assertSame(6, $result->data()['allocation_count']);
        self::assertSame(6, $deltas['payment_component_allocations']['delta']);
        self::assertSame(395933, (int) DB::table('payment_component_allocations')->where('customer_payment_id', $paymentId)->sum('allocated_amount_rupiah'));
        self::assertSame([
            'product_only_work_item' => 142539, 'service_external_purchase_part' => 53127,
            'service_fee' => 142993, 'service_store_stock_part' => 57274,
        ], DB::table('payment_component_allocations')->where('customer_payment_id', $paymentId)
            ->selectRaw('component_type, SUM(allocated_amount_rupiah) AS amount')->groupBy('component_type')
            ->orderBy('component_type')->pluck('amount', 'component_type')->map(fn ($amount) => (int) $amount)->all());
        self::assertSame($before['payment_allocations'], $after['payment_allocations'], 'Component path must not fabricate legacy allocations.');
        self::assertSame(1, $deltas['customer_payment_cash_details']['delta']);
        $this->assertDatabaseHas('customer_payment_cash_details', ['customer_payment_id' => $paymentId,
            'amount_paid_rupiah' => 395933, 'amount_received_rupiah' => 400003, 'change_rupiah' => 4070]);
        $this->assertDatabaseHas('notes', ['id' => $noteId, 'note_state' => 'closed', 'total_rupiah' => 395933, 'current_revision_id' => $revisionId]);
        self::assertTrue($deltas['notes']['changed']);
        self::assertTrue($deltas['note_history_projection']['changed']);
        $this->assertDatabaseHas('note_history_projection', ['note_id' => $noteId, 'net_paid_rupiah' => 395933, 'outstanding_rupiah' => 0]);
        self::assertSame(1, $deltas['note_mutation_events']['delta']);
        self::assertSame(1, DB::table('note_mutation_events')->where('mutation_type', 'note_closed')->count());
        self::assertSame(1, $deltas['idempotency_records']['delta']);
        $record = DB::table('idempotency_records')->where('operation', 'record_note_payment')->sole();
        self::assertSame((string) $cashier->getAuthIdentifier(), $record->actor_id);
        self::assertSame('audit-payment-full', $record->idempotency_key);
        self::assertSame('succeeded', $record->status);
        self::assertSame($noteId, $record->result_note_id);
        self::assertSame($paymentId, json_decode($record->result_payload_json, true, 512, JSON_THROW_ON_ERROR)['data']['payment_id']);
        $legacy = DB::table('audit_logs')->whereNotIn('id', $legacyIds)->where('event', 'payment_allocated')->sole();
        $context = json_decode($legacy->context, true, 512, JSON_THROW_ON_ERROR);
        self::assertSame($paymentId, $context['payment_id']);
        self::assertSame($noteId, $context['note_id']);
        self::assertSame(395933, $context['amount']);
        self::assertSame(6, $context['allocation_count']);
        self::assertSame($payload['selected_row_ids'], $context['selected_row_ids']);
        foreach (['inventory_movements', 'note_revisions', 'customer_refunds', 'refund_component_allocations'] as $table) {
            self::assertSame($before[$table], $after[$table], $table.' must not change during this payment.');
        }

        // ADR-0042 / Blueprint0018 A01 acceptance gate. Legacy success is never outbox compliance.
        self::assertSame(1, $deltas['audit_outbox']['delta'],
            'A01: payment succeeded but requires durable outbox capture. Actual runtime measurements: '.json_encode($deltas, JSON_THROW_ON_ERROR));
        self::assertSame(1, $deltas['audit_logs']['delta']);
        $event = DB::table('audit_outbox')->where('bounded_context', 'payment')->sole();
        self::assertSame('customer_payment', $event->aggregate_type);
        self::assertSame($paymentId, $event->aggregate_id);
        self::assertSame('payment_allocated', $event->event_name);
        self::assertSame('pending', $event->status);
        self::assertSame(0, (int) $event->attempts);
        self::assertSame((string) $cashier->getAuthIdentifier(), $event->actor_id);
        self::assertSame('kasir', $event->actor_role);
        foreach (['reason', 'source_channel', 'request_id', 'correlation_id', 'snapshots_json'] as $field) {
            self::assertNull($event->{$field}, $field.' must not be fabricated.');
        }
        self::assertSame('2026-09-16 10:11:12', Carbon::parse($event->occurred_at)->format('Y-m-d H:i:s'));
        $this->assertDatabaseHas('customer_payments', ['id' => $paymentId, 'paid_at' => '2026-09-15']);
        self::assertSame('2026-09-16 10:11:12', Carbon::parse(DB::table('customer_payments')->where('id', $paymentId)->value('recorded_at'))->format('Y-m-d H:i:s'));
        self::assertSame([
            'payment_id' => $paymentId, 'note_id' => $noteId, 'amount_rupiah' => 395933,
            'payment_method' => 'cash', 'amount_received_rupiah' => 400003, 'change_rupiah' => 4070,
            'allocation_count' => 6, 'selected_row_ids' => $payload['selected_row_ids'],
        ], json_decode($event->metadata_json, true, 512, JSON_THROW_ON_ERROR));
        self::assertSame($before['audit_events'], $after['audit_events']);
        self::assertSame($before['audit_event_snapshots'], $after['audit_event_snapshots']);
        foreach ($before['audit_outbox'] as $row) {
            self::assertSame($row, (array) DB::table('audit_outbox')->where('id', $row['id'])->sole());
        }
    }

    public function test_actual_outbox_writer_failure_rolls_back_the_entire_payment(): void
    {
        $this->preparePrimitiveFixture();
        $cashier = $this->loginAsKasir();
        $this->post(route('notes.workspace.store'), $this->primitiveWorkspace($this->primitiveItems(), 'audit-rollback-create'))
            ->assertRedirect()->assertSessionHasNoErrors();
        $noteId = (string) DB::table('notes')->value('id');
        self::assertInstanceOf(DatabaseAuditLogAdapter::class, app(AuditLogPort::class));
        self::assertInstanceOf(DatabaseAuditOutboxWriterAdapter::class, app(AuditEventWriterPort::class));
        $handler = app(RecordAndAllocateNotePaymentHandler::class);
        $payload = array_replace($this->primitivePayment('audit-rollback-full', 395933, 'cash', 400003), [
            '_actor_id' => (string) $cashier->getAuthIdentifier(), 'note_id' => $noteId,
        ]);
        $before = $this->captureRuntimeRows();
        self::assertSame(0, DB::transactionLevel());
        $failure = new RuntimeException('Injected failure after canonical payment outbox insert.');
        $during = null;
        $writerCalls = 0;
        $transactionLevel = null;
        $connection = DB::connection();
        $dispatcher = $connection->getEventDispatcher();
        self::assertNotNull($dispatcher);
        $isolatedDispatcher = clone $dispatcher;
        $connection->setEventDispatcher($isolatedDispatcher);
        // QueryExecuted fires after the real adapter inserts: no writer replacement or mock.
        $isolatedDispatcher->listen(QueryExecuted::class, function (QueryExecuted $query) use (
            &$during, &$writerCalls, &$transactionLevel, $failure,
        ): void {
            if (! str_starts_with(strtolower($query->sql), 'insert into `audit_outbox`')
                || ! in_array('payment_allocated', $query->bindings, true)) {
                return;
            }
            $writerCalls++;
            $transactionLevel = $query->connection->transactionLevel();
            $during = $this->captureRuntimeRows();
            throw $failure;
        });
        try {
            $handler->handle($noteId, 395933, '2026-09-15', $payload['selected_row_ids'], 'cash', 400003, $payload);
            self::fail('The actual canonical writer failure must escape the payment handler.');
        } catch (RuntimeException $caught) {
            self::assertSame($failure, $caught);
        } finally {
            $connection->setEventDispatcher($dispatcher);
        }

        self::assertSame(1, $writerCalls, 'Exactly one actual payment outbox insert must reach the failure seam.');
        self::assertSame(1, $transactionLevel, 'The writer must participate in the payment transaction.');
        self::assertNotNull($during);
        foreach (['customer_payments' => 1, 'payment_component_allocations' => 6,
            'customer_payment_cash_details' => 1, 'idempotency_records' => 1,
            'note_mutation_events' => 1, 'note_mutation_snapshots' => 2,
            'audit_logs' => 1, 'audit_outbox' => 1] as $table => $delta) {
            self::assertSame($delta, count($during[$table]) - count($before[$table]), $table.' must exist before the injected failure.');
        }
        self::assertNotSame($before['notes'], $during['notes']);
        self::assertNotSame($before['note_history_projection'], $during['note_history_projection']);
        $payment = $during['customer_payments'][0];
        self::assertSame(395933, (int) $payment['amount_rupiah']);
        $event = array_values(array_filter($during['audit_outbox'], fn (array $row): bool => $row['bounded_context'] === 'payment'));
        self::assertCount(1, $event);
        self::assertSame($payment['id'], $event[0]['aggregate_id']);
        self::assertSame('payment_allocated', $event[0]['event_name']);
        self::assertSame(0, DB::transactionLevel());
        $after = $this->captureRuntimeRows();
        foreach ($before as $table => $rows) {
            self::assertSame($rows, $after[$table], $table.' must be restored byte-for-byte after writer failure.');
        }

        // The rolled-back idempotency key must remain usable for a real committed retry.
        $retry = $handler->handle($noteId, 395933, '2026-09-15', $payload['selected_row_ids'], 'cash', 400003, $payload);
        self::assertTrue($retry->isSuccess(), $retry->message() ?? 'Retry must succeed.');
        self::assertSame(0, DB::transactionLevel());
        $this->assertDatabaseCount('customer_payments', 1);
        $this->assertDatabaseHas('audit_outbox', ['bounded_context' => 'payment',
            'aggregate_id' => $retry->data()['payment_id'], 'event_name' => 'payment_allocated']);
        $this->assertDatabaseHas('idempotency_records', ['operation' => 'record_note_payment',
            'idempotency_key' => 'audit-rollback-full', 'status' => 'succeeded']);
    }

    /** @return array<string, list<array<string, mixed>>> */
    private function captureRuntimeRows(): array
    {
        $snapshot = [];
        foreach (['customer_payments', 'payment_allocations', 'payment_component_allocations',
            'customer_payment_cash_details', 'notes', 'note_history_projection', 'idempotency_records',
            'note_mutation_events', 'note_mutation_snapshots', 'audit_logs', 'audit_outbox', 'audit_events',
            'audit_event_snapshots', 'inventory_movements', 'note_revisions', 'customer_refunds', 'refund_component_allocations'] as $table) {
            $key = match ($table) {
                'customer_payment_cash_details' => 'customer_payment_id',
                'note_history_projection' => 'note_id',
                default => 'id',
            };
            $snapshot[$table] = DB::table($table)->orderBy($key)->get()->map(fn ($row) => (array) $row)->all();
        }

        return $snapshot;
    }
}
