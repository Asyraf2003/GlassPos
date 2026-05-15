<?php

declare(strict_types=1);

namespace Tests\Feature\Payment;

use App\Application\Payment\UseCases\RecordCustomerRefundHandler;
use App\Ports\Out\Note\NoteReaderPort;
use App\Core\Note\Note\Note;
use App\Adapters\Out\Note\DatabaseNoteReaderAdapter;
use App\Core\Note\WorkItem\ServiceDetail;
use App\Core\Note\WorkItem\WorkItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsMinimalNotePaymentFixture;
use Tests\TestCase;

final class RecordSelectedRowsCustomerRefundFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalNotePaymentFixture;

    public function test_it_records_refund_only_for_selected_rows(): void
    {
        $this->seedNote();
        $this->seedPaymentAndAllocations();

        $result = app(RecordCustomerRefundHandler::class)->handle(
            'payment-1',
            'note-1',
            4000,
            '2026-04-03',
            'Refund line terpilih',
            'actor-1',
            ['wi-2'],
        );

        $this->assertTrue($result->isSuccess());

        $refundId = (string) DB::table('customer_refunds')->value('id');

        $this->assertDatabaseHas('refund_component_allocations', [
            'customer_refund_id' => $refundId,
            'customer_payment_id' => 'payment-1',
            'note_id' => 'note-1',
            'work_item_id' => 'wi-2',
            'component_type' => 'service_fee',
            'component_ref_id' => 'wi-2',
            'refunded_amount_rupiah' => 4000,
        ]);

        $this->assertDatabaseMissing('refund_component_allocations', [
            'customer_refund_id' => $refundId,
            'work_item_id' => 'wi-1',
        ]);

        $this->assertDatabaseMissing('refund_component_allocations', [
            'customer_refund_id' => $refundId,
            'work_item_id' => 'wi-3',
        ]);
    }

    public function test_it_records_external_purchase_refund_only_for_selected_external_row(): void
    {
        $this->seedNote();
        $this->seedPaymentAndAllocations();

        $result = app(RecordCustomerRefundHandler::class)->handle(
            'payment-1',
            'note-1',
            2000,
            '2026-04-03',
            'Refund komponen external line terpilih',
            'actor-1',
            ['wi-3'],
        );

        $this->assertTrue($result->isSuccess());

        $refundId = (string) DB::table('customer_refunds')->value('id');

        $this->assertDatabaseHas('refund_component_allocations', [
            'customer_refund_id' => $refundId,
            'customer_payment_id' => 'payment-1',
            'note_id' => 'note-1',
            'work_item_id' => 'wi-3',
            'component_type' => 'service_external_purchase_part',
            'component_ref_id' => 'ext-1',
            'refunded_amount_rupiah' => 2000,
        ]);

        $this->assertDatabaseMissing('refund_component_allocations', [
            'customer_refund_id' => $refundId,
            'work_item_id' => 'wi-1',
        ]);

        $this->assertDatabaseMissing('refund_component_allocations', [
            'customer_refund_id' => $refundId,
            'work_item_id' => 'wi-2',
        ]);

        $this->assertDatabaseMissing('refund_component_allocations', [
            'customer_refund_id' => $refundId,
            'component_type' => 'product_only_work_item',
        ]);

        $this->assertDatabaseMissing('refund_component_allocations', [
            'customer_refund_id' => $refundId,
            'component_type' => 'service_store_stock_part',
        ]);

        $this->assertSame(
            1,
            DB::table('refund_component_allocations')
                ->where('customer_refund_id', $refundId)
                ->count()
        );
    }


    public function test_selected_rows_refund_stores_operational_timestamps_on_refund_component_allocations(): void
    {
        $this->seedNote();
        $this->seedPaymentAndAllocations();

        $result = app(RecordCustomerRefundHandler::class)->handle(
            'payment-1',
            'note-1',
            4000,
            '2026-04-03',
            'Refund timestamp allocation',
            'actor-1',
            ['wi-2'],
        );

        $this->assertTrue($result->isSuccess());

        $refundId = (string) DB::table('customer_refunds')->value('id');

        $rows = DB::table('refund_component_allocations')
            ->where('customer_refund_id', $refundId)
            ->get(['created_at', 'updated_at']);

        $this->assertCount(1, $rows);

        foreach ($rows as $row) {
            $this->assertNotNull($row->created_at);
            $this->assertNotNull($row->updated_at);
            $this->assertSame($row->created_at, $row->updated_at);
        }
    }


    public function test_selected_row_refund_locks_note_before_refund_allocation_reads(): void
    {
        $this->seedNote();
        $this->seedPaymentAndAllocations();

        $spy = new class ($this->app->make(DatabaseNoteReaderAdapter::class)) implements NoteReaderPort {
            public int $forUpdateCalls = 0;

            public function __construct(private readonly NoteReaderPort $delegate)
            {
            }

            public function countAll(): int
            {
                return $this->delegate->countAll();
            }

            public function getById(string $id): ?Note
            {
                return $this->delegate->getById($id);
            }

            public function getByIdForUpdate(string $id): ?Note
            {
                $this->forUpdateCalls++;

                return $this->delegate->getByIdForUpdate($id);
            }
        };

        $this->app->instance(NoteReaderPort::class, $spy);

        $result = app(RecordCustomerRefundHandler::class)->handle(
            'payment-1',
            'note-1',
            4000,
            '2026-04-03',
            'Refund selected-row lock invariant',
            'actor-1',
            ['wi-2'],
        );

        $this->assertTrue($result->isSuccess());
        $this->assertGreaterThan(
            0,
            $spy->forUpdateCalls,
            'Selected-row customer refund must lock the note before refund allocation reads to serialize concurrent refunds.',
        );
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
