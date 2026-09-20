<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Adapters\Out\Audit\DatabaseAuditOutboxWriterAdapter;
use App\Application\Note\UseCases\CreateNoteRevisionHandler;
use App\Ports\Out\AuditEventWriterPort;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Support\BuildsPrimitiveLifecycleFixture;
use Tests\TestCase;
use RuntimeException;
use PHPUnit\Framework\Attributes\DataProvider;

final class PrimitiveRevisionAuditAtomicityFeatureTest extends TestCase
{
    use BuildsPrimitiveLifecycleFixture;
    use DatabaseTruncation;

    protected function tearDown(): void
    {
        RefreshDatabaseState::$migrated = false;
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_revision_without_surplus_requires_its_own_durable_capture(): void
    {
        $this->preparePrimitiveFixture();
        $admin = $this->loginAsAuthorizedAdmin();
        $this->post(route('notes.workspace.store'), $this->primitiveWorkspace($this->primitiveItems(), 'audit-revision-create'))
            ->assertRedirect()->assertSessionHasNoErrors();
        $noteId = (string) DB::table('notes')->value('id');
        $base = (string) DB::table('notes')->value('current_revision_id');
        $before = DB::table('audit_outbox')->pluck('id')->all();
        self::assertInstanceOf(DatabaseAuditOutboxWriterAdapter::class, app(AuditEventWriterPort::class));
        self::assertSame(0, DB::transactionLevel());
        $payload = array_replace($this->primitiveWorkspace($this->primitiveItems(70211), 'audit-revision-up'), ['base_revision_id' => $base]);
        $result = app(CreateNoteRevisionHandler::class)->handle($noteId, $payload, (string) $admin->getAuthIdentifier());
        self::assertTrue($result->isSuccess(), $result->message());
        self::assertSame(0, DB::transactionLevel());
        $revisionId = $result->data()['revision_id'];
        $this->assertDatabaseHas('notes', ['id' => $noteId, 'total_rupiah' => 402425, 'current_revision_id' => $revisionId]);
        $this->assertDatabaseHas('audit_logs', ['event' => 'note_revision_created']);
        $this->assertDatabaseCount('note_revision_surplus_dispositions', 0);
        $events = DB::table('audit_outbox')->whereNotIn('id', $before)->where('event_name', 'note_revision_created')->get();
        self::assertCount(1, $events, 'ADR-0042 requires revision durable capture even when no surplus is generated.');
        $event = $events->sole();
        self::assertSame($revisionId, $event->aggregate_id);
        self::assertSame('note_revision', $event->aggregate_type);
        self::assertSame((string) $admin->getAuthIdentifier(), $event->actor_id);
        $metadata = json_decode($event->metadata_json, true, flags: JSON_THROW_ON_ERROR);
        self::assertSame($noteId, $metadata['note_root_id']);
        self::assertSame($base, $metadata['parent_revision_id']);
        self::assertSame($revisionId, $metadata['revision_id']);
    }

    public static function revisionAuditSeams(): array
    {
        return [
            'revision capture after replacement' => ['note_revision_created', false],
            'automatic surplus payout after due capture' => ['note_revision_surplus_refund_paid_recorded', true],
        ];
    }

    #[DataProvider('revisionAuditSeams')]
    public function test_actual_revision_writer_failure_restores_the_whole_graph(string $eventName, bool $surplus): void
    {
        $this->preparePrimitiveFixture();
        $admin = $this->loginAsAuthorizedAdmin();
        $create = $this->primitiveWorkspace($this->primitiveItems(), 'audit-seam-create');
        if ($surplus) {
            $create['inline_payment'] = ['decision' => 'pay_full', 'payment_method' => 'cash',
                'paid_at' => '2026-09-15', 'amount_paid_rupiah' => 395933, 'amount_received_rupiah' => 400003];
        }
        $this->post(route('notes.workspace.store'), $create)->assertRedirect()->assertSessionHasNoErrors();
        $noteId = (string) DB::table('notes')->value('id');
        $base = (string) DB::table('notes')->value('current_revision_id');
        $payload = array_replace($this->primitiveWorkspace($this->primitiveItems($surplus ? 51983 : 70211), 'audit-seam-revision'), ['base_revision_id' => $base]);
        $before = $this->captureGraph();
        $during = null;
        $calls = 0;
        $level = null;
        $connection = DB::connection();
        self::assertSame(0, $connection->transactionLevel());
        self::assertInstanceOf(DatabaseAuditOutboxWriterAdapter::class, app(AuditEventWriterPort::class));
        $dispatcher = $connection->getEventDispatcher();
        self::assertNotNull($dispatcher);
        $listener = clone $dispatcher;
        $connection->setEventDispatcher($listener);
        $this->withoutExceptionHandling();
        $failure = new RuntimeException('Injected actual revision outbox failure');
        $listener->listen(QueryExecuted::class, function (QueryExecuted $query) use ($eventName, $failure, &$during, &$calls, &$level): void {
            if (! str_starts_with(strtolower($query->sql), 'insert into `audit_outbox`') || ! in_array($eventName, $query->bindings, true)) {
                return;
            }
            $calls++;
            $level = $query->connection->transactionLevel();
            $during = $this->captureGraph();
            throw $failure;
        });
        try {
            $this->patch(route('admin.notes.workspace.update', ['noteId' => $noteId]), $payload)->assertSessionHasNoErrors();
            self::fail('Canonical capture failure must escape.');
        } catch (RuntimeException $caught) {
            self::assertSame($failure, $caught, $caught->getMessage());
        } finally {
            $connection->setEventDispatcher($dispatcher);
        }
        self::assertSame(1, $calls);
        self::assertSame(1, $level);
        self::assertNotNull($during);
        foreach (['notes', 'work_items', 'note_revisions', 'note_revision_lines', 'note_revision_settlements',
            'inventory_movements', 'audit_logs', 'audit_outbox', 'idempotency_records'] as $table) {
            self::assertNotSame($before[$table], $during[$table], $table.' must have changed before failure.');
        }
        if ($surplus) {
            self::assertCount(1, $during['note_revision_surplus_dispositions']);
            self::assertSame(11736, (int) $during['note_revision_surplus_dispositions'][0]['amount_rupiah']);
            self::assertSame([], $during['note_revision_surplus_refund_payments'], 'Capture precedes payout insertion.');
            self::assertNotSame($before['payment_component_allocations'], $during['payment_component_allocations']);
        }
        self::assertSame(0, DB::transactionLevel());
        foreach ($this->captureGraph() as $table => $rows) {
            if ($table === 'audit_logs') {
                // HTTP authorization records the attempt before the business transaction starts.
                $ids = array_column($before[$table], 'id');
                $attempts = array_values(array_filter($rows, fn (array $row): bool => ! in_array($row['id'], $ids, true)));
                self::assertCount(1, $attempts);
                self::assertSame('admin_transaction_capability_used', $attempts[0]['event']);
                self::assertSame((string) $admin->getAuthIdentifier(), json_decode($attempts[0]['context'], true)['actor_id']);
                $rows = array_values(array_filter($rows, fn (array $row): bool => in_array($row['id'], $ids, true)));
            }
            self::assertSame($before[$table], $rows, $table.' must roll back exactly.');
        }
        $this->patch(route('admin.notes.workspace.update', ['noteId' => $noteId]), $payload)->assertRedirect()->assertSessionHasNoErrors();
        self::assertSame(0, DB::transactionLevel());
        $this->assertDatabaseHas('idempotency_records', ['idempotency_key' => 'audit-seam-revision', 'status' => 'succeeded']);
        $revisionId = (string) DB::table('notes')->where('id', $noteId)->value('current_revision_id');
        $this->assertDatabaseHas('audit_outbox', ['aggregate_id' => $revisionId, 'event_name' => 'note_revision_created']);
        if ($surplus) {
            $due = DB::table('note_revision_surplus_dispositions')->sole();
            $paid = DB::table('note_revision_surplus_refund_payments')->sole();
            self::assertSame($due->id, $paid->note_revision_surplus_disposition_id);
            self::assertSame(11736, (int) $paid->amount_rupiah);
            $this->assertDatabaseHas('audit_outbox', ['aggregate_id' => $due->id, 'audit_event_id' => $due->audit_event_id,
                'event_name' => 'note_revision_surplus_refund_due_created']);
            $this->assertDatabaseHas('audit_outbox', ['aggregate_id' => $paid->id,
                'event_name' => 'note_revision_surplus_refund_paid_recorded']);
        }
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
