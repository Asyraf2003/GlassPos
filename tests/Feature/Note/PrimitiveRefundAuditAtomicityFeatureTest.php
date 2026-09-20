<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Adapters\Out\Audit\DatabaseAuditOutboxWriterAdapter;
use App\Ports\Out\AuditEventWriterPort;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\Support\BuildsPrimitiveLifecycleFixture;
use Tests\TestCase;

final class PrimitiveRefundAuditAtomicityFeatureTest extends TestCase
{
    use BuildsPrimitiveLifecycleFixture;
    use DatabaseTruncation;

    protected function tearDown(): void
    {
        RefreshDatabaseState::$migrated = false;
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_split_refund_requires_durable_capture_and_rolls_back_on_actual_writer_failure(): void
    {
        $this->preparePrimitiveFixture();
        $cashier = $this->loginAsKasir();
        $create = $this->primitiveWorkspace($this->primitiveItems(), 'audit-refund-create');
        $create['inline_payment'] = ['decision' => 'pay_partial', 'payment_method' => 'cash',
            'paid_at' => '2026-09-15', 'amount_paid_rupiah' => 73129, 'amount_received_rupiah' => 100003];
        $this->post(route('notes.workspace.store'), $create)->assertRedirect()->assertSessionHasNoErrors();
        $noteId = (string) DB::table('notes')->value('id');
        $this->post(route('cashier.notes.payments.store', ['noteId' => $noteId]),
            $this->primitivePayment('audit-refund-settle', 322804, 'transfer'))->assertSessionHasNoErrors();
        $productRow = (string) DB::table('work_items')->where('transaction_type', 'store_stock_sale_only')->value('id');
        $route = route('cashier.notes.refunds.store', ['noteId' => $noteId]);
        $payload = ['selected_row_ids' => [$productRow], 'refunded_at' => '2026-09-15',
            'reason' => 'Atomic split refund', 'idempotency_key' => 'audit-refund-product'];
        $before = $this->captureGraph();
        $connection = DB::connection();
        self::assertSame(0, $connection->transactionLevel());
        self::assertInstanceOf(DatabaseAuditOutboxWriterAdapter::class, app(AuditEventWriterPort::class));
        $dispatcher = $connection->getEventDispatcher();
        self::assertNotNull($dispatcher);
        $listener = clone $dispatcher;
        $connection->setEventDispatcher($listener);
        $failure = new RuntimeException('Injected actual split refund outbox failure');
        $during = null;
        $calls = 0;
        $level = null;
        $listener->listen(QueryExecuted::class, function (QueryExecuted $query) use ($failure, &$during, &$calls, &$level): void {
            if (! str_starts_with(strtolower($query->sql), 'insert into `audit_outbox`')
                || ! in_array('selected_rows_refund_plan_recorded', $query->bindings, true)) {
                return;
            }
            $calls++;
            $level = $query->connection->transactionLevel();
            $during = $this->captureGraph();
            throw $failure;
        });
        $this->withoutExceptionHandling();
        $caughtFailure = null;
        try {
            $this->post($route, $payload)->assertSessionHasNoErrors();
        } catch (RuntimeException $caught) {
            $caughtFailure = $caught;
        } finally {
            $connection->setEventDispatcher($dispatcher);
        }
        self::assertSame(1, $calls, 'ADR-0042: successful split refund must call canonical durable capture.');
        self::assertSame($failure, $caughtFailure);
        self::assertSame(1, $level);
        self::assertNotNull($during);
        self::assertCount(2, $during['customer_refunds']);
        self::assertCount(2, $during['refund_component_allocations']);
        self::assertSame(142539, array_sum(array_column($during['customer_refunds'], 'amount_rupiah')));
        foreach (['notes', 'work_items', 'inventory_movements', 'product_inventory', 'product_inventory_costing',
            'note_history_projection', 'idempotency_records', 'audit_logs', 'audit_outbox'] as $table) {
            self::assertNotSame($before[$table], $during[$table], $table.' must change before the seam.');
        }
        self::assertSame(0, DB::transactionLevel());
        foreach ($this->captureGraph() as $table => $rows) {
            self::assertSame($before[$table], $rows, $table.' must roll back exactly.');
        }
        $this->post($route, $payload)->assertRedirect()->assertSessionHasNoErrors();
        self::assertSame(0, DB::transactionLevel());
        $event = DB::table('audit_outbox')->where('event_name', 'selected_rows_refund_plan_recorded')->sole();
        self::assertSame($noteId, $event->aggregate_id);
        self::assertSame((string) $cashier->getAuthIdentifier(), $event->actor_id);
        self::assertSame('pending', $event->status);
        $metadata = json_decode($event->metadata_json, true, flags: JSON_THROW_ON_ERROR);
        self::assertEqualsCanonicalizing(DB::table('customer_refunds')->pluck('id')->all(), $metadata['refund_ids']);
        self::assertSame([$productRow], $metadata['selected_row_ids']);
        self::assertSame(142539, $metadata['total_refund_rupiah']);
        $this->assertDatabaseHas('audit_logs', ['event' => 'selected_rows_refund_plan_recorded']);
        $after = $this->captureGraph();
        $this->post($route, $payload)->assertSessionHasNoErrors();
        self::assertSame($after, $this->captureGraph(), 'Exact replay must not repeat refund, stock or audit.');
    }

    public function test_direct_customer_refund_has_its_own_atomic_capture(): void
    {
        $this->preparePrimitiveFixture();
        $cashier = $this->loginAsKasir();
        $create = $this->primitiveWorkspace($this->primitiveItems(), 'audit-direct-create');
        $create['inline_payment'] = ['decision' => 'pay_full', 'payment_method' => 'cash',
            'paid_at' => '2026-09-15', 'amount_paid_rupiah' => 395933, 'amount_received_rupiah' => 400003];
        $this->post(route('notes.workspace.store'), $create)->assertRedirect()->assertSessionHasNoErrors();
        $noteId = (string) DB::table('notes')->value('id');
        $paymentId = (string) DB::table('customer_payments')->value('id');
        $rowId = (string) DB::table('work_items')->where('transaction_type', 'store_stock_sale_only')->value('id');
        $handler = app(\App\Application\Payment\UseCases\RecordCustomerRefundHandler::class);
        $command = fn () => $handler->handle($paymentId, $noteId, 142539, '2026-09-15', 'Direct refund audit probe', (string) $cashier->getAuthIdentifier(), [$rowId]);
        $before = $this->captureGraph();
        $connection = DB::connection();
        self::assertSame(0, $connection->transactionLevel());
        $dispatcher = $connection->getEventDispatcher();
        self::assertNotNull($dispatcher);
        $listener = clone $dispatcher;
        $connection->setEventDispatcher($listener);
        $failure = new RuntimeException('Injected direct refund capture failure');
        $calls = 0;
        $during = null;
        $listener->listen(QueryExecuted::class, function (QueryExecuted $query) use ($failure, &$calls, &$during): void {
            if (! str_starts_with(strtolower($query->sql), 'insert into `audit_outbox`') || ! in_array('customer_refund_recorded', $query->bindings, true)) {
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
        self::assertSame(1, $calls, 'Direct refund must also call durable capture.');
        self::assertSame($failure, $caughtFailure);
        self::assertCount(1, $during['customer_refunds']);
        self::assertCount(1, $during['refund_component_allocations']);
        self::assertSame(0, DB::transactionLevel());
        self::assertSame($before, $this->captureGraph());
        $retry = $command();
        self::assertTrue($retry->isSuccess(), $retry->message());
        $refundId = $retry->data()['refund']['id'];
        $event = DB::table('audit_outbox')->where('event_name', 'customer_refund_recorded')->sole();
        self::assertSame($refundId, $event->aggregate_id);
        $metadata = json_decode($event->metadata_json, true, flags: JSON_THROW_ON_ERROR);
        self::assertSame($refundId, $metadata['refund_id']);
        self::assertSame($paymentId, $metadata['customer_payment_id']);
        self::assertSame($noteId, $metadata['note_id']);
        self::assertSame(142539, $metadata['amount_rupiah']);
        self::assertSame(0, DB::transactionLevel());
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
