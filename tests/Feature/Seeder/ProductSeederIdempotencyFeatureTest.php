<?php

declare(strict_types=1);

namespace Tests\Feature\Seeder;

use Database\Seeders\Product\ProductScenarioRecreatedSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use Stringable;
use Tests\TestCase;

final class ProductSeederIdempotencyFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_recreated_product_scenario_can_be_rerun_without_warning_or_state_growth(): void
    {
        $logger = new class extends AbstractLogger {
            public int $warningCount = 0;

            /**
             * @param mixed $level
             * @param array<string, mixed> $context
             */
            public function log($level, string|Stringable $message, array $context = []): void
            {
                if ($level === LogLevel::WARNING) {
                    $this->warningCount++;
                }
            }
        };

        Log::swap($logger);

        $this->seed(ProductScenarioRecreatedSeeder::class);

        $this->assertRecreatedScenarioState();

        $this->seed(ProductScenarioRecreatedSeeder::class);

        $this->assertRecreatedScenarioState();

        self::assertSame(0, $logger->warningCount);
    }

    private function assertRecreatedScenarioState(): void
    {
        $rows = DB::table('products')
            ->where('kode_barang', 'like', 'PRD-RCR-%')
            ->get([
                'kode_barang',
                'deleted_at',
                'reorder_point_qty',
                'critical_threshold_qty',
            ]);

        self::assertCount(8, $rows);

        self::assertSame(
            4,
            $rows->filter(fn (object $row): bool => $row->deleted_at === null)->count(),
            'Expected exactly one active replacement for each recreated product code.'
        );

        self::assertSame(
            4,
            $rows->filter(fn (object $row): bool => $row->deleted_at !== null)->count(),
            'Expected exactly one deleted historical product for each recreated product code.'
        );

        foreach (['PRD-RCR-001', 'PRD-RCR-002', 'PRD-RCR-003', 'PRD-RCR-004'] as $code) {
            $matchingRows = $rows->where('kode_barang', $code);

            self::assertSame(2, $matchingRows->count(), 'Expected two lifecycle rows for ' . $code . '.');
            self::assertSame(1, $matchingRows->where('deleted_at', null)->count(), 'Expected one active row for ' . $code . '.');
            self::assertSame(1, $matchingRows->filter(fn (object $row): bool => $row->deleted_at !== null)->count(), 'Expected one deleted row for ' . $code . '.');

            foreach ($matchingRows as $row) {
                self::assertNotNull($row->reorder_point_qty, 'Expected reorder threshold for ' . $code . '.');
                self::assertNotNull($row->critical_threshold_qty, 'Expected critical threshold for ' . $code . '.');
            }
        }
    }
}
