<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Application\Note\UseCases\CreateNoteRevisionHandler;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\Support\BuildsPrimitiveLifecycleFixture;
use Tests\TestCase;
use Throwable;

final class PrimitiveRevisionConcurrencyFeatureTest extends TestCase
{
    use BuildsPrimitiveLifecycleFixture;
    use DatabaseTruncation;

    protected function tearDown(): void
    {
        RefreshDatabaseState::$migrated = false;
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_concurrent_exact_revision_replay_wins_over_stale_base(): void
    {
        $this->runRevisionRace('exact');
    }

    public function test_concurrent_same_key_changed_payload_is_conflict(): void
    {
        $this->runRevisionRace('changed');
    }

    public function test_concurrent_same_key_different_root_is_conflict(): void
    {
        $this->runRevisionRace('different-root');
    }

    public function test_concurrent_fresh_key_with_stale_base_is_stale_revision(): void
    {
        $this->runRevisionRace('stale');
    }

    public function test_winner_rollback_allows_waiting_claim_to_proceed(): void
    {
        $this->runRevisionRace('rollback');
    }

    private function runRevisionRace(string $scenario): void
    {
        self::assertTrue(function_exists('pcntl_fork'), 'True race proof requires pcntl_fork.');
        self::assertSame('mysql', DB::connection()->getDriverName(), 'This lock-wait probe targets disposable MariaDB.');
        $this->preparePrimitiveFixture();
        $admin = $this->loginAsAuthorizedAdmin();
        $this->post(route('notes.workspace.store'), $this->primitiveWorkspace($this->primitiveItems(), 'race-create'))
            ->assertRedirect()->assertSessionHasNoErrors();
        $noteId = (string) DB::table('notes')->value('id');
        $base = (string) DB::table('notes')->value('current_revision_id');
        $actorId = (string) $admin->getAuthIdentifier();
        $payload = array_replace($this->primitiveWorkspace($this->primitiveItems(70211), 'race-same-key'), ['base_revision_id' => $base]);
        $secondNoteId = $noteId;
        $secondPayload = $payload;
        if ($scenario === 'stale') {
            $secondPayload['idempotency_key'] = 'race-fresh-key';
        }
        if ($scenario === 'changed') {
            $secondPayload['items'][1]['service']['price_rupiah'] = 90001;
        }
        if ($scenario === 'different-root') {
            $this->post(route('notes.workspace.store'), $this->primitiveWorkspace([$this->primitiveItems()[1]], 'race-other-root'))
                ->assertRedirect()->assertSessionHasNoErrors();
            $secondNoteId = (string) DB::table('notes')->where('id', '<>', $noteId)->value('id');
            $secondPayload['base_revision_id'] = (string) DB::table('notes')->where('id', $secondNoteId)->value('current_revision_id');
        }
        $dir = sys_get_temp_dir().'/glasspos-revision-race-'.bin2hex(random_bytes(8));
        self::assertTrue(mkdir($dir, 0700));
        self::assertSame(0, DB::transactionLevel());
        DB::disconnect();
        $children = [];
        $wait = null;
        try {
            $children[] = $this->forkRevision('first', $dir, $noteId, $actorId, $payload, $scenario === 'rollback');
            $this->awaitFile($dir.'/first-held');
            $children[] = $this->forkRevision('second', $dir, $secondNoteId, $actorId, $secondPayload);
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
                if ($wait === null && $scenario !== 'exact') {
                    // MariaDB can expose this unique-insert wait only in PROCESSLIST.
                    // Keep A's original lock graph gate; the additional probes
                    // requires the server to be executing the claim while winner is held.
                    $query = DB::selectOne('SELECT ID, TIME, INFO FROM information_schema.PROCESSLIST WHERE ID = ?', [$secondId]);
                    $sql = strtolower((string) ($query->INFO ?? ''));
                    if ($query !== null && (int) $query->TIME >= 1
                        && (str_starts_with($sql, 'insert into `idempotency_records`')
                            || (str_contains($sql, 'from `notes`') && str_contains($sql, 'for update')))) {
                        self::assertSame(0, DB::table('idempotency_records')->where('operation', 'create_note_revision')->count());
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
        if ($scenario === 'rollback') {
            self::assertSame(RuntimeException::class, $first['exception']);
            self::assertSame('Injected winner rollback', $first['message']);
            self::assertTrue($second['success'], json_encode($second, JSON_THROW_ON_ERROR));
            self::assertSame('Revisi nota berhasil disimpan.', $second['message']);
        } else {
            self::assertTrue($first['success'] ?? false, json_encode($first, JSON_THROW_ON_ERROR));
        }
        self::assertSame(($scenario === 'rollback' ? $second : $first)['effects'], $this->effectHashes(), 'Loser must not duplicate revision, allocation, inventory or audit effects.');
        // Effects must be exactly one even if the replay response contract is broken.
        self::assertSame(2, DB::table('note_revisions')->where('note_root_id', $noteId)->count());
        $this->assertDatabaseHas('notes', ['id' => $noteId, 'total_rupiah' => 402425]);
        self::assertSame(1, DB::table('idempotency_records')->where('operation', 'create_note_revision')->count());
        self::assertSame(1, DB::table('audit_outbox')->where('event_name', 'note_revision_created')->count());
        self::assertSame(0, DB::transactionLevel());
        if ($scenario === 'rollback') {
            return;
        }
        if ($scenario === 'stale') {
            self::assertArrayNotHasKey('exception', $second);
            self::assertFalse($second['success']);
            self::assertSame('STALE_REVISION', $second['data']['code']);
            return;
        }
        if ($scenario !== 'exact') {
            self::assertArrayNotHasKey('exception', $second);
            self::assertFalse($second['success']);
            self::assertSame(['IDEMPOTENCY_KEY_PAYLOAD_MISMATCH'], $second['data']['idempotency_key']);
            self::assertSame('Idempotency key revisi sudah dipakai untuk payload berbeda.', $second['message']);
            if ($scenario === 'different-root') {
                self::assertSame(1, DB::table('note_revisions')->where('note_root_id', $secondNoteId)->count());
                $this->assertDatabaseHas('notes', ['id' => $secondNoteId, 'total_rupiah' => 63719]);
            }
            return;
        }
        self::assertTrue($second['success'] ?? false, 'Exact concurrent retry must replay, not fail: '.json_encode($second, JSON_THROW_ON_ERROR).'; proof='.$dir.'/proof.json');
        self::assertSame($first['data']['revision_id'], $second['data']['revision_id']);
        self::assertSame('Revisi nota sudah diproses sebelumnya.', $second['message']);
    }

    private function forkRevision(string $worker, string $dir, string $noteId, string $actorId, array $payload, bool $rollbackWinner = false): int
    {
        $pid = pcntl_fork();
        if ($pid === -1) {
            throw new RuntimeException('Unable to fork revision worker.');
        }
        if ($pid !== 0) {
            return $pid;
        }
        try {
            DB::purge();
            DB::reconnect();
            DB::statement('SET SESSION innodb_lock_wait_timeout = 20');
            // Publish readiness atomically; mere file existence can expose an empty connection ID.
            file_put_contents($dir.'/'.$worker.'-connected.tmp', (string) DB::selectOne('SELECT CONNECTION_ID() AS id')->id);
            rename($dir.'/'.$worker.'-connected.tmp', $dir.'/'.$worker.'-connected');
            if ($worker === 'first') {
                $held = false;
                DB::listen(function (QueryExecuted $query) use ($dir, &$held, $rollbackWinner): void {
                    $sql = strtolower($query->sql);
                    if ($held || ! str_contains($sql, 'from `notes`') || ! str_contains($sql, 'for update')) {
                        return;
                    }
                    $held = true;
                    file_put_contents($dir.'/first-held', (string) $query->connection->transactionLevel());
                    $this->awaitFile($dir.'/release-first');
                    if ($rollbackWinner) {
                        throw new RuntimeException('Injected winner rollback');
                    }
                });
            }
            $result = app(CreateNoteRevisionHandler::class)->handle($noteId, $payload, $actorId);
            $receipt = ['success' => $result->isSuccess(), 'message' => $result->message(), 'data' => $result->data(), 'effects' => $this->effectHashes()];
        } catch (Throwable $e) {
            $receipt = ['success' => false, 'exception' => $e::class, 'message' => $e->getMessage()];
        }
        file_put_contents($dir.'/'.$worker.'-result.json', json_encode($receipt, JSON_THROW_ON_ERROR));
        DB::disconnect();
        exit(0);
    }

    private function effectHashes(): array
    {
        $hashes = [];
        foreach (['notes', 'note_revisions', 'note_revision_lines', 'note_revision_settlements',
            'work_items', 'work_item_store_stock_lines', 'work_item_service_details', 'work_item_external_purchase_lines',
            'customer_payments', 'payment_allocations', 'payment_component_allocations', 'customer_payment_cash_details',
            'customer_refunds', 'refund_component_allocations', 'product_inventory', 'product_inventory_costing',
            'inventory_movements', 'idempotency_records', 'audit_logs', 'audit_outbox', 'audit_events', 'audit_event_snapshots'] as $table) {
            $rows = DB::table($table)->get()->map(fn ($row): string => json_encode($row, JSON_THROW_ON_ERROR))->all();
            sort($rows);
            $hashes[$table] = hash('sha256', json_encode($rows, JSON_THROW_ON_ERROR));
        }
        return $hashes;
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
