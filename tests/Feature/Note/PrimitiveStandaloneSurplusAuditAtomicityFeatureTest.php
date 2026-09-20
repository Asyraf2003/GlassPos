<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Adapters\Out\Audit\DatabaseAuditEventWriterAdapter;
use App\Application\Note\UseCases\CreateNoteRevisionSurplusRefundDueCommand;
use App\Application\Note\UseCases\CreateNoteRevisionSurplusRefundDueHandler;
use App\Application\Note\UseCases\RecordNoteRevisionSurplusRefundPaymentCommand;
use App\Application\Note\UseCases\RecordNoteRevisionSurplusRefundPaymentHandler;
use DateTimeImmutable;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

final class PrimitiveStandaloneSurplusAuditAtomicityFeatureTest extends TestCase
{
    use DatabaseTruncation;

    protected function tearDown(): void
    {
        RefreshDatabaseState::$migrated = false;
        parent::tearDown();
    }

    public function test_standalone_due_and_paid_handlers_rollback_actual_canonical_writes(): void
    {
        // Persisted starting settlement fixture, same boundary as existing standalone handler tests.
        // All subsequent due/payment lifecycle transitions go through production handlers.
        $this->seedSourceSettlement('atomic-surplus-settlement', 122000);
        $dueHandler = app(CreateNoteRevisionSurplusRefundDueHandler::class);
        $dueCommand = new CreateNoteRevisionSurplusRefundDueCommand('atomic-surplus-settlement', 122000,
            'Standalone due probe', 'admin-test-001', 'admin');
        $due = $this->assertCanonicalFailureAndRetry($dueHandler, fn () => $dueHandler->handle($dueCommand), 'note_revision_surplus_dispositions');
        self::assertTrue($due->isSuccess(), $due->message() ?? 'Standalone due retry must succeed.');
        $disposition = DB::table('note_revision_surplus_dispositions')->sole();
        self::assertSame(122000, (int) $disposition->amount_rupiah);
        $this->assertDatabaseHas('audit_events', ['id' => $disposition->audit_event_id,
            'aggregate_id' => $disposition->id, 'event_name' => 'note_revision_surplus_refund_due_created']);

        $paidHandler = app(RecordNoteRevisionSurplusRefundPaymentHandler::class);
        $paidCommand = new RecordNoteRevisionSurplusRefundPaymentCommand($disposition->id, 50000,
            new DateTimeImmutable('2026-09-20'), 'Standalone paid probe', 'admin-test-001', 'admin', 'atomic-surplus-paid');
        $paid = $this->assertCanonicalFailureAndRetry($paidHandler, fn () => $paidHandler->handle($paidCommand), 'note_revision_surplus_refund_payments');
        self::assertTrue($paid->isSuccess(), $paid->message() ?? 'Standalone payout retry must succeed.');
        self::assertSame(72000, $paid->data()['remaining_refund_due_rupiah']);
        $payment = DB::table('note_revision_surplus_refund_payments')->sole();
        $this->assertDatabaseHas('audit_events', ['id' => $payment->audit_event_id,
            'aggregate_id' => $payment->id, 'event_name' => 'note_revision_surplus_refund_paid_recorded']);
        $after = $this->captureGraph();
        self::assertTrue($paidHandler->handle($paidCommand)->isSuccess());
        self::assertSame($after, $this->captureGraph(), 'Same-key replay cannot repeat payout or capture.');
    }

    private function assertCanonicalFailureAndRetry(object $handler, callable $command, string $businessTable): mixed
    {
        self::assertInstanceOf(DatabaseAuditEventWriterAdapter::class, (new \ReflectionProperty($handler, 'auditWriter'))->getValue($handler));
        $before = $this->captureGraph();
        self::assertSame(0, DB::transactionLevel());
        $connection = DB::connection();
        $dispatcher = $connection->getEventDispatcher();
        self::assertNotNull($dispatcher);
        $listener = clone $dispatcher;
        $connection->setEventDispatcher($listener);
        $failure = new RuntimeException('Injected canonical snapshot write failure');
        $during = null;
        $calls = 0;
        $listener->listen(QueryExecuted::class, function (QueryExecuted $query) use ($failure, &$during, &$calls): void {
            if (! str_starts_with(strtolower($query->sql), 'insert into `audit_event_snapshots`')) {
                return;
            }
            $calls++;
            self::assertSame(1, $query->connection->transactionLevel());
            $during = $this->captureGraph();
            throw $failure;
        });
        $caughtFailure = null;
        try {
            $command();
        } catch (RuntimeException $caught) {
            $caughtFailure = $caught;
        } finally {
            $connection->setEventDispatcher($dispatcher);
        }
        self::assertSame(1, $calls);
        self::assertSame($failure, $caughtFailure);
        self::assertNotNull($during);
        self::assertSame(1, count($during['audit_events']) - count($before['audit_events']));
        self::assertSame(2, count($during['audit_event_snapshots']) - count($before['audit_event_snapshots']));
        self::assertSame($before[$businessTable], $during[$businessTable], 'Canonical capture precedes business insertion.');
        self::assertSame(0, DB::transactionLevel());
        self::assertSame($before, $this->captureGraph(), 'Both canonical rows and all business surfaces must be restored.');
        $result = $command();
        self::assertSame(0, DB::transactionLevel());

        return $result;
    }

    private function seedSourceSettlement(
        string $settlementId,
        int $surplusRupiah,
        string $status = 'overpaid_pending',
    ): void {
        DB::table('notes')->insert([
            'id' => 'note-root-test-001',
            'customer_name' => 'Customer Test',
            'customer_phone' => '08123456789',
            'transaction_date' => '2026-05-13',
            'note_state' => 'closed',
            'closed_at' => '2026-05-13 09:00:00',
            'closed_by_actor_id' => 'admin-test-001',
            'reopened_at' => null,
            'reopened_by_actor_id' => null,
            'total_rupiah' => 143000,
        ]);

        DB::table('note_revisions')->insert([
            'id' => 'note-revision-test-001',
            'note_root_id' => 'note-root-test-001',
            'revision_number' => 2,
            'parent_revision_id' => null,
            'created_by_actor_id' => 'admin-test-001',
            'reason' => 'Test revision surplus.',
            'customer_name' => 'Customer Test',
            'customer_phone' => '08123456789',
            'transaction_date' => '2026-05-13',
            'grand_total_rupiah' => 143000,
            'line_count' => 1,
            'created_at' => '2026-05-13 09:30:00',
            'updated_at' => null,
        ]);

        DB::table('note_revision_settlements')->insert([
            'id' => $settlementId,
            'note_revision_id' => 'note-revision-test-001',
            'note_root_id' => 'note-root-test-001',
            'gross_total_rupiah' => 143000,
            'carry_forward_paid_rupiah' => 265000,
            'carry_forward_refunded_rupiah' => 0,
            'net_paid_rupiah' => 265000,
            'outstanding_rupiah' => 0,
            'surplus_rupiah' => $surplusRupiah,
            'settlement_status' => $status,
            'created_at' => '2026-05-13 09:30:00',
            'updated_at' => null,
        ]);
    }

    private function captureGraph(): array
    {
        $tables = ['notes', 'work_items', 'work_item_service_details', 'work_item_store_stock_lines',
            'work_item_external_purchase_lines', 'note_revisions', 'note_revision_lines', 'note_revision_settlements',
            'note_revision_surplus_dispositions', 'note_revision_surplus_refund_payments', 'customer_payments',
            'payment_allocations', 'payment_component_allocations', 'customer_payment_cash_details',
            'customer_refunds', 'refund_component_allocations', 'product_inventory', 'product_inventory_costing',
            'inventory_movements', 'note_history_projection', 'note_mutation_events', 'note_mutation_snapshots',
            'idempotency_records', 'audit_logs', 'audit_outbox', 'audit_events', 'audit_event_snapshots'];
        $snapshot = [];
        foreach ($tables as $table) {
            $rows = DB::table($table)->get()->map(fn ($row) => (array) $row)->all();
            usort($rows, fn (array $a, array $b): int => strcmp(json_encode($a, JSON_THROW_ON_ERROR), json_encode($b, JSON_THROW_ON_ERROR)));
            $snapshot[$table] = $rows;
        }

        return $snapshot;
    }
}
