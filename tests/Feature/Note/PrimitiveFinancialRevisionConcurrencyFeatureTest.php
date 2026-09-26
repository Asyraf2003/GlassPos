<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

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

final class PrimitiveFinancialRevisionConcurrencyFeatureTest extends TestCase
{
    use BuildsPrimitiveLifecycleFixture;
    use DatabaseTruncation;

    protected function tearDown(): void
    {
        RefreshDatabaseState::$migrated = false;
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_payment_commits_before_waiting_revision_carries_allocation(): void
    {
        $this->runFinancialRace(false);
    }

    public function test_inventory_refund_commits_before_waiting_revision_without_duplicate_return(): void
    {
        $this->runFinancialRace(true);
    }

    private function runFinancialRace(bool $refund): void
    {
        self::assertTrue(function_exists('pcntl_fork'));
        self::assertSame('mysql', DB::connection()->getDriverName());
        $this->preparePrimitiveFixture();
        $cashier = $this->loginAsKasir();
        $create = $this->primitiveWorkspace($this->primitiveItems(), 'financial-race-create');
        if ($refund) {
            $create['inline_payment'] = ['decision' => 'pay_full', 'payment_method' => 'cash', 'paid_at' => '2026-09-15', 'amount_paid_rupiah' => 395933, 'amount_received_rupiah' => 400003];
        }
        $this->post(route('notes.workspace.store'), $create)->assertSessionHasNoErrors();
        $noteId = (string) DB::table('notes')->value('id');
        $base = (string) DB::table('notes')->value('current_revision_id');
        $oldLine = (string) DB::table('work_item_store_stock_lines')->where('product_id', 'primitive-p')->value('id');
        $oldRow = (string) DB::table('work_item_store_stock_lines')->where('id', $oldLine)->value('work_item_id');
        $original = DB::table('inventory_movements')->where('source_type', 'work_item_store_stock_line')->orderBy('id')->get();
        $payment = $this->primitivePayment('financial-race-payment', 73129, 'cash', 100003);
        $admin = $this->loginAsAuthorizedAdmin();
        $items = $refund ? array_values(array_slice($this->primitiveItems(), 1)) : $this->primitiveItems(70211);
        $revision = array_replace($this->primitiveWorkspace($items, 'financial-race-revision'), ['base_revision_id' => $base]);
        $firstAction = function () use ($refund, $cashier, $noteId, $oldRow, $payment): void {
            $this->actingAs($cashier);
            if ($refund) {
                $this->post(route('cashier.notes.refunds.store', ['noteId' => $noteId]), ['selected_row_ids' => [$oldRow], 'stock_returns' => [$oldRow => true], 'refunded_at' => '2026-09-15', 'reason' => 'Concurrent paid product return', 'idempotency_key' => 'financial-race-refund'])->assertRedirect()->assertSessionHasNoErrors();
            } else {
                $this->post(route('cashier.notes.payments.store', ['noteId' => $noteId]), $payment)->assertRedirect()->assertSessionHasNoErrors();
            }
        };
        $secondAction = function () use ($admin, $noteId, $revision): void {
            $this->actingAs($admin)->patch(route('admin.notes.workspace.update', ['noteId' => $noteId]), $revision)->assertRedirect()->assertSessionHasNoErrors();
        };
        $dir = sys_get_temp_dir().'/glasspos-financial-race-'.bin2hex(random_bytes(8));
        self::assertTrue(mkdir($dir, 0700));
        self::assertSame(0, DB::transactionLevel());
        DB::disconnect();
        $children = [];
        $wait = null;
        try {
            $children[] = $this->forkAction('first', $dir, $firstAction);
            $this->awaitFile($dir.'/first-held');
            self::assertGreaterThanOrEqual(1, (int) file_get_contents($dir.'/first-held'));
            $children[] = $this->forkAction('second', $dir, $secondAction);
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
                    // Also retain server execution evidence when MariaDB omits a lock graph row.
                    $query = DB::selectOne('SELECT ID, TIME, INFO FROM information_schema.PROCESSLIST WHERE ID = ?', [$secondId]);
                    $sql = strtolower((string) ($query->INFO ?? ''));
                    if ($query !== null && (int) $query->TIME >= 1
                        && (str_contains($sql, 'from `notes`') && str_contains($sql, 'for update'))) {
                        self::assertSame(1, DB::table('note_revisions')->count());
                        $wait = (object) ['waiting_id' => $secondId, 'winner_id' => $firstId, 'proof_kind' => 'server-executing-before-winner-release', 'sql' => $sql];
                    }
                }
                if ($wait !== null || is_file($dir.'/second-result.json')) {
                    break;
                }
                usleep(10000);
            } while (microtime(true) < $deadline);
            self::assertNotNull($wait, 'Independent second connection must be observed waiting on first transaction.');
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
        $first = json_decode((string) file_get_contents($dir.'/first-result.json'), true, flags: JSON_THROW_ON_ERROR);
        $second = json_decode((string) file_get_contents($dir.'/second-result.json'), true, flags: JSON_THROW_ON_ERROR);
        file_put_contents($dir.'/proof.json', json_encode(['lock_wait' => $wait, 'first' => $first, 'second' => $second], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        self::assertTrue($first['success'], json_encode($first, JSON_THROW_ON_ERROR));
        self::assertTrue($second['success'], json_encode($second, JSON_THROW_ON_ERROR));
        self::assertSame(0, $first['transaction_level']);
        self::assertSame(0, $second['transaction_level']);
        self::assertSame(2, DB::table('note_revisions')->count());
        self::assertSame(1, DB::table('audit_outbox')->where('event_name', 'note_revision_created')->count());
        self::assertSame(1, DB::table('customer_payments')->count());
        self::assertSame($refund ? 395933 : 73129, (int) DB::table('customer_payments')->sum('amount_rupiah'));
        self::assertSame($refund ? 1 : 0, DB::table('customer_refunds')->count());
        self::assertSame($refund ? 142539 : 0, (int) DB::table('refund_component_allocations')->sum('refunded_amount_rupiah'));
        $this->assertDatabaseHas('note_history_projection', ['note_id' => $noteId, 'total_rupiah' => $refund ? 253394 : 402425, 'outstanding_rupiah' => $refund ? 0 : 329296]);
        self::assertSame($original->toJson(), DB::table('inventory_movements')->whereIn('id', $original->pluck('id'))->orderBy('id')->get()->toJson());
        $this->assertDatabaseHas('product_inventory', ['product_id' => 'primitive-p', 'qty_on_hand' => $refund ? 17 : 14]);
        $this->assertDatabaseHas('product_inventory', ['product_id' => 'primitive-q', 'qty_on_hand' => 21]);
        $movements = DB::table('inventory_movements')->where('source_type', '<>', 'seed_fixture')->get();
        self::assertCount($refund ? 5 : 6, $movements);
        self::assertSame(1, $movements->where('source_id', $oldLine)->where('qty_delta', 3)->count(), 'Original product stock must be compensated exactly once.');
        self::assertSame($refund ? 1 : 0, DB::table('inventory_movements')->where('reversal_source_id', $oldLine)->count());
        self::assertSame($refund ? -23006 : -82169, (int) $movements->sum('total_cost_rupiah'));
        self::assertSame(0, DB::table('note_revision_surplus_dispositions')->count());
        self::assertSame(0, DB::transactionLevel());
    }

    private function forkAction(string $worker, string $dir, Closure $action): int
    {
        $pid = pcntl_fork();
        if ($pid === -1) {
            throw new RuntimeException('Unable to fork financial worker.');
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
            $action();
            $receipt = ['success' => true, 'transaction_level' => DB::transactionLevel()];
        } catch (Throwable $e) {
            $receipt = ['success' => false, 'exception' => $e::class, 'message' => $e->getMessage()];
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
                throw new RuntimeException('Timed out at race barrier '.$path);
            }
            usleep(10000);
            clearstatcache(true, $path);
        }
    }
}
