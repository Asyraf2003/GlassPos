<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Adapters\Out\Audit\DatabaseAuditLogAdapter;
use App\Adapters\Out\Audit\DatabaseAuditOutboxWriterAdapter;
use App\Application\Payment\UseCases\RecordAndAllocateNotePaymentHandler;
use App\Ports\Out\AuditEventWriterPort;
use App\Ports\Out\AuditLogPort;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Support\BuildsPrimitiveLifecycleFixture;
use Tests\TestCase;

final class PrimitiveLifecycleAuditAtomicityFeatureTest extends TestCase
{
    use BuildsPrimitiveLifecycleFixture;
    use RefreshDatabase;

    protected function tearDown(): void
    {
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
        $this->advancePrimitiveTime();
        $payload = array_replace($this->primitivePayment('audit-payment-full', 395933, 'cash', 400003), [
            '_actor_id' => (string) $cashier->getAuthIdentifier(), 'note_id' => $noteId,
        ]);

        // Real application entry point, real transaction runner, real writers; no audit mock/failure injection.
        $result = app(RecordAndAllocateNotePaymentHandler::class)->handle(
            $noteId, 395933, '2026-09-15', $payload['selected_row_ids'], 'cash', 400003, $payload,
        );
        self::assertTrue($result->isSuccess(), $result->message() ?? 'Payment must succeed before assessing audit capture.');
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
        self::assertGreaterThan(0, $deltas['audit_outbox']['delta'],
            'A01: payment succeeded but requires durable outbox capture. Actual runtime measurements: '.json_encode($deltas, JSON_THROW_ON_ERROR));
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
