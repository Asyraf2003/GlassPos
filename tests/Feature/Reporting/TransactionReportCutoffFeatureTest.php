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
        $current = $reader->rows(null, null)[0];
        self::assertSame(100001, $current['allocated_payment_rupiah']);
        self::assertSame(0, $current['outstanding_rupiah']);
    }

}
