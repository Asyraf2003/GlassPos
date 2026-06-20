<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Application\Note\UseCases\CorrectPaidServiceWithStoreStockPartServiceFeeOnlyHandler;
use App\Core\Note\WorkItem\ServiceDetail;
use App\Core\Note\WorkItem\WorkItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsMinimalNotePaymentFixture;
use Tests\TestCase;

final class CorrectPaidServiceWithStoreStockPartServiceFeeOnlyFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalNotePaymentFixture;

    public function test_package_correction_rejects_service_price_below_package_base_floor_and_keeps_rows_unchanged(): void
    {
        $this->seedNotePaymentProduct('product-floor-guard', 'FLR-001', 'Kampas Rem', 'Federal', 100, 50000);

        $this->seedNoteBase('note-floor-guard', 'Budi Floor Guard', '2026-06-20', 150000);
        $this->seedWorkItemBase('wi-floor-guard', 'note-floor-guard', 1, WorkItem::TYPE_SERVICE_WITH_STORE_STOCK_PART, WorkItem::STATUS_OPEN, 150000);
        $this->seedServiceDetailBase('wi-floor-guard', 'Paket Servis Floor Original', 100000, ServiceDetail::PART_SOURCE_NONE);

        DB::table('work_item_service_details')->where('work_item_id', 'wi-floor-guard')->update([
            'package_profit_rupiah' => 30000,
            'package_base_service_price_rupiah' => 80000,
            'package_service_extra_rupiah' => 20000,
        ]);

        $this->seedStoreStockLineBase('sto-floor-guard', 'wi-floor-guard', 'product-floor-guard', 1, 50000);
        $this->seedCustomerPaymentBase('payment-floor-guard', 150000, '2026-06-20');

        DB::table('payment_component_allocations')->insert([
            [
                'id' => 'pca-floor-guard-product',
                'customer_payment_id' => 'payment-floor-guard',
                'note_id' => 'note-floor-guard',
                'work_item_id' => 'wi-floor-guard',
                'component_type' => 'service_store_stock_part',
                'component_ref_id' => 'sto-floor-guard',
                'component_amount_rupiah_snapshot' => 50000,
                'allocated_amount_rupiah' => 50000,
                'allocation_priority' => 1,
            ],
            [
                'id' => 'pca-floor-guard-service',
                'customer_payment_id' => 'payment-floor-guard',
                'note_id' => 'note-floor-guard',
                'work_item_id' => 'wi-floor-guard',
                'component_type' => 'service_fee',
                'component_ref_id' => 'wi-floor-guard',
                'component_amount_rupiah_snapshot' => 100000,
                'allocated_amount_rupiah' => 100000,
                'allocation_priority' => 2,
            ],
        ]);

        $result = app(CorrectPaidServiceWithStoreStockPartServiceFeeOnlyHandler::class)->handle(
            'note-floor-guard',
            1,
            'Paket Servis Floor Below Base',
            60000,
            ServiceDetail::PART_SOURCE_NONE,
            'Phase 2 floor guard rejects below base service price.',
            'actor-floor-guard',
        );

        self::assertFalse($result->isSuccess(), $result->message());

        $this->assertDatabaseHas('notes', [
            'id' => 'note-floor-guard',
            'total_rupiah' => 150000,
        ]);

        $this->assertDatabaseHas('work_items', [
            'id' => 'wi-floor-guard',
            'subtotal_rupiah' => 150000,
        ]);

        $this->assertDatabaseHas('work_item_service_details', [
            'work_item_id' => 'wi-floor-guard',
            'service_name' => 'Paket Servis Floor Original',
            'service_price_rupiah' => 100000,
            'package_profit_rupiah' => 30000,
            'package_base_service_price_rupiah' => 80000,
            'package_service_extra_rupiah' => 20000,
        ]);

        $this->assertDatabaseHas('work_item_store_stock_lines', [
            'id' => 'sto-floor-guard',
            'work_item_id' => 'wi-floor-guard',
            'product_id' => 'product-floor-guard',
            'qty' => 1,
            'line_total_rupiah' => 50000,
        ]);
    }

    public function test_it_corrects_service_fee_only_and_keeps_store_stock_lines_intact(): void
    {
        $this->seedNotePaymentProduct('product-1', 'KB-001', 'Ban Luar', 'Federal', 100, 3000);

        $this->seedNoteBase('note-1', 'Budi', '2026-04-02', 8000);
        $this->seedWorkItemBase('wi-1', 'note-1', 1, WorkItem::TYPE_SERVICE_WITH_STORE_STOCK_PART, WorkItem::STATUS_OPEN, 8000);
        $this->seedServiceDetailBase('wi-1', 'Servis Lama', 5000, ServiceDetail::PART_SOURCE_NONE);
        $this->seedStoreStockLineBase('sto-1', 'wi-1', 'product-1', 1, 3000);
        $this->seedCustomerPaymentBase('pay-1', 8000, '2026-04-02');

        DB::table('payment_component_allocations')->insert([
            [
                'id' => 'pca-1',
                'customer_payment_id' => 'pay-1',
                'note_id' => 'note-1',
                'work_item_id' => 'wi-1',
                'component_type' => 'service_store_stock_part',
                'component_ref_id' => 'sto-1',
                'component_amount_rupiah_snapshot' => 3000,
                'allocated_amount_rupiah' => 3000,
                'allocation_priority' => 1,
            ],
            [
                'id' => 'pca-2',
                'customer_payment_id' => 'pay-1',
                'note_id' => 'note-1',
                'work_item_id' => 'wi-1',
                'component_type' => 'service_fee',
                'component_ref_id' => 'wi-1',
                'component_amount_rupiah_snapshot' => 5000,
                'allocated_amount_rupiah' => 5000,
                'allocation_priority' => 2,
            ],
        ]);

        $result = app(CorrectPaidServiceWithStoreStockPartServiceFeeOnlyHandler::class)->handle(
            'note-1',
            1,
            'Servis Baru',
            4000,
            ServiceDetail::PART_SOURCE_NONE,
            'Koreksi fee jasa',
            'actor-1',
        );

        $this->assertTrue($result->isSuccess());
        $this->assertSame(1000, $result->data()['refund_required_rupiah']);

        $this->assertDatabaseHas('notes', [
            'id' => 'note-1',
            'total_rupiah' => 7000,
        ]);

        $this->assertDatabaseHas('work_items', [
            'id' => 'wi-1',
            'subtotal_rupiah' => 7000,
        ]);

        $this->assertDatabaseHas('work_item_service_details', [
            'work_item_id' => 'wi-1',
            'service_name' => 'Servis Baru',
            'service_price_rupiah' => 4000,
            'part_source' => ServiceDetail::PART_SOURCE_NONE,
        ]);

        $this->assertDatabaseHas('work_item_store_stock_lines', [
            'id' => 'sto-1',
            'work_item_id' => 'wi-1',
            'product_id' => 'product-1',
            'qty' => 1,
            'line_total_rupiah' => 3000,
        ]);

        $this->assertDatabaseHas('note_mutation_events', [
            'note_id' => 'note-1',
            'mutation_type' => 'paid_service_with_store_stock_part_service_fee_only_corrected',
            'actor_id' => 'actor-1',
            'reason' => 'Koreksi fee jasa',
        ]);
    }
}
