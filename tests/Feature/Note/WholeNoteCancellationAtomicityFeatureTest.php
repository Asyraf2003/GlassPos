<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Adapters\Out\Audit\DatabaseAuditOutboxWriterAdapter;
use App\Application\Note\UseCases\CancelNoteHandler;
use App\Ports\Out\AuditEventWriterPort;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\Support\BuildsPrimitiveLifecycleFixture;
use Tests\TestCase;

final class WholeNoteCancellationAtomicityFeatureTest extends TestCase
{
    use BuildsPrimitiveLifecycleFixture;
    use DatabaseTruncation;

    protected function tearDown(): void
    {
        RefreshDatabaseState::$migrated = false;
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_actual_audit_outbox_failure_rolls_back_cancellation_and_allows_retry(): void
    {
        $this->preparePrimitiveFixture();
        $cashier = $this->loginAsKasir();
        $this->post(route('notes.workspace.store'), $this->primitiveWorkspace(array_slice($this->primitiveItems(), 0, 1), 'cancel-audit-create'))
            ->assertRedirect()->assertSessionHasNoErrors();
        $noteId = (string) DB::table('notes')->value('id');
        $base = (string) DB::table('notes')->value('current_revision_id');
        self::assertInstanceOf(DatabaseAuditOutboxWriterAdapter::class, app(AuditEventWriterPort::class));
        self::assertSame(0, DB::transactionLevel());
        $before = $this->captureCancellationRows();
        $failure = new RuntimeException('Injected failure after cancellation audit outbox insert.');
        $during = null;
        $writerCalls = 0;
        $connection = DB::connection();
        $dispatcher = $connection->getEventDispatcher();
        self::assertNotNull($dispatcher);
        $isolatedDispatcher = clone $dispatcher;
        $connection->setEventDispatcher($isolatedDispatcher);
        $isolatedDispatcher->listen(QueryExecuted::class, function (QueryExecuted $query) use (&$during, &$writerCalls, $failure): void {
            if (! str_starts_with(strtolower($query->sql), 'insert into `audit_outbox`')
                || ! in_array('note_cancelled', $query->bindings, true)) {
                return;
            }
            $writerCalls++;
            self::assertSame(1, $query->connection->transactionLevel());
            $during = $this->captureCancellationRows();
            throw $failure;
        });
        $payload = ['base_revision_id' => $base, 'idempotency_key' => 'cancel-audit-fail', 'reason' => 'Audit gagal'];
        try {
            app(CancelNoteHandler::class)->handle($noteId, (string) $cashier->getAuthIdentifier(), $payload);
            self::fail('Failure from the actual cancellation audit writer must escape the handler.');
        } catch (RuntimeException $caught) {
            self::assertSame($failure, $caught);
        } finally {
            $connection->setEventDispatcher($dispatcher);
        }
        self::assertSame(1, $writerCalls);
        self::assertNotNull($during);
        self::assertSame(0, DB::transactionLevel());
        self::assertSame($before, $this->captureCancellationRows());
        $this->assertDatabaseHas('notes', ['id' => $noteId, 'note_state' => 'open']);
        $this->assertDatabaseMissing('idempotency_records', ['operation' => 'cancel_note', 'idempotency_key' => 'cancel-audit-fail']);

        $retry = app(CancelNoteHandler::class)->handle($noteId, (string) $cashier->getAuthIdentifier(), $payload);
        self::assertTrue($retry->isSuccess(), $retry->message() ?? 'A rolled-back cancellation key must be retryable.');
        $this->assertDatabaseHas('notes', ['id' => $noteId, 'note_state' => 'cancelled', 'total_rupiah' => 0]);
        $this->assertDatabaseHas('idempotency_records', ['operation' => 'cancel_note', 'idempotency_key' => 'cancel-audit-fail', 'status' => 'succeeded']);
    }

    private function captureCancellationRows(): array
    {
        $keys = [
            'notes' => 'id', 'work_items' => 'id', 'product_inventory' => 'product_id',
            'product_inventory_costing' => 'product_id', 'inventory_movements' => 'id',
            'note_history_projection' => 'note_id', 'note_mutation_events' => 'id',
            'note_mutation_snapshots' => 'id', 'audit_outbox' => 'id', 'idempotency_records' => 'id',
        ];

        $rows = [];
        foreach ($keys as $table => $key) {
            $rows[$table] = DB::table($table)->orderBy($key)->get()->toJson();
        }

        return $rows;
    }
}
