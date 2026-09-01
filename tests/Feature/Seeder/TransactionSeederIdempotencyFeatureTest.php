<?php

declare(strict_types=1);

namespace Tests\Feature\Seeder;

use Carbon\CarbonImmutable;
use Database\Seeders\CreateOnly\CreateInventorySeeder;
use Database\Seeders\CreateOnly\CreateMasterBasicSeeder;
use Database\Seeders\CreateOnly\CreateTransactionMonthNormalSeeder;
use Database\Seeders\CreateOnly\CreateTransactionWeekSeeder;
use Database\Seeders\CreateOnly\CreateUserSeeder;
use Database\Seeders\CreateOnly\Support\CreateOnlyTransactionSeedContext;
use Database\Seeders\CreateOnly\Support\CreateOnlyTransactionSeedIdentity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class TransactionSeederIdempotencyFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_weekly_transaction_seed_replays_without_payload_drift_after_stock_out(): void
    {
        CarbonImmutable::setTestNow('2026-09-01 08:00:00');
        $this->seedTransactionDependencies();

        $this->seed(CreateTransactionWeekSeeder::class);

        $firstStock = $this->stockSnapshot();
        $firstHashes = $this->idempotencyHashes('seed-create-transaction-week-v2-2026-09-%');

        $this->assertSame(6, $this->weeklyNoteCount());
        $this->assertCount(6, $firstHashes);
        $this->assertSame(19, $firstStock['prod-basic-001'] ?? null);

        $this->seed(CreateTransactionWeekSeeder::class);

        $this->assertSame(6, $this->weeklyNoteCount());
        $this->assertSame($firstStock, $this->stockSnapshot());
        $this->assertSame($firstHashes, $this->idempotencyHashes('seed-create-transaction-week-v2-2026-09-%'));
    }

    public function test_month_normal_transaction_seed_replays_after_weekly_prerequisite_without_product_pool_drift(): void
    {
        CarbonImmutable::setTestNow('2026-09-01 08:00:00');
        $this->seedTransactionDependencies();

        $this->seed(CreateTransactionWeekSeeder::class);
        $stockAfterWeekly = $this->stockSnapshot();
        $this->assertSame(19, $stockAfterWeekly['prod-basic-001'] ?? null);

        $this->seed(CreateTransactionMonthNormalSeeder::class);

        $firstStock = $this->stockSnapshot();
        $firstHashes = $this->idempotencyHashes('seed-create-transaction-month-normal-v2-2026-09-%');

        $this->assertSame(6, $this->weeklyNoteCount());
        $this->assertSame(28, $this->monthlyNormalNoteCount());
        $this->assertCount(28, $firstHashes);

        $this->seed(CreateTransactionMonthNormalSeeder::class);

        $this->assertSame(28, $this->monthlyNormalNoteCount());
        $this->assertSame($firstStock, $this->stockSnapshot());
        $this->assertSame($firstHashes, $this->idempotencyHashes('seed-create-transaction-month-normal-v2-2026-09-%'));
    }

    public function test_transaction_seed_identity_changes_with_active_month(): void
    {
        CarbonImmutable::setTestNow('2026-09-15 12:00:00');
        $september = CreateOnlyTransactionSeedIdentity::key('week', 1);

        CarbonImmutable::setTestNow('2026-10-15 12:00:00');
        $october = CreateOnlyTransactionSeedIdentity::key('week', 1);

        $this->assertSame('seed-create-transaction-week-v2-2026-09-0001', $september);
        $this->assertSame('seed-create-transaction-week-v2-2026-10-0001', $october);
        $this->assertNotSame($september, $october);
    }

    public function test_transaction_seed_actor_is_bound_to_cashier_fixture_identity(): void
    {
        $this->seed(CreateUserSeeder::class);

        $adminId = (string) DB::table('users')->where('email', 'admin@gmail.com')->value('id');
        $cashierId = (string) DB::table('users')->where('email', 'kasir@gmail.com')->value('id');

        DB::table('actor_accesses')->where('actor_id', $adminId)->update(['role' => 'kasir']);

        $this->assertSame($cashierId, (new CreateOnlyTransactionSeedContext())->cashierActorId());
    }

    private function seedTransactionDependencies(): void
    {
        $this->seed([
            CreateUserSeeder::class,
            CreateMasterBasicSeeder::class,
            CreateInventorySeeder::class,
        ]);
    }

    private function weeklyNoteCount(): int
    {
        return DB::table('notes')
            ->where('customer_name', 'like', 'Seed Customer Mingguan %')
            ->count();
    }

    private function monthlyNormalNoteCount(): int
    {
        return DB::table('notes')
            ->where('customer_name', 'like', 'Seed Customer Bulanan %')
            ->count();
    }

    /** @return array<string, int> */
    private function stockSnapshot(): array
    {
        return DB::table('product_inventory')
            ->orderBy('product_id')
            ->pluck('qty_on_hand', 'product_id')
            ->map(static fn (mixed $qty): int => (int) $qty)
            ->all();
    }

    /** @return array<string, string> */
    private function idempotencyHashes(string $keyLike): array
    {
        return DB::table('idempotency_records')
            ->where('operation', 'create_transaction_workspace')
            ->where('idempotency_key', 'like', $keyLike)
            ->orderBy('idempotency_key')
            ->pluck('request_hash', 'idempotency_key')
            ->map(static fn (mixed $hash): string => (string) $hash)
            ->all();
    }
}
