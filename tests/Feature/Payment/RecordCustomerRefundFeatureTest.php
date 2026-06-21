<?php

declare(strict_types=1);

namespace Tests\Feature\Payment;

use App\Application\Payment\UseCases\RecordCustomerRefundHandler;
use App\Core\Note\WorkItem\ServiceDetail;
use App\Core\Note\WorkItem\WorkItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsMinimalNotePaymentFixture;
use Tests\TestCase;

final class RecordCustomerRefundFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalNotePaymentFixture;

    public function test_it_records_default_refundable_component_allocations_only(): void
    {
        $this->seedNote();
        $this->seedPaymentAndAllocations();

        $result = app(RecordCustomerRefundHandler::class)->handle(
            'payment-1',
            'note-1',
            4000,
            '2026-04-03',
            'Refund komponen default refundable',
            'actor-1',
        );

        $this->assertTrue($result->isSuccess());
        $refundId = (string) DB::table('customer_refunds')->value('id');

        $this->assertDatabaseHas('refund_component_allocations', [
            'customer_refund_id' => $refundId,
            'customer_payment_id' => 'payment-1',
            'note_id' => 'note-1',
            'component_type' => 'service_store_stock_part',
            'component_ref_id' => 'sto-2',
            'refunded_amount_rupiah' => 3000,
        ]);

        $this->assertDatabaseHas('refund_component_allocations', [
            'customer_refund_id' => $refundId,
            'component_type' => 'product_only_work_item',
            'component_ref_id' => 'wi-1',
            'refunded_amount_rupiah' => 1000,
        ]);

        $this->assertDatabaseMissing('refund_component_allocations', [
            'customer_refund_id' => $refundId,
            'component_type' => 'service_fee',
        ]);
    }

    public function test_it_rejects_refund_that_exceeds_remaining_pair_allocation(): void
    {
        $this->seedNote();
        $this->seedPaymentAndAllocations();

        $first = app(RecordCustomerRefundHandler::class)->handle(
            'payment-1',
            'note-1',
            4000,
            '2026-04-03',
            'Refund jasa',
            'actor-1',
        );

        $this->assertTrue($first->isSuccess());

        $second = app(RecordCustomerRefundHandler::class)->handle(
            'payment-1',
            'note-1',
            11000,
            '2026-04-03',
            'Refund berlebih',
            'actor-1',
        );

        $this->assertFalse($second->isSuccess());
        $this->assertDatabaseCount('customer_refunds', 1);
    }

    public function test_generic_refund_reverses_fully_refunded_store_stock_component(): void
    {
        $this->seedNote();
        $this->seedPaymentAndAllocations();

        DB::table('product_inventory')->insert([
            'product_id' => 'product-2',
            'qty_on_hand' => 9,
        ]);

        DB::table('product_inventory_costing')->insert([
            'product_id' => 'product-2',
            'avg_cost_rupiah' => 3000,
            'inventory_value_rupiah' => 27000,
        ]);

        DB::table('inventory_movements')->insert([
            'id' => 'move-sto-2',
            'product_id' => 'product-2',
            'movement_type' => 'stock_out',
            'source_type' => 'work_item_store_stock_line',
            'source_id' => 'sto-2',
            'tanggal_mutasi' => '2026-04-02',
            'qty_delta' => -1,
            'unit_cost_rupiah' => 3000,
            'total_cost_rupiah' => -3000,
        ]);

        $result = app(RecordCustomerRefundHandler::class)->handle(
            'payment-1',
            'note-1',
            7000,
            '2026-04-03',
            'Refund sebagian part stok toko',
            'actor-1',
        );

        $this->assertTrue($result->isSuccess());
        $refundId = (string) DB::table('customer_refunds')->value('id');

        $this->assertDatabaseHas('refund_component_allocations', [
            'customer_refund_id' => $refundId,
            'component_type' => 'service_store_stock_part',
            'component_ref_id' => 'sto-2',
            'refunded_amount_rupiah' => 3000,
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'source_type' => 'work_item_store_stock_line_reversal',
            'source_id' => 'sto-2',
            'qty_delta' => 1,
            'unit_cost_rupiah' => 3000,
            'total_cost_rupiah' => 3000,
        ]);

        $this->assertDatabaseHas('product_inventory', [
            'product_id' => 'product-2',
            'qty_on_hand' => 10,
        ]);
    }


    public function test_it_stores_operational_timestamps_on_new_refund(): void
    {
        $this->seedNote();
        $this->seedPaymentAndAllocations();

        $result = app(RecordCustomerRefundHandler::class)->handle(
            'payment-1',
            'note-1',
            4000,
            '2026-04-03',
            'Refund jasa timestamp',
            'actor-1',
        );

        $this->assertTrue($result->isSuccess());

        $row = DB::table('customer_refunds')
            ->where('customer_payment_id', 'payment-1')
            ->where('note_id', 'note-1')
            ->first(['created_at', 'updated_at']);

        $this->assertNotNull($row);
        $this->assertNotNull($row->created_at);
        $this->assertNotNull($row->updated_at);
        $this->assertSame($row->created_at, $row->updated_at);
    }

    private function seedNote(): void
    {
        $this->seedNotePaymentProduct('product-1', 'KB-001', 'Ban Luar', 'Federal', 100, 5000);
        $this->seedNotePaymentProduct('product-2', 'KB-002', 'Kampas Rem', 'Federal', 90, 3000);

        $this->seedNoteBase('note-1', 'Budi', '2026-04-02', 23000);

        $this->seedWorkItemBase('wi-1', 'note-1', 1, WorkItem::TYPE_STORE_STOCK_SALE_ONLY, WorkItem::STATUS_OPEN, 5000);
        $this->seedWorkItemBase('wi-2', 'note-1', 2, WorkItem::TYPE_SERVICE_WITH_STORE_STOCK_PART, WorkItem::STATUS_OPEN, 7000);
        $this->seedWorkItemBase('wi-3', 'note-1', 3, WorkItem::TYPE_SERVICE_WITH_EXTERNAL_PURCHASE, WorkItem::STATUS_OPEN, 11000);

        $this->seedServiceDetailBase('wi-2', 'Servis A', 4000, ServiceDetail::PART_SOURCE_NONE);
        $this->seedServiceDetailBase('wi-3', 'Servis B', 9000, ServiceDetail::PART_SOURCE_NONE);

        $this->seedStoreStockLineBase('sto-1', 'wi-1', 'product-1', 1, 5000);
        $this->seedStoreStockLineBase('sto-2', 'wi-2', 'product-2', 1, 3000);

        DB::table('work_item_external_purchase_lines')->insert([
            'id' => 'ext-1',
            'work_item_id' => 'wi-3',
            'cost_description' => 'Beli luar',
            'unit_cost_rupiah' => 2000,
            'qty' => 1,
            'line_total_rupiah' => 2000,
        ]);
    }

    private function seedPaymentAndAllocations(): void
    {
        $this->seedCustomerPaymentBase('payment-1', 14000, '2026-04-02');

        DB::table('payment_component_allocations')->insert([
            [
                'id' => 'pa-1',
                'customer_payment_id' => 'payment-1',
                'note_id' => 'note-1',
                'work_item_id' => 'wi-1',
                'component_type' => 'product_only_work_item',
                'component_ref_id' => 'wi-1',
                'component_amount_rupiah_snapshot' => 5000,
                'allocated_amount_rupiah' => 5000,
                'allocation_priority' => 1,
            ],
            [
                'id' => 'pa-2',
                'customer_payment_id' => 'payment-1',
                'note_id' => 'note-1',
                'work_item_id' => 'wi-2',
                'component_type' => 'service_store_stock_part',
                'component_ref_id' => 'sto-2',
                'component_amount_rupiah_snapshot' => 3000,
                'allocated_amount_rupiah' => 3000,
                'allocation_priority' => 2,
            ],
            [
                'id' => 'pa-3',
                'customer_payment_id' => 'payment-1',
                'note_id' => 'note-1',
                'work_item_id' => 'wi-3',
                'component_type' => 'service_external_purchase_part',
                'component_ref_id' => 'ext-1',
                'component_amount_rupiah_snapshot' => 2000,
                'allocated_amount_rupiah' => 2000,
                'allocation_priority' => 3,
            ],
            [
                'id' => 'pa-4',
                'customer_payment_id' => 'payment-1',
                'note_id' => 'note-1',
                'work_item_id' => 'wi-2',
                'component_type' => 'service_fee',
                'component_ref_id' => 'wi-2',
                'component_amount_rupiah_snapshot' => 4000,
                'allocated_amount_rupiah' => 4000,
                'allocation_priority' => 4,
            ],
        ]);
    }
}
