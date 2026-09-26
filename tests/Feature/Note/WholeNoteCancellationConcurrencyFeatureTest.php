<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Application\Note\Services\SelectedNoteRowsRefundPlanResolver;
use App\Application\Note\UseCases\CancelNoteHandler;
use App\Application\Note\UseCases\CreateNoteRevisionHandler;
use App\Application\Payment\Services\RecordSelectedRowsRefundPlanTransaction;
use App\Application\Payment\UseCases\RecordAndAllocateNotePaymentHandler;
use Closure;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;
use Tests\Support\BuildsPrimitiveLifecycleFixture;
use Tests\TestCase;
use Throwable;

final class WholeNoteCancellationConcurrencyFeatureTest extends TestCase
{
    use BuildsPrimitiveLifecycleFixture;
    use DatabaseTruncation;

    protected function tearDown(): void
    {
        RefreshDatabaseState::$migrated = false;
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_cancel_serializes_before_competing_payment(): void
    {
        $this->runRace('payment');
    }

    public function test_cancel_serializes_before_competing_revision(): void
    {
        $this->runRace('revision');
    }

    public function test_concurrent_cancellations_create_one_canonical_effect(): void
    {
        $this->runRace('cancel');
    }

    public function test_paid_cancellation_attempt_serializes_with_refund_lifecycle(): void
    {
        $this->runRace('refund');
    }

    private function runRace(string $competitor): void
    {
        self::assertTrue(function_exists('pcntl_fork'));
        self::assertSame('mysql', DB::connection()->getDriverName());
        $this->preparePrimitiveFixture();
        $cashier = $this->loginAsKasir();
        $workspace = $this->primitiveWorkspace([$this->primitiveItems()[0]], 'cancel-race-create');
        if ($competitor === 'refund') {
            $workspace['inline_payment'] = ['decision' => 'pay_full', 'payment_method' => 'cash', 'paid_at' => '2026-09-15', 'amount_paid_rupiah' => 142539, 'amount_received_rupiah' => 150003];
        }
        $this->post(route('notes.workspace.store'), $workspace)
            ->assertSessionHasNoErrors();
        $noteId = (string) DB::table('notes')->value('id');
        $base = (string) DB::table('notes')->value('current_revision_id');
        $actorId = (string) $cashier->getAuthIdentifier();
        $first = fn () => app(CancelNoteHandler::class)->handle($noteId, $actorId, [
            'base_revision_id' => $base, 'idempotency_key' => 'race-cancel-first', 'reason' => 'Cancel first',
        ]);
        $second = match ($competitor) {
            'cancel' => fn () => app(CancelNoteHandler::class)->handle($noteId, $actorId, [
                'base_revision_id' => $base, 'idempotency_key' => 'race-cancel-second', 'reason' => 'Cancel second',
            ]),
            'payment' => fn () => app(RecordAndAllocateNotePaymentHandler::class)->handle(
                $noteId, 1, '2026-09-15', [(string) DB::table('work_items')->value('id')], 'cash', null,
                ['_actor_id' => $actorId, '_note_id' => $noteId, 'idempotency_key' => 'race-payment'],
            ),
            'revision' => fn () => app(CreateNoteRevisionHandler::class)->handle(
                $noteId, array_replace($this->primitiveWorkspace([$this->primitiveItems()[1]], 'race-revision'), ['base_revision_id' => $base]), $actorId, false,
            ),
            'refund' => function () use ($noteId, $actorId): mixed {
                $rowId = (string) DB::table('work_items')->where('note_id', $noteId)->value('id');
                $planned = app(SelectedNoteRowsRefundPlanResolver::class)->resolve($noteId, [$rowId], [$rowId => true]);
                if ($planned->isFailure()) {
                    return $planned;
                }

                return app(RecordSelectedRowsRefundPlanTransaction::class)->run(
                    $planned->data()['plan'], '2026-09-15', 'Refund menangani transaksi lunas', $actorId, 'kasir',
                    ['_actor_id' => $actorId, '_note_id' => $noteId, 'idempotency_key' => 'race-refund'],
                );
            },
            default => throw new InvalidArgumentException('Unsupported cancellation race competitor.'),
        };

        $dir = sys_get_temp_dir().'/glasspos-cancel-race-'.bin2hex(random_bytes(8));
        self::assertTrue(mkdir($dir, 0700));
        self::assertSame(0, DB::transactionLevel());
        DB::disconnect();
        $children = [];
        $wait = null;
        try {
            $children[] = $this->forkAction('first', $dir, $first);
            $this->awaitFile($dir.'/first-held');
            self::assertSame('1', file_get_contents($dir.'/first-held'));
            $children[] = $this->forkAction('second', $dir, $second);
            $this->awaitFile($dir.'/second-connected');
            DB::purge();
            DB::reconnect();
            $firstId = (int) file_get_contents($dir.'/first-connected');
            $secondId = (int) file_get_contents($dir.'/second-connected');
            self::assertNotSame($firstId, $secondId);
            $observerId = (int) DB::selectOne('SELECT CONNECTION_ID() AS id')->id;
            self::assertNotContains($observerId, [$firstId, $secondId]);
            $deadline = microtime(true) + 12;
            do {
                $wait = DB::selectOne('SELECT r.trx_mysql_thread_id AS waiting_id, b.trx_mysql_thread_id AS blocking_id
                    FROM information_schema.INNODB_LOCK_WAITS w
                    JOIN information_schema.INNODB_TRX r ON r.trx_id = w.requesting_trx_id
                    JOIN information_schema.INNODB_TRX b ON b.trx_id = w.blocking_trx_id
                    WHERE r.trx_mysql_thread_id = ? AND b.trx_mysql_thread_id = ?', [$secondId, $firstId]);
                if ($wait === null) {
                    $query = DB::selectOne('SELECT ID, TIME, INFO FROM information_schema.PROCESSLIST WHERE ID = ?', [$secondId]);
                    $sql = strtolower((string) ($query->INFO ?? ''));
                    if ($query !== null && (int) $query->TIME >= 1 && str_contains($sql, 'from `notes`') && str_contains($sql, 'for update')) {
                        $wait = (object) ['waiting_id' => $secondId, 'blocking_id' => $firstId, 'proof' => 'processlist-for-update'];
                    }
                }
                if ($wait !== null || is_file($dir.'/first-result.json') || is_file($dir.'/second-result.json')) {
                    break;
                }
                usleep(10000);
            } while (microtime(true) < $deadline);
            self::assertNotNull($wait, 'Independent competitor connection must be observed waiting on the cancellation root lock.');
            self::assertFileDoesNotExist($dir.'/first-result.json');
            self::assertFileDoesNotExist($dir.'/second-result.json');
        } finally {
            touch($dir.'/release-first');
            foreach ($children as $pid) {
                pcntl_waitpid($pid, $status);
                self::assertTrue(pcntl_wifexited($status) && pcntl_wexitstatus($status) === 0, 'Race worker must exit normally.');
            }
            DB::purge();
            DB::reconnect();
        }

        $firstResult = json_decode((string) file_get_contents($dir.'/first-result.json'), true, flags: JSON_THROW_ON_ERROR);
        $secondResult = json_decode((string) file_get_contents($dir.'/second-result.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertSame($competitor !== 'refund', $firstResult['success'], json_encode($firstResult, JSON_THROW_ON_ERROR));
        self::assertSame($competitor === 'refund', $secondResult['success'], json_encode($secondResult, JSON_THROW_ON_ERROR));
        self::assertSame(0, $firstResult['transaction_level']);
        self::assertSame(0, $secondResult['transaction_level']);
        self::assertSame($firstId, (int) ($wait->blocking_id ?? $firstId));
        self::assertSame($secondId, (int) ($wait->waiting_id ?? $secondId));
        $state = $competitor === 'refund' ? 'refunded' : 'cancelled';
        $this->assertDatabaseHas('notes', ['id' => $noteId, 'note_state' => $state, 'total_rupiah' => 0]);
        self::assertSame(1, DB::table('inventory_movements')->where('source_type', 'work_item_store_stock_line_reversal')->count());
        self::assertSame($competitor === 'refund' ? 0 : 1, DB::table('note_mutation_events')->where('note_id', $noteId)->where('mutation_type', 'note_cancelled')->count());
        self::assertSame($competitor === 'refund' ? 0 : 1, DB::table('audit_outbox')->where('event_name', 'note_cancelled')->count());
        self::assertSame(1, DB::table('note_revisions')->where('note_root_id', $noteId)->count());
        self::assertSame($competitor === 'refund' ? 1 : 0, DB::table('customer_payments')->count());
        self::assertSame($competitor === 'refund' ? 1 : 0, DB::table('customer_refunds')->count());
        self::assertSame($competitor === 'refund' ? 0 : 1, DB::table('idempotency_records')->where('operation', 'cancel_note')->count());
        self::assertSame(0, DB::transactionLevel());
    }

    private function forkAction(string $worker, string $dir, Closure $action): int
    {
        $pid = pcntl_fork();
        if ($pid === -1) {
            throw new RuntimeException('Unable to fork cancellation race worker.');
        }
        if ($pid !== 0) {
            return $pid;
        }
        try {
            DB::purge();
            DB::reconnect();
            DB::statement('SET SESSION innodb_lock_wait_timeout = 20');
            file_put_contents($dir.'/'.$worker.'-connected.tmp', (string) DB::selectOne('SELECT CONNECTION_ID() AS id')->id);
            rename($dir.'/'.$worker.'-connected.tmp', $dir.'/'.$worker.'-connected');
            if ($worker === 'first') {
                $held = false;
                DB::listen(function (QueryExecuted $query) use ($dir, &$held): void {
                    $sql = strtolower($query->sql);
                    if ($held || ! str_contains($sql, 'from `notes`') || ! str_contains($sql, 'for update')) {
                        return;
                    }
                    $held = true;
                    file_put_contents($dir.'/first-held', (string) $query->connection->transactionLevel());
                    $this->awaitFile($dir.'/release-first');
                });
            }
            $result = $action();
            $receipt = $this->receipt($result);
        } catch (Throwable $e) {
            $receipt = ['success' => false, 'exception' => $e::class, 'message' => $e->getMessage(), 'transaction_level' => DB::transactionLevel()];
        }
        file_put_contents($dir.'/'.$worker.'-result.json', json_encode($receipt, JSON_THROW_ON_ERROR));
        DB::disconnect();
        exit(0);
    }

    private function receipt(mixed $result): array
    {
        return [
            'success' => $result->isSuccess(), 'message' => $result->message(),
            'errors' => method_exists($result, 'errors') ? $result->errors() : $result->data(),
            'transaction_level' => DB::transactionLevel(),
        ];
    }

    private function awaitFile(string $path): void
    {
        $deadline = microtime(true) + 18;
        while (! is_file($path)) {
            if (microtime(true) > $deadline) {
                throw new RuntimeException('Timed out at race barrier '.$path);
            }
            usleep(10000);
            clearstatcache(true, $path);
        }
    }
}
