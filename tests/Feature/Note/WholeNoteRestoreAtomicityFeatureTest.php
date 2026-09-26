<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Adapters\Out\Audit\DatabaseAuditOutboxWriterAdapter;
use App\Application\Note\UseCases\RestoreCancelledNoteHandler;
use App\Ports\Out\AuditEventWriterPort;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\Support\BuildsPrimitiveLifecycleFixture;
use Tests\TestCase;

final class WholeNoteRestoreAtomicityFeatureTest extends TestCase
{
    use BuildsPrimitiveLifecycleFixture;
    use DatabaseTruncation;

    protected function tearDown(): void
    {
        RefreshDatabaseState::$migrated = false;
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_actual_restore_audit_outbox_failure_rolls_back_revision_and_stock_then_same_key_retries(): void
    {
        $this->preparePrimitiveFixture();
        $cashier = $this->loginAsKasir();
        $this->post(route('notes.workspace.store'), $this->primitiveWorkspace([$this->primitiveItems()[0]], 'restore-audit-create'))
            ->assertRedirect()->assertSessionHasNoErrors();
        $noteId = (string) DB::table('notes')->value('id');
        $baseRevisionId = (string) DB::table('notes')->value('current_revision_id');
        $this->postJson('/cashier/notes/'.$noteId.'/cancel', [
            'base_revision_id' => $baseRevisionId,
            'idempotency_key' => 'restore-audit-cancel',
            'reason' => 'Batalkan sebelum transaksi berjalan',
        ])->assertOk();
        $cancellationId = (string) DB::table('note_mutation_events')
            ->where('note_id', $noteId)->where('mutation_type', 'note_cancelled')->value('id');
        self::assertInstanceOf(DatabaseAuditOutboxWriterAdapter::class, app(AuditEventWriterPort::class));
        self::assertSame(0, DB::transactionLevel());
        $before = $this->captureRestoreRows();
        $failure = new RuntimeException('Injected failure after restore audit outbox insert.');
        $during = null;
        $writerCalls = 0;
        $connection = DB::connection();
        $dispatcher = $connection->getEventDispatcher();
        self::assertNotNull($dispatcher);
        $isolatedDispatcher = clone $dispatcher;
        $connection->setEventDispatcher($isolatedDispatcher);
        $isolatedDispatcher->listen(QueryExecuted::class, function (QueryExecuted $query) use (&$during, &$writerCalls, $failure): void {
            if (! str_starts_with(strtolower($query->sql), 'insert into `audit_outbox`')
                || ! in_array('note_restored', $query->bindings, true)) {
                return;
            }
            $writerCalls++;
            self::assertSame(1, $query->connection->transactionLevel());
            $during = $this->captureRestoreRows();
            throw $failure;
        });
        $payload = [
            'base_revision_id' => $baseRevisionId,
            'cancellation_event_id' => $cancellationId,
            'source_revision_id' => $baseRevisionId,
            'idempotency_key' => 'restore-audit-fail',
            'reason' => 'Pelanggan melanjutkan transaksi',
        ];
        try {
            app(RestoreCancelledNoteHandler::class)->handle($noteId, (string) $cashier->getAuthIdentifier(), $payload);
            self::fail('Failure from the actual restore audit writer must escape the handler.');
        } catch (RuntimeException $caught) {
            self::assertSame($failure, $caught);
        } finally {
            $connection->setEventDispatcher($dispatcher);
        }

        self::assertSame(1, $writerCalls);
        self::assertNotNull($during);
        self::assertSame(0, DB::transactionLevel());
        self::assertSame($before, $this->captureRestoreRows());
        $this->assertDatabaseHas('notes', ['id' => $noteId, 'note_state' => 'cancelled', 'current_revision_id' => $baseRevisionId]);

        $retry = app(RestoreCancelledNoteHandler::class)->handle($noteId, (string) $cashier->getAuthIdentifier(), $payload);
        self::assertTrue($retry->isSuccess(), $retry->message() ?? 'A rolled-back restore key must be retryable.');
        $this->assertDatabaseHas('notes', ['id' => $noteId, 'note_state' => 'open', 'current_revision_id' => $noteId.'-r002']);
        $this->assertDatabaseHas('idempotency_records', ['operation' => 'restore_cancelled_note', 'idempotency_key' => 'restore-audit-fail', 'status' => 'succeeded']);
    }

    private function captureRestoreRows(): array
    {
        $keys = [
            'notes' => 'id', 'work_items' => 'id', 'work_item_store_stock_lines' => 'id',
            'product_inventory' => 'product_id', 'product_inventory_costing' => 'product_id',
            'inventory_movements' => 'id', 'note_revisions' => 'id', 'note_revision_lines' => 'id',
            'note_revision_settlements' => 'id', 'note_history_projection' => 'note_id',
            'note_mutation_events' => 'id', 'note_mutation_snapshots' => 'id',
            'audit_outbox' => 'id', 'idempotency_records' => 'id',
        ];
        $rows = [];
        foreach ($keys as $table => $key) {
            $rows[$table] = DB::table($table)->orderBy($key)->get()->toJson();
        }

        return $rows;
    }
}
