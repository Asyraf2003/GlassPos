<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Adapters\Out\Reporting\Queries\TransactionCashLedgerReportingQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\SeedsMinimalNotePaymentFixture;
use Tests\TestCase;

final class CashLedgerHistoricalPaymentAmountFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalNotePaymentFixture;

    #[DataProvider('allocationSources')]
    public function test_capped_current_allocations_do_not_rewrite_historical_cash_events(string $source): void
    {
        // Isolate the gauntlet's post-revision state: two historical payments,
        // but only Rp150k allocated to the replacement obligation.
        $this->seedNoteBase('note-ledger', 'Historical cash', '2026-09-13', 150000, 'closed');
        $this->seedWorkItemBase('wi-ledger', 'note-ledger', 1, 'service_only', 'completed', 150000);

        foreach ([['cash', 300000, 0, '2026-09-11'], ['transfer', 580000, 150000, '2026-09-12']] as [$method, $paid, $allocated, $date]) {
            $paymentId = 'payment-'.$method;
            $this->seedCustomerPaymentBase($paymentId, $paid, $date);
            DB::table('customer_payments')->where('id', $paymentId)->update(['payment_method' => $method]);

            if ($source !== 'component') {
                $this->seedPaymentAllocationBase('legacy-'.$method, $paymentId, 'note-ledger', $allocated);
            }
            if ($source !== 'legacy') {
                DB::table('payment_component_allocations')->insert([
                    'id' => 'component-'.$method,
                    'customer_payment_id' => $paymentId,
                    'note_id' => 'note-ledger',
                    'work_item_id' => 'wi-ledger',
                    'component_type' => 'service_fee',
                    'component_ref_id' => 'wi-ledger',
                    'component_amount_rupiah_snapshot' => 150000,
                    'allocated_amount_rupiah' => $allocated,
                    'allocation_priority' => 1,
                ]);
            }
        }
        DB::table('customer_payment_cash_details')->insert([
            'customer_payment_id' => 'payment-cash',
            'amount_paid_rupiah' => 300000,
            'amount_received_rupiah' => 350000,
            'change_rupiah' => 50000,
        ]);

        $query = app(TransactionCashLedgerReportingQuery::class);
        $rows = $query->rows('2026-09-01', '2026-09-30');
        self::assertCount(2, $rows, 'Compatibility allocations must not duplicate a payment event.');
        self::assertSame(880000, $query->reconciliation('2026-09-01', '2026-09-30')['total_in_rupiah']);
        self::assertSame(300000, $rows[0]['event_amount_rupiah']);
        self::assertSame(350000, $rows[0]['cash_amount_received_rupiah']);
        self::assertSame(50000, $rows[0]['cash_change_rupiah']);
        self::assertSame('cash', $rows[0]['payment_method']);
        self::assertSame(580000, $rows[1]['event_amount_rupiah']);
        self::assertSame('transfer', $rows[1]['payment_method']);
        self::assertNull($rows[1]['cash_amount_received_rupiah']);
        self::assertSame(300000, $query->reconciliation('2026-09-11', '2026-09-11')['total_in_rupiah']);
        self::assertSame(580000, $query->reconciliation('2026-09-12', '2026-09-12')['total_in_rupiah']);
        self::assertSame(0, $query->reconciliation('2026-09-13', '2026-09-13')['total_in_rupiah']);
    }

    public static function allocationSources(): array
    {
        return ['legacy' => ['legacy'], 'component' => ['component'], 'both' => ['both']];
    }
}
