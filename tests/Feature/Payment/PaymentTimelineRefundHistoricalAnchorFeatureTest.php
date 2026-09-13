<?php

declare(strict_types=1);

namespace Tests\Feature\Payment;

use App\Adapters\Out\Payment\DatabaseNotePaymentTimelineReaderAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsMinimalNotePaymentFixture;
use Tests\TestCase;

final class PaymentTimelineRefundHistoricalAnchorFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalNotePaymentFixture;

    public function test_timeline_keeps_refunded_payment_without_current_allocation_and_preserves_cash_detail(): void
    {
        $this->seedNoteBase('timeline-root', 'Historical payments', '2026-09-13', 80000, 'closed');
        $this->seedCustomerPaymentBase('historical-cash', 20000, '2026-09-11');
        $this->seedCustomerPaymentBase('current-transfer', 80000, '2026-09-12');
        $this->seedCustomerPaymentBase('unrelated-payment', 900000, '2026-09-12');
        DB::table('customer_payments')->where('id', 'historical-cash')->update(['payment_method' => 'cash']);
        DB::table('customer_payments')->where('id', 'current-transfer')->update(['payment_method' => 'transfer']);
        $this->seedPaymentAllocationBase('current-allocation', 'current-transfer', 'timeline-root', 80000);
        DB::table('customer_payment_cash_details')->insert([
            'customer_payment_id' => 'historical-cash',
            'amount_paid_rupiah' => 20000, 'amount_received_rupiah' => 100000, 'change_rupiah' => 80000,
        ]);
        foreach (['refund-a', 'refund-b'] as $id) {
            DB::table('customer_refunds')->insert([
                'id' => $id, 'customer_payment_id' => 'historical-cash', 'note_id' => 'timeline-root',
                'amount_rupiah' => 10000, 'refunded_at' => '2026-09-12', 'reason' => 'Component refunded before revision',
            ]);
        }

        $timeline = app(DatabaseNotePaymentTimelineReaderAdapter::class)->findByNoteId('timeline-root');
        self::assertCount(2, $timeline);
        $byId = array_column($timeline, null, 'payment_id');
        self::assertSame(20000, $byId['historical-cash']['payment_amount_rupiah']);
        self::assertSame(100000, $byId['historical-cash']['amount_received_rupiah']);
        self::assertSame(80000, $byId['historical-cash']['change_rupiah']);
        self::assertSame(0, $byId['historical-cash']['allocated_amount_rupiah']);
        self::assertSame(80000, $byId['current-transfer']['payment_amount_rupiah']);
        self::assertNull($byId['current-transfer']['amount_received_rupiah']);
        self::assertSame(1, DB::table('payment_allocations')->count());
    }
}
