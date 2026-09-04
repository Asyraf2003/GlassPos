<?php

declare(strict_types=1);

namespace Tests\Feature\Payment;

use App\Core\Note\WorkItem\WorkItem;
use App\Ports\Out\Payment\NotePaymentTimelineReaderPort;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsMinimalNotePaymentFixture;
use Tests\TestCase;

final class DatabaseNotePaymentTimelineReaderAdapterFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalNotePaymentFixture;

    public function test_component_allocation_is_authoritative_and_legacy_duplicate_is_not_double_counted(): void
    {
        $today = date('Y-m-d');
        $this->seedNoteBase('timeline-reader-note', 'Reader Customer', $today, 100000);
        $this->seedWorkItemBase(
            'timeline-reader-work',
            'timeline-reader-note',
            1,
            WorkItem::TYPE_SERVICE_ONLY,
            WorkItem::STATUS_OPEN,
            100000,
        );

        DB::table('customer_payments')->insert([
            [
                'id' => 'timeline-payment-component',
                'amount_rupiah' => 100000,
                'payment_method' => 'cash',
                'paid_at' => $today,
                'recorded_at' => $today.' 10:00:00.100000',
                'created_at' => $today.' 10:00:00',
            ],
            [
                'id' => 'timeline-payment-legacy',
                'amount_rupiah' => 40000,
                'payment_method' => 'transfer',
                'paid_at' => $today,
                'recorded_at' => $today.' 10:00:00.200000',
                'created_at' => $today.' 10:00:00',
            ],
        ]);
        DB::table('customer_payment_cash_details')->insert([
            'customer_payment_id' => 'timeline-payment-component',
            'amount_paid_rupiah' => 100000,
            'amount_received_rupiah' => 120000,
            'change_rupiah' => 20000,
        ]);
        DB::table('payment_component_allocations')->insert([
            'id' => 'timeline-component-allocation',
            'customer_payment_id' => 'timeline-payment-component',
            'note_id' => 'timeline-reader-note',
            'work_item_id' => 'timeline-reader-work',
            'component_type' => 'service_fee',
            'component_ref_id' => 'timeline-reader-work',
            'component_amount_rupiah_snapshot' => 100000,
            'allocated_amount_rupiah' => 60000,
            'allocation_priority' => 20,
        ]);
        DB::table('payment_allocations')->insert([
            [
                'id' => 'timeline-duplicate-legacy-allocation',
                'customer_payment_id' => 'timeline-payment-component',
                'note_id' => 'timeline-reader-note',
                'amount_rupiah' => 100000,
            ],
            [
                'id' => 'timeline-pure-legacy-allocation',
                'customer_payment_id' => 'timeline-payment-legacy',
                'note_id' => 'timeline-reader-note',
                'amount_rupiah' => 40000,
            ],
        ]);

        $events = app(NotePaymentTimelineReaderPort::class)->findByNoteId('timeline-reader-note');

        self::assertSame(
            ['timeline-payment-component', 'timeline-payment-legacy'],
            array_column($events, 'payment_id'),
        );
        self::assertSame([60000, 40000], array_column($events, 'allocated_amount_rupiah'));
        self::assertSame([100000, 40000], array_column($events, 'payment_amount_rupiah'));
        self::assertSame(120000, $events[0]['amount_received_rupiah']);
        self::assertSame(20000, $events[0]['change_rupiah']);
        self::assertNull($events[1]['amount_received_rupiah']);
        self::assertNull($events[1]['change_rupiah']);
    }
}
