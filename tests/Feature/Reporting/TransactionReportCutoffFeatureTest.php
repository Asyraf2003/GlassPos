<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Application\Reporting\UseCases\GetTransactionReportDatasetHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class TransactionReportCutoffFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_future_payment_does_not_erase_historical_receivable(): void
    {
        DB::table('notes')->insert(['id' => 'cutoff-note', 'customer_name' => 'Customer', 'transaction_date' => '2026-09-30', 'total_rupiah' => 100001]);
        DB::table('customer_payments')->insert(['id' => 'future-payment', 'amount_rupiah' => 100001, 'paid_at' => '2026-10-01']);
        DB::table('payment_allocations')->insert(['id' => 'future-allocation', 'customer_payment_id' => 'future-payment', 'note_id' => 'cutoff-note', 'amount_rupiah' => 100001]);
        $data = app(GetTransactionReportDatasetHandler::class)->handle('2026-09-01', '2026-09-30')->data();
        self::assertSame(0, $data['summary']['allocated_payment_rupiah']);
        self::assertSame(100001, $data['summary']['outstanding_rupiah']);
    }

    public function test_payment_cutoff_includes_last_day_and_preserves_unbounded_current_position(): void
    {
        DB::table('notes')->insert(['id' => 'boundary-note', 'customer_name' => 'Customer', 'transaction_date' => '2026-09-01', 'total_rupiah' => 100001]);
        foreach (['2026-09-01' => 1, '2026-09-30' => 99999, '2026-10-01' => 1] as $date => $amount) {
            DB::table('customer_payments')->insert(['id' => $date, 'amount_rupiah' => $amount, 'paid_at' => $date]);
            DB::table('payment_allocations')->insert(['id' => $date, 'customer_payment_id' => $date, 'note_id' => 'boundary-note', 'amount_rupiah' => $amount]);
        }
        $reader = app(\App\Adapters\Out\Reporting\Queries\TransactionSummaryReportingQuery::class);
        $historical = $reader->rows('2026-09-01', '2026-09-30')[0];
        self::assertSame(100000, $historical['allocated_payment_rupiah']);
        self::assertSame(100000, $historical['gross_payment_rupiah']);
        self::assertSame(1, $historical['outstanding_rupiah']);
        $current = $reader->rows(null, null, 'current')[0];
        self::assertSame(100001, $current['allocated_payment_rupiah']);
        self::assertSame(0, $current['outstanding_rupiah']);
    }

    public function test_future_revision_and_cancellation_do_not_restate_historical_note(): void
    {
        DB::table('notes')->insert([
            'id' => 'versioned-note',
            'customer_name' => 'Current Customer',
            'transaction_date' => '2026-09-15',
            'total_rupiah' => 130001,
            'note_state' => 'cancelled',
            'current_revision_id' => 'versioned-note-r002',
            'latest_revision_number' => 2,
        ]);
        DB::table('note_revisions')->insert([
            [
                'id' => 'versioned-note-r001', 'note_root_id' => 'versioned-note', 'revision_number' => 1,
                'parent_revision_id' => null, 'created_by_actor_id' => null, 'reason' => 'Initial',
                'customer_name' => 'Historical Customer', 'customer_phone' => null, 'transaction_date' => '2026-09-15',
                'grand_total_rupiah' => 100001, 'line_count' => 0,
                'created_at' => '2026-09-15 10:00:00', 'updated_at' => null,
            ],
            [
                'id' => 'versioned-note-r002', 'note_root_id' => 'versioned-note', 'revision_number' => 2,
                'parent_revision_id' => 'versioned-note-r001', 'created_by_actor_id' => null, 'reason' => 'Future edit',
                'customer_name' => 'Current Customer', 'customer_phone' => null, 'transaction_date' => '2026-09-15',
                'grand_total_rupiah' => 130001, 'line_count' => 0,
                'created_at' => '2026-10-01 09:00:00', 'updated_at' => null,
            ],
        ]);
        DB::table('note_mutation_events')->insert([
            'id' => 'future-cancellation', 'note_id' => 'versioned-note', 'mutation_type' => 'note_cancelled',
            'actor_id' => 'admin', 'actor_role' => 'ADMIN', 'reason' => 'Future cancellation',
            'occurred_at' => '2026-10-01 10:00:00', 'related_customer_payment_id' => null,
            'related_customer_refund_id' => null,
        ]);

        $historical = app(GetTransactionReportDatasetHandler::class)->handle('2026-09-01', '2026-09-30')->data();
        self::assertCount(1, $historical['rows']);
        self::assertSame('Historical Customer', $historical['rows'][0]['customer_name']);
        self::assertSame(100001, $historical['rows'][0]['gross_transaction_rupiah']);
        self::assertSame(100001, $historical['rows'][0]['outstanding_rupiah']);

        $current = app(GetTransactionReportDatasetHandler::class)->handleCurrent('2026-09-01', '2026-09-30')->data();
        self::assertSame([], $current['rows']);
    }
}
