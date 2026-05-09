<?php

declare(strict_types=1);

namespace Tests\Feature\Payment;

use App\Adapters\Out\Payment\DatabasePaymentAllocationReaderAdapter;
use App\Core\Note\WorkItem\WorkItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsMinimalNotePaymentFixture;
use Tests\TestCase;

final class DatabasePaymentAllocationReaderAdapterFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalNotePaymentFixture;

    public function test_pair_total_includes_component_refunds_for_revised_note_component_flow(): void
    {
        $today = date('Y-m-d');

        $this->seedNoteBase('note-1', 'Budi', $today, 143000, 'open');
        $this->seedCustomerPaymentBase('payment-1', 265000, $today);
        $this->seedWorkItemBase(
            'wi-old-1',
            'note-1',
            1,
            WorkItem::TYPE_STORE_STOCK_SALE_ONLY,
            WorkItem::STATUS_CANCELED,
            122000,
        );
        $this->seedWorkItemBase(
            'wi-new-1',
            'note-1',
            1,
            WorkItem::TYPE_STORE_STOCK_SALE_ONLY,
            WorkItem::STATUS_OPEN,
            143000,
        );

        DB::table('payment_component_allocations')->insert([
            'id' => 'pca-active-1',
            'customer_payment_id' => 'payment-1',
            'note_id' => 'note-1',
            'work_item_id' => 'wi-new-1',
            'component_type' => 'product_only_work_item',
            'component_ref_id' => 'wi-new-1',
            'component_amount_rupiah_snapshot' => 143000,
            'allocated_amount_rupiah' => 143000,
            'allocation_priority' => 1,
        ]);

        DB::table('customer_refunds')->insert([
            'id' => 'refund-old-1',
            'customer_payment_id' => 'payment-1',
            'note_id' => 'note-1',
            'amount_rupiah' => 122000,
            'refunded_at' => $today,
            'reason' => 'Refund historical row before revision',
        ]);

        DB::table('refund_component_allocations')->insert([
            'id' => 'rca-old-1',
            'customer_refund_id' => 'refund-old-1',
            'customer_payment_id' => 'payment-1',
            'note_id' => 'note-1',
            'work_item_id' => 'wi-old-1',
            'component_type' => 'product_only_work_item',
            'component_ref_id' => 'wi-old-1',
            'refunded_amount_rupiah' => 122000,
            'refund_priority' => 1,
        ]);

        $reader = new DatabasePaymentAllocationReaderAdapter();

        self::assertSame(
            265000,
            $reader
                ->getTotalAllocatedAmountByCustomerPaymentIdAndNoteId('payment-1', 'note-1')
                ->amount(),
        );
    }
    public function test_pair_total_is_capped_by_payment_amount_after_full_refund(): void
    {
        $today = date('Y-m-d');

        $this->seedNoteBase('note-1', 'Budi', $today, 0, 'refunded');
        $this->seedCustomerPaymentBase('payment-1', 265000, $today);
        $this->seedWorkItemBase(
            'wi-old-1',
            'note-1',
            1,
            WorkItem::TYPE_STORE_STOCK_SALE_ONLY,
            WorkItem::STATUS_CANCELED,
            122000,
        );
        $this->seedWorkItemBase(
            'wi-new-1',
            'note-1',
            1,
            WorkItem::TYPE_STORE_STOCK_SALE_ONLY,
            WorkItem::STATUS_CANCELED,
            143000,
        );

        DB::table('payment_component_allocations')->insert([
            'id' => 'pca-active-1',
            'customer_payment_id' => 'payment-1',
            'note_id' => 'note-1',
            'work_item_id' => 'wi-new-1',
            'component_type' => 'product_only_work_item',
            'component_ref_id' => 'wi-new-1',
            'component_amount_rupiah_snapshot' => 143000,
            'allocated_amount_rupiah' => 143000,
            'allocation_priority' => 1,
        ]);

        DB::table('customer_refunds')->insert([
            [
                'id' => 'refund-old-1',
                'customer_payment_id' => 'payment-1',
                'note_id' => 'note-1',
                'amount_rupiah' => 122000,
                'refunded_at' => $today,
                'reason' => 'Refund historical row before revision',
            ],
            [
                'id' => 'refund-active-1',
                'customer_payment_id' => 'payment-1',
                'note_id' => 'note-1',
                'amount_rupiah' => 143000,
                'refunded_at' => $today,
                'reason' => 'Refund active row after revision',
            ],
        ]);

        DB::table('refund_component_allocations')->insert([
            [
                'id' => 'rca-old-1',
                'customer_refund_id' => 'refund-old-1',
                'customer_payment_id' => 'payment-1',
                'note_id' => 'note-1',
                'work_item_id' => 'wi-old-1',
                'component_type' => 'product_only_work_item',
                'component_ref_id' => 'wi-old-1',
                'refunded_amount_rupiah' => 122000,
                'refund_priority' => 1,
            ],
            [
                'id' => 'rca-active-1',
                'customer_refund_id' => 'refund-active-1',
                'customer_payment_id' => 'payment-1',
                'note_id' => 'note-1',
                'work_item_id' => 'wi-new-1',
                'component_type' => 'product_only_work_item',
                'component_ref_id' => 'wi-new-1',
                'refunded_amount_rupiah' => 143000,
                'refund_priority' => 1,
            ],
        ]);

        $reader = new DatabasePaymentAllocationReaderAdapter();

        self::assertSame(
            265000,
            $reader
                ->getTotalAllocatedAmountByCustomerPaymentIdAndNoteId('payment-1', 'note-1')
                ->amount(),
        );
    }


    public function test_note_level_allocated_total_excludes_active_component_refunds(): void
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

        $reader = new DatabasePaymentAllocationReaderAdapter();

        self::assertSame(
            50000,
            $reader
                ->getTotalAllocatedAmountByNoteId('note-001-active-refund')
                ->amount(),
        );
    }

}
