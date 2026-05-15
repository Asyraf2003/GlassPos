<?php

declare(strict_types=1);

namespace Tests\Feature\Payment;

use App\Application\Payment\UseCases\RecordAndAllocateNotePaymentHandler;
use App\Application\Shared\DTO\Result;
use App\Core\Note\WorkItem\ServiceDetail;
use App\Core\Note\WorkItem\WorkItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsMinimalNotePaymentFixture;
use Tests\TestCase;

final class RecordAndAllocateNotePaymentFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalNotePaymentFixture;

    public function test_it_allocates_to_all_product_buckets_before_any_service_fee(): void
    {
        $this->seedMixedNote();

        $result = app(RecordAndAllocateNotePaymentHandler::class)->handle('note-1', 10000, '2026-04-02');

        $this->assertInstanceOf(Result::class, $result);
        $this->assertTrue($result->isSuccess());
        $this->assertDatabaseCount('payment_component_allocations', 3);

        $paymentId = (string) DB::table('customer_payments')->value('id');

        $this->assertDatabaseHas('payment_component_allocations', [
            'customer_payment_id' => $paymentId,
            'note_id' => 'note-1',
            'component_type' => 'product_only_work_item',
            'component_ref_id' => 'wi-1',
            'allocated_amount_rupiah' => 5000,
        ]);

        $this->assertDatabaseHas('payment_component_allocations', [
            'customer_payment_id' => $paymentId,
            'note_id' => 'note-1',
            'component_type' => 'service_store_stock_part',
            'component_ref_id' => 'sto-2',
            'allocated_amount_rupiah' => 3000,
        ]);

        $this->assertDatabaseHas('payment_component_allocations', [
            'customer_payment_id' => $paymentId,
            'note_id' => 'note-1',
            'component_type' => 'service_external_purchase_part',
            'component_ref_id' => 'ext-1',
            'allocated_amount_rupiah' => 2000,
        ]);

        $this->assertDatabaseMissing('payment_component_allocations', [
            'customer_payment_id' => $paymentId,
            'component_type' => 'service_fee',
        ]);
    }


    public function test_record_and_allocate_note_payment_stores_operational_timestamps_on_component_allocations(): void
    {
        $this->seedMixedNote();

        $result = app(RecordAndAllocateNotePaymentHandler::class)->handle('note-1', 10000, '2026-04-02');

        $this->assertTrue($result->isSuccess());

        $paymentId = (string) DB::table('customer_payments')->value('id');

        $rows = DB::table('payment_component_allocations')
            ->where('customer_payment_id', $paymentId)
            ->where('note_id', 'note-1')
            ->get(['created_at', 'updated_at']);

        $this->assertCount(3, $rows);

        foreach ($rows as $row) {
            $this->assertNotNull($row->created_at);
            $this->assertNotNull($row->updated_at);
            $this->assertSame($row->created_at, $row->updated_at);
        }
    }

    public function test_it_moves_to_service_fee_only_after_product_buckets_are_fully_covered(): void
    {
        $this->seedMixedNote();
        $this->seedCustomerPaymentBase('payment-old', 10000, '2026-04-01');
        $this->seedComponentAllocation('alloc-old-1', 'payment-old', 'note-1', 'wi-1', 'product_only_work_item', 'wi-1', 5000, 5000, 1);
        $this->seedComponentAllocation('alloc-old-2', 'payment-old', 'note-1', 'wi-2', 'service_store_stock_part', 'sto-2', 3000, 3000, 2);
        $this->seedComponentAllocation('alloc-old-3', 'payment-old', 'note-1', 'wi-3', 'service_external_purchase_part', 'ext-1', 2000, 2000, 3);

        $result = app(RecordAndAllocateNotePaymentHandler::class)->handle('note-1', 4000, '2026-04-02');

        $this->assertTrue($result->isSuccess());

        $paymentId = (string) DB::table('customer_payments')
            ->where('id', '!=', 'payment-old')
            ->value('id');

        $this->assertDatabaseHas('payment_component_allocations', [
            'customer_payment_id' => $paymentId,
            'note_id' => 'note-1',
            'component_type' => 'service_fee',
            'component_ref_id' => 'wi-2',
            'allocated_amount_rupiah' => 4000,
        ]);
    }

    private function seedMixedNote(): void
    {
        $this->seedNotePaymentProduct('product-1', 'KB-001', 'Ban Luar', 'Federal', 100, 5000);
        $this->seedNotePaymentProduct('product-2', 'KB-002', 'Kampas Rem', 'Federal', 90, 3000);

        $this->seedNoteBase('note-1', 'Budi Santoso', '2026-04-02', 24000);

        $this->seedWorkItemBase('wi-1', 'note-1', 1, WorkItem::TYPE_STORE_STOCK_SALE_ONLY, WorkItem::STATUS_OPEN, 5000);
        $this->seedWorkItemBase('wi-2', 'note-1', 2, WorkItem::TYPE_SERVICE_WITH_STORE_STOCK_PART, WorkItem::STATUS_OPEN, 8000);
        $this->seedWorkItemBase('wi-3', 'note-1', 3, WorkItem::TYPE_SERVICE_WITH_EXTERNAL_PURCHASE, WorkItem::STATUS_OPEN, 11000);

        $this->seedServiceDetailBase('wi-2', 'Servis A', 5000, ServiceDetail::PART_SOURCE_NONE);
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

    private function seedComponentAllocation(
        string $id,
        string $paymentId,
        string $noteId,
        string $workItemId,
        string $componentType,
        string $componentRefId,
        int $snapshot,
        int $allocated,
        int $priority,
    ): void {
        DB::table('payment_component_allocations')->insert([
            'id' => $id,
            'customer_payment_id' => $paymentId,
            'note_id' => $noteId,
            'work_item_id' => $workItemId,
            'component_type' => $componentType,
            'component_ref_id' => $componentRefId,
            'component_amount_rupiah_snapshot' => $snapshot,
            'allocated_amount_rupiah' => $allocated,
            'allocation_priority' => $priority,
        ]);
    }
}
