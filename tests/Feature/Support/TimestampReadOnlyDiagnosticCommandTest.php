<?php

declare(strict_types=1);

namespace Tests\Feature\Support;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class TimestampReadOnlyDiagnosticCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.timezone' => 'Asia/Makassar',
            'app.display_timezone' => 'Asia/Makassar',
        ]);
    }

    public function test_it_outputs_raw_and_display_timestamp_samples_without_date_only_candidates(): void
    {
        $this->insertAuditEvent();

        $exitCode = Artisan::call('diagnostics:timestamp-readonly', [
            '--limit' => 1,
            '--table' => 'audit_events',
        ]);

        $output = Artisan::output();

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('Timestamp read-only diagnostic', $output);
        self::assertStringContainsString('mode: READ ONLY', $output);
        self::assertStringContainsString('app.timezone: Asia/Makassar', $output);
        self::assertStringContainsString('app.display_timezone: Asia/Makassar', $output);
        self::assertStringContainsString('audit_events', $output);
        self::assertStringContainsString('occurred_at', $output);
        self::assertStringContainsString('2026-06-29 10:07:45', $output);
        self::assertStringContainsString('29 Juni 2026 10:07', $output);
        self::assertStringContainsString('note_refund_recorded', $output);

        foreach ([
            'refunded_at',
            'transaction_date',
            'shipment_date',
            'due_date',
            'expense_date',
            'payment_date',
        ] as $dateOnlyField) {
            self::assertStringNotContainsString($dateOnlyField, $output);
        }
    }

    public function test_it_exposes_legacy_rows_without_guessing_or_shifting_their_semantics(): void
    {
        DB::table('audit_events')->insert([
            [
                'id' => 'audit-utc-like-legacy-row',
                'bounded_context' => 'note',
                'aggregate_type' => 'note',
                'aggregate_id' => 'note-utc-like',
                'event_name' => 'diagnostic_utc_like_row',
                'actor_id' => null,
                'actor_role' => null,
                'reason' => 'Legacy row intentionally left untouched after timezone unification.',
                'source_channel' => 'test',
                'request_id' => null,
                'correlation_id' => null,
                'occurred_at' => '2026-06-29 03:45:00',
                'metadata_json' => json_encode(['classification' => 'legacy_unknown']),
            ],
            [
                'id' => 'audit-local-like-legacy-row',
                'bounded_context' => 'note',
                'aggregate_type' => 'note',
                'aggregate_id' => 'note-local-like',
                'event_name' => 'diagnostic_local_like_row',
                'actor_id' => null,
                'actor_role' => null,
                'reason' => 'Legacy local-like row intentionally left untouched.',
                'source_channel' => 'test',
                'request_id' => null,
                'correlation_id' => null,
                'occurred_at' => '2026-06-29 11:45:00',
                'metadata_json' => json_encode(['classification' => 'legacy_unknown']),
            ],
        ]);

        $exitCode = Artisan::call('diagnostics:timestamp-readonly', [
            '--limit' => 10,
            '--table' => 'audit_events',
        ]);

        $output = Artisan::output();

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('diagnostic_utc_like_row', $output);
        self::assertStringContainsString('2026-06-29 03:45:00', $output);
        self::assertStringContainsString('29 Juni 2026 03:45', $output);
        self::assertStringContainsString('diagnostic_local_like_row', $output);
        self::assertStringContainsString('2026-06-29 11:45:00', $output);
        self::assertStringContainsString('29 Juni 2026 11:45', $output);
    }

    public function test_it_does_not_run_database_write_queries(): void
    {
        $this->insertAuditEvent();

        $queries = [];

        DB::listen(static function (QueryExecuted $query) use (&$queries): void {
            $queries[] = $query->sql;
        });

        $exitCode = Artisan::call('diagnostics:timestamp-readonly', [
            '--limit' => 1,
            '--table' => 'audit_events',
        ]);

        self::assertSame(0, $exitCode);

        $writeQueries = array_values(array_filter(
            $queries,
            static fn (string $sql): bool => preg_match(
                '/^\s*(insert|update|delete|replace|truncate|alter|create|drop)\b/i',
                $sql
            ) === 1
        ));

        self::assertSame([], $writeQueries, implode(PHP_EOL, $writeQueries));
    }

    private function insertAuditEvent(): void
    {
        DB::table('audit_events')->insert([
            'id' => 'audit-diagnostic-1',
            'bounded_context' => 'note',
            'aggregate_type' => 'note',
            'aggregate_id' => 'note-1',
            'event_name' => 'note_refund_recorded',
            'actor_id' => null,
            'actor_role' => null,
            'reason' => 'diagnostic test',
            'source_channel' => 'test',
            'request_id' => null,
            'correlation_id' => null,
            'occurred_at' => '2026-06-29 10:07:45',
            'metadata_json' => json_encode(['source' => 'timestamp-readonly-diagnostic-test']),
        ]);
    }
}
