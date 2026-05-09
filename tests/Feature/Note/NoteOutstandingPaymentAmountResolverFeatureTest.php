<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Application\Note\Services\NoteOutstandingPaymentAmountResolver;
use App\Core\Note\WorkItem\ServiceDetail;
use App\Core\Note\WorkItem\WorkItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsMinimalNotePaymentFixture;
use Tests\TestCase;

final class NoteOutstandingPaymentAmountResolverFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalNotePaymentFixture;

    public function test_active_refund_reopens_outstanding_amount_for_normal_note(): void
    {
        $today = date('Y-m-d');

        $this->seedNoteBase('note-001-active-refund', 'Budi Refund Aktif', $today, 50000, 'closed');
        $this->seedCustomerPaymentBase('payment-001-active-refund', 50000, $today);
        $this->seedWorkItemBase(
            'wi-001-active-refund',
            'note-001-active-refund',
            1,
            WorkItem::TYPE_SERVICE_ONLY,
            WorkItem::STATUS_OPEN,
            50000,
        );

        $this->seedServiceDetailBase(
            'wi-001-active-refund',
            'Jasa #001 active refund',
            50000,
            ServiceDetail::PART_SOURCE_NONE,
        );

        DB::table('payment_component_allocations')->insert([
            'id' => 'pca-001-active-refund',
            'customer_payment_id' => 'payment-001-active-refund',
            'note_id' => 'note-001-active-refund',
            'work_item_id' => 'wi-001-active-refund',
            'component_type' => 'service_fee',
            'component_ref_id' => 'wi-001-active-refund',
            'component_amount_rupiah_snapshot' => 50000,
            'allocated_amount_rupiah' => 50000,
            'allocation_priority' => 1,
        ]);

        DB::table('customer_refunds')->insert([
            'id' => 'refund-001-active-refund',
            'customer_payment_id' => 'payment-001-active-refund',
            'note_id' => 'note-001-active-refund',
            'amount_rupiah' => 10000,
            'refunded_at' => $today,
            'reason' => 'Refund aktif untuk characterization #001',
        ]);

        DB::table('refund_component_allocations')->insert([
            'id' => 'rca-001-active-refund',
            'customer_refund_id' => 'refund-001-active-refund',
            'customer_payment_id' => 'payment-001-active-refund',
            'note_id' => 'note-001-active-refund',
            'work_item_id' => 'wi-001-active-refund',
            'component_type' => 'service_fee',
            'component_ref_id' => 'wi-001-active-refund',
            'refunded_amount_rupiah' => 10000,
            'refund_priority' => 1,
        ]);

        $result = app(NoteOutstandingPaymentAmountResolver::class)
            ->resolveFull('note-001-active-refund');

        self::assertTrue($result->isSuccess());
        self::assertSame(50000, $result->data()['grand_total_rupiah']);
        self::assertSame(40000, $result->data()['net_paid_rupiah']);
        self::assertSame(10000, $result->data()['outstanding_rupiah']);
        self::assertSame(10000, $result->data()['amount_rupiah']);
    }
}
