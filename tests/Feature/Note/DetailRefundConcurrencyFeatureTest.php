<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Application\Note\Services\SelectedNoteRowsRefundPlanResolver;
use App\Application\Payment\Services\RecordSelectedRowsRefundPlanTransaction;
use Closure;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\Support\BuildsPrimitiveLifecycleFixture;
use Tests\TestCase;
use Throwable;

final class DetailRefundConcurrencyFeatureTest extends TestCase
{
    use BuildsPrimitiveLifecycleFixture;
    use DatabaseTruncation;

    protected function tearDown(): void
    {
        RefreshDatabaseState::$migrated = false;
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_overlapping_different_keys_validate_current_rows_under_root_lock(): void
    {
        $this->refundRace(false);
    }

    public function test_overlapping_same_key_replays_without_duplicate_effects(): void
    {
        $this->refundRace(true);
    }

    private function refundRace(bool $sameKey): void
    {
        self::assertTrue(function_exists('pcntl_fork'));
        self::assertSame('mysql', DB::connection()->getDriverName());
        $this->preparePrimitiveFixture();
        $cashier = $this->loginAsKasir();
        $this->post(route('notes.workspace.store'), $this->primitiveWorkspace([$this->primitiveItems()[0]], 'refund-race-create'))
            ->assertSessionHasNoErrors();
        $noteId = (string) DB::table('notes')->value('id');
        $this->post(route('cashier.notes.payments.store', ['noteId' => $noteId]), $this->primitivePayment('refund-race-pay', 142539, 'transfer'))
            ->assertSessionHasNoErrors();
        $row = (string) DB::table('work_items')->value('id');
        $actorId = (string) $cashier->getAuthIdentifier();
        $resolved = app(SelectedNoteRowsRefundPlanResolver::class)->resolve($noteId, [$row], [$row => true]);
        self::assertTrue($resolved->isSuccess());
        $plan = $resolved->data()['plan'];
        $refund = fn (string $key) => app(RecordSelectedRowsRefundPlanTransaction::class)->run(
            $plan, '2026-09-15', 'Return paid row', $actorId, 'kasir',
            ['_actor_id' => $actorId, '_note_id' => $noteId, 'selected_row_ids' => [$row],
                'stock_returns' => [$row => true], 'idempotency_key' => $key],
        );

        $dir = sys_get_temp_dir().'/glasspos-refund-race-'.bin2hex(random_bytes(8));
        self::assertTrue(mkdir($dir, 0700));
        self::assertSame(0, DB::transactionLevel());
        DB::disconnect();
        $children = [];
        $wait = null;
        try {
            $children[] = $this->forkAction('first', $dir, fn () => $refund('refund-race-first'));
            $this->awaitFile($dir.'/first-held');
            self::assertSame('1', file_get_contents($dir.'/first-held'));
            $children[] = $this->forkAction('second', $dir, fn () => $refund($sameKey ? 'refund-race-first' : 'refund-race-second'));
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
                    if ($query !== null && (int) $query->TIME >= 1 && ((str_contains($sql, 'from `notes`') && str_contains($sql, 'for update')) || str_contains($sql, 'insert into `idempotency_records`'))) {
                        $wait = (object) ['waiting_id' => $secondId, 'blocking_id' => $firstId];
                    }
                }
                if ($wait !== null || is_file($dir.'/first-result.json') || is_file($dir.'/second-result.json')) {
                    break;
                }
                usleep(10000);
            } while (microtime(true) < $deadline);
            self::assertNotNull($wait, 'The competing refund must overlap and wait on the same canonical root lock.');
            self::assertFileDoesNotExist($dir.'/first-result.json');
            self::assertFileDoesNotExist($dir.'/second-result.json');
        } finally {
            touch($dir.'/release-first');
            foreach ($children as $pid) {
                pcntl_waitpid($pid, $status);
                self::assertTrue(pcntl_wifexited($status) && pcntl_wexitstatus($status) === 0, 'Refund race worker must exit normally.');
            }
            DB::purge();
            DB::reconnect();
        }

        $firstResult = json_decode((string) file_get_contents($dir.'/first-result.json'), true, flags: JSON_THROW_ON_ERROR);
        $secondResult = json_decode((string) file_get_contents($dir.'/second-result.json'), true, flags: JSON_THROW_ON_ERROR);
        self::assertTrue($firstResult['success'], json_encode($firstResult, JSON_THROW_ON_ERROR));
        self::assertSame($sameKey, $secondResult['success'], json_encode($secondResult, JSON_THROW_ON_ERROR));
        self::assertSame(0, $firstResult['transaction_level']);
        self::assertSame(0, $secondResult['transaction_level']);
        self::assertSame($firstId, (int) ($wait->blocking_id ?? 0));
        self::assertSame($secondId, (int) ($wait->waiting_id ?? 0));
        $this->assertDatabaseHas('notes', ['id' => $noteId, 'note_state' => 'refunded', 'total_rupiah' => 0]);
        self::assertSame(1, DB::table('customer_refunds')->count());
        self::assertSame(1, DB::table('refund_component_allocations')->count());
        self::assertSame(1, DB::table('audit_outbox')->where('event_name', 'selected_rows_refund_plan_recorded')->count());
        self::assertSame(1, DB::table('idempotency_records')->where('operation', 'record_selected_rows_refund')->where('status', 'succeeded')->count());
        self::assertSame(1, DB::table('inventory_movements')->where('source_type', 'work_item_store_stock_line_reversal')->count());
        $this->assertDatabaseHas('product_inventory', ['product_id' => 'primitive-p', 'qty_on_hand' => 17]);
        self::assertSame(0, DB::transactionLevel());
    }

    private function forkAction(string $worker, string $dir, Closure $action): int
    {
        $pid = pcntl_fork();
        if ($pid === -1) {
            throw new RuntimeException('Unable to fork refund race worker.');
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
            $receipt = ['success' => $result->isSuccess(), 'message' => $result->message(), 'transaction_level' => DB::transactionLevel()];
        } catch (Throwable $e) {
            $receipt = ['success' => false, 'message' => $e->getMessage(), 'transaction_level' => DB::transactionLevel()];
        }
        file_put_contents($dir.'/'.$worker.'-result.json', json_encode($receipt, JSON_THROW_ON_ERROR));
        DB::disconnect();
        exit(0);
    }

    private function awaitFile(string $path): void
    {
        $deadline = microtime(true) + 18;
        while (! is_file($path)) {
            if (microtime(true) > $deadline) {
                throw new RuntimeException('Timed out at refund race barrier '.$path);
            }
            usleep(10000);
            clearstatcache(true, $path);
        }
    }
}
