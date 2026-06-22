<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Core\Note\WorkItem\ServiceDetail;
use App\Core\Note\WorkItem\WorkItem;
use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsMinimalNotePaymentFixture;
use Tests\TestCase;

final class RecordClosedNoteRefundControllerFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalNotePaymentFixture;

    public function test_cashier_can_record_refund_for_closed_note(): void
    {
        $user = $this->seedKasir();
        $this->seedClosedPaidProductOnlyNote();

        $this->actingAs($user)
            ->from(route('cashier.notes.index'))
            ->post(route('cashier.notes.refunds.store', ['noteId' => 'note-1']), [
                'selected_row_ids' => ['wi-1'],
                'refunded_at' => date('Y-m-d'),
                'reason' => 'Koreksi line produk',
            ])
            ->assertRedirect(route('cashier.notes.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('customer_refunds', [
            'customer_payment_id' => 'payment-1',
            'note_id' => 'note-1',
            'amount_rupiah' => 50000,
            'reason' => 'Koreksi line produk',
        ]);

        $refundId = (string) DB::table('customer_refunds')->value('id');

        $this->assertDatabaseHas('refund_component_allocations', [
            'customer_refund_id' => $refundId,
            'customer_payment_id' => 'payment-1',
            'note_id' => 'note-1',
            'component_type' => 'product_only_work_item',
            'component_ref_id' => 'wi-1',
            'refunded_amount_rupiah' => 50000,
        ]);

        $this->assertDatabaseHas('work_items', [
            'id' => 'wi-1',
            'status' => WorkItem::STATUS_CANCELED,
        ]);

        $this->assertDatabaseHas('notes', ['id' => 'note-1', 'total_rupiah' => 0]);
    }


    public function test_cashier_cannot_record_refund_for_historical_note_outside_cashier_access_window(): void
    {
        $user = $this->seedKasir();
        $this->seedClosedPaidServiceOnlyNote(date('Y-m-d', strtotime('-3 days')));

        $this->actingAs($user)
            ->from(route('cashier.notes.index'))
            ->post(route('cashier.notes.refunds.store', ['noteId' => 'note-1']), [
                'selected_row_ids' => ['wi-1'],
                'refunded_at' => date('Y-m-d'),
                'reason' => 'Unauthorized historical refund',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('customer_refunds', 0);

        $this->assertDatabaseHas('work_items', [
            'id' => 'wi-1',
            'status' => WorkItem::STATUS_OPEN,
        ]);

        $this->assertDatabaseHas('notes', [
            'id' => 'note-1',
            'total_rupiah' => 50000,
        ]);
    }


    public function test_cashier_cannot_record_refund_for_open_partially_paid_row(): void
    {
        $user = $this->seedKasir();
        $this->seedOpenPartialPaidServiceOnlyNote();

        $this->actingAs($user)
            ->from(route('cashier.notes.index'))
            ->post(route('cashier.notes.refunds.store', ['noteId' => 'note-1']), [
                'selected_row_ids' => ['wi-1'],
                'refunded_at' => date('Y-m-d'),
                'reason' => 'Batalkan line open',
            ])
            ->assertRedirect(route('cashier.notes.index'))
            ->assertSessionHasErrors(['refund']);

        $this->assertDatabaseCount('customer_refunds', 0);
        $this->assertDatabaseCount('refund_component_allocations', 0);

        $this->assertDatabaseHas('work_items', [
            'id' => 'wi-1',
            'status' => WorkItem::STATUS_OPEN,
        ]);

        $this->assertDatabaseHas('notes', [
            'id' => 'note-1',
            'note_state' => 'open',
            'total_rupiah' => 50000,
        ]);

        $this->assertDatabaseMissing('note_mutation_events', [
            'note_id' => 'note-1',
            'mutation_type' => 'note_rows_canceled_via_refund',
        ]);
    }

    public function test_refund_request_requires_reason(): void
    {
        $user = $this->seedKasir();
        $this->seedClosedPaidServiceOnlyNote();

        $this->actingAs($user)
            ->from(route('cashier.notes.index'))
            ->post(route('cashier.notes.refunds.store', ['noteId' => 'note-1']), [
                'selected_row_ids' => ['wi-1'],
                'refunded_at' => date('Y-m-d'),
                'reason' => '',
            ])
            ->assertRedirect(route('cashier.notes.index'))
            ->assertSessionHasErrors(['reason']);

        $this->assertDatabaseCount('customer_refunds', 0);
    }

    public function test_refund_allocates_only_selected_rows(): void
    {
        $user = $this->seedKasir();
        $this->seedClosedPaidTwoLineNote();

        $this->actingAs($user)
            ->from(route('cashier.notes.index'))
            ->post(route('cashier.notes.refunds.store', ['noteId' => 'note-2']), [
                'selected_row_ids' => ['wi-2'],
                'refunded_at' => date('Y-m-d'),
                'reason' => 'Refund line kedua saja',
            ])
            ->assertRedirect(route('cashier.notes.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('customer_refunds', [
            'customer_payment_id' => 'payment-2',
            'note_id' => 'note-2',
            'amount_rupiah' => 30000,
            'reason' => 'Refund line kedua saja',
        ]);

        $refundId = (string) DB::table('customer_refunds')
            ->where('customer_payment_id', 'payment-2')
            ->where('note_id', 'note-2')
            ->value('id');

        $this->assertDatabaseHas('refund_component_allocations', [
            'customer_refund_id' => $refundId,
            'customer_payment_id' => 'payment-2',
            'note_id' => 'note-2',
            'component_type' => 'product_only_work_item',
            'component_ref_id' => 'wi-2',
            'refunded_amount_rupiah' => 30000,
        ]);

        $this->assertDatabaseMissing('refund_component_allocations', [
            'customer_refund_id' => $refundId,
            'customer_payment_id' => 'payment-2',
            'note_id' => 'note-2',
            'component_ref_id' => 'wi-1',
        ]);

        $this->assertDatabaseHas('work_items', [
            'id' => 'wi-2',
            'status' => WorkItem::STATUS_CANCELED,
        ]);

        $this->assertDatabaseHas('work_items', [
            'id' => 'wi-1',
            'status' => WorkItem::STATUS_OPEN,
        ]);

        $this->assertDatabaseHas('notes', [
            'id' => 'note-2',
            'total_rupiah' => 50000,
        ]);
    }


    public function test_cashier_can_refund_legacy_paid_product_only_note_without_component_allocations(): void
    {
        $user = $this->seedKasir();
        $today = date('Y-m-d');

        $this->seedProductForRefund('product-legacy-refund-1', 50000, 30000, 1);
        $this->seedNoteBase('note-legacy-refund-1', 'Budi Legacy Refund', $today, 50000, 'closed');
        $this->seedWorkItemBase(
            'wi-legacy-refund-1',
            'note-legacy-refund-1',
            1,
            WorkItem::TYPE_STORE_STOCK_SALE_ONLY,
            WorkItem::STATUS_OPEN,
            50000
        );
        $this->seedStoreStockLineBase(
            'ssl-legacy-refund-1',
            'wi-legacy-refund-1',
            'product-legacy-refund-1',
            1,
            50000
        );
        $this->seedStockOut(
            'move-legacy-refund-1',
            'product-legacy-refund-1',
            'ssl-legacy-refund-1',
            $today,
            30000
        );

        $this->seedCustomerPaymentBase('payment-legacy-refund-1', 50000, $today);
        $this->seedPaymentAllocationBase(
            'allocation-legacy-refund-1',
            'payment-legacy-refund-1',
            'note-legacy-refund-1',
            50000
        );

        $this->assertSame(0, DB::table('payment_component_allocations')->count());

        $this->actingAs($user)
            ->from(route('cashier.notes.index'))
            ->post(route('cashier.notes.refunds.store', ['noteId' => 'note-legacy-refund-1']), [
                'selected_row_ids' => ['wi-legacy-refund-1'],
                'refunded_at' => $today,
                'reason' => 'Refund legacy paid product line',
            ])
            ->assertRedirect(route('cashier.notes.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('customer_refunds', [
            'customer_payment_id' => 'payment-legacy-refund-1',
            'note_id' => 'note-legacy-refund-1',
            'amount_rupiah' => 50000,
            'reason' => 'Refund legacy paid product line',
        ]);

        $refundId = (string) DB::table('customer_refunds')
            ->where('customer_payment_id', 'payment-legacy-refund-1')
            ->where('note_id', 'note-legacy-refund-1')
            ->value('id');

        $this->assertNotSame('', $refundId);

        $this->assertDatabaseHas('refund_component_allocations', [
            'customer_refund_id' => $refundId,
            'customer_payment_id' => 'payment-legacy-refund-1',
            'note_id' => 'note-legacy-refund-1',
            'work_item_id' => 'wi-legacy-refund-1',
            'component_type' => 'product_only_work_item',
            'component_ref_id' => 'wi-legacy-refund-1',
            'refunded_amount_rupiah' => 50000,
        ]);

        $this->assertDatabaseHas('work_items', [
            'id' => 'wi-legacy-refund-1',
            'status' => WorkItem::STATUS_CANCELED,
        ]);

        $this->assertDatabaseHas('notes', [
            'id' => 'note-legacy-refund-1',
            'total_rupiah' => 0,
        ]);
    }



    public function test_legacy_paid_service_only_refund_is_rejected_without_mutating_note(): void
    {
        $user = $this->seedKasir();
        $today = date('Y-m-d');

        $this->seedNoteBase('note-legacy-service-refund-1', 'Budi Legacy Service Refund', $today, 50000, 'closed');
        $this->seedWorkItemBase(
            'wi-legacy-service-refund-1',
            'note-legacy-service-refund-1',
            1,
            WorkItem::TYPE_SERVICE_ONLY,
            WorkItem::STATUS_OPEN,
            50000
        );
        $this->seedServiceDetailBase(
            'wi-legacy-service-refund-1',
            'Servis Legacy Tidak Refundable',
            50000,
            ServiceDetail::PART_SOURCE_NONE
        );

        $this->seedCustomerPaymentBase('payment-legacy-service-refund-1', 50000, $today);
        $this->seedPaymentAllocationBase(
            'allocation-legacy-service-refund-1',
            'payment-legacy-service-refund-1',
            'note-legacy-service-refund-1',
            50000
        );

        $this->assertSame(0, DB::table('payment_component_allocations')->count());

        $this->actingAs($user)
            ->from(route('cashier.notes.index'))
            ->post(route('cashier.notes.refunds.store', ['noteId' => 'note-legacy-service-refund-1']), [
                'selected_row_ids' => ['wi-legacy-service-refund-1'],
                'refunded_at' => $today,
                'reason' => 'Refund legacy service-only must stay blocked',
            ])
            ->assertRedirect(route('cashier.notes.index'))
            ->assertSessionHasErrors(['refund']);

        $this->assertDatabaseCount('customer_refunds', 0);
        $this->assertDatabaseCount('refund_component_allocations', 0);

        $this->assertDatabaseHas('work_items', [
            'id' => 'wi-legacy-service-refund-1',
            'status' => WorkItem::STATUS_OPEN,
        ]);

        $this->assertDatabaseHas('notes', [
            'id' => 'note-legacy-service-refund-1',
            'note_state' => 'closed',
            'total_rupiah' => 50000,
        ]);

        $this->assertDatabaseMissing('note_mutation_events', [
            'note_id' => 'note-legacy-service-refund-1',
            'mutation_type' => 'note_rows_canceled_via_refund',
        ]);
    }


    private function seedKasir(): User
    {
        $this->loginAsKasir();

        $user = User::query()->create([
            'name' => 'Kasir Refund',
            'email' => 'cashier-refund@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        return $user;
    }

    private function seedClosedPaidServiceOnlyNote(?string $transactionDate = null): void
    {
        $today = $transactionDate ?? date('Y-m-d');

        $this->seedNoteBase('note-1', 'Budi', $today, 50000, 'closed');
        $this->seedWorkItemBase('wi-1', 'note-1', 1, WorkItem::TYPE_SERVICE_ONLY, WorkItem::STATUS_OPEN, 50000);
        $this->seedServiceDetailBase('wi-1', 'Servis A', 50000, ServiceDetail::PART_SOURCE_NONE);

        $this->seedCustomerPaymentBase('payment-1', 50000, $today);
        $this->seedPaymentAllocationBase('allocation-1', 'payment-1', 'note-1', 50000);

        DB::table('payment_component_allocations')->insert([
            'id' => 'pca-1',
            'customer_payment_id' => 'payment-1',
            'note_id' => 'note-1',
            'work_item_id' => 'wi-1',
            'component_type' => 'service_fee',
            'component_ref_id' => 'wi-1',
            'component_amount_rupiah_snapshot' => 50000,
            'allocated_amount_rupiah' => 50000,
            'allocation_priority' => 1,
        ]);
    }

    private function seedClosedPaidProductOnlyNote(): void
    {
        $today = date('Y-m-d');

        $this->seedProductForRefund('product-refund-1', 50000, 30000, 1);
        $this->seedNoteBase('note-1', 'Budi', $today, 50000, 'closed');
        $this->seedWorkItemBase('wi-1', 'note-1', 1, WorkItem::TYPE_STORE_STOCK_SALE_ONLY, WorkItem::STATUS_OPEN, 50000);
        $this->seedStoreStockLineBase('ssl-refund-1', 'wi-1', 'product-refund-1', 1, 50000);
        $this->seedStockOut('move-refund-1', 'product-refund-1', 'ssl-refund-1', $today, 30000);
        $this->seedCustomerPaymentBase('payment-1', 50000, $today);
        $this->seedPaymentAllocationBase('allocation-1', 'payment-1', 'note-1', 50000);

        DB::table('payment_component_allocations')->insert([
            'id' => 'pca-1',
            'customer_payment_id' => 'payment-1',
            'note_id' => 'note-1',
            'work_item_id' => 'wi-1',
            'component_type' => 'product_only_work_item',
            'component_ref_id' => 'wi-1',
            'component_amount_rupiah_snapshot' => 50000,
            'allocated_amount_rupiah' => 50000,
            'allocation_priority' => 1,
        ]);
    }

    private function seedOpenPartialPaidServiceOnlyNote(): void
    {
        $today = date('Y-m-d');

        $this->seedNoteBase('note-1', 'Budi', $today, 50000, 'open');
        $this->seedWorkItemBase('wi-1', 'note-1', 1, WorkItem::TYPE_SERVICE_ONLY, WorkItem::STATUS_OPEN, 50000);
        $this->seedServiceDetailBase('wi-1', 'Servis A', 50000, ServiceDetail::PART_SOURCE_NONE);

        $this->seedCustomerPaymentBase('payment-1', 20000, $today);

        DB::table('payment_component_allocations')->insert([
            'id' => 'pca-1',
            'customer_payment_id' => 'payment-1',
            'note_id' => 'note-1',
            'work_item_id' => 'wi-1',
            'component_type' => 'service_fee',
            'component_ref_id' => 'wi-1',
            'component_amount_rupiah_snapshot' => 50000,
            'allocated_amount_rupiah' => 20000,
            'allocation_priority' => 1,
        ]);
    }

    private function seedClosedPaidTwoLineNote(): void
    {
        $today = date('Y-m-d');

        $this->seedNoteBase('note-2', 'Joko', $today, 80000, 'closed');

        $this->seedWorkItemBase('wi-1', 'note-2', 1, WorkItem::TYPE_SERVICE_ONLY, WorkItem::STATUS_OPEN, 50000);
        $this->seedServiceDetailBase('wi-1', 'Servis A', 50000, ServiceDetail::PART_SOURCE_NONE);

        $this->seedProductForRefund('product-refund-2', 30000, 20000, 1);
        $this->seedWorkItemBase('wi-2', 'note-2', 2, WorkItem::TYPE_STORE_STOCK_SALE_ONLY, WorkItem::STATUS_OPEN, 30000);
        $this->seedStoreStockLineBase('ssl-refund-2', 'wi-2', 'product-refund-2', 1, 30000);
        $this->seedStockOut('move-refund-2', 'product-refund-2', 'ssl-refund-2', $today, 20000);

        $this->seedCustomerPaymentBase('payment-2', 80000, $today);
        $this->seedPaymentAllocationBase('allocation-2', 'payment-2', 'note-2', 80000);

        DB::table('payment_component_allocations')->insert([
            [
                'id' => 'pca-2a',
                'customer_payment_id' => 'payment-2',
                'note_id' => 'note-2',
                'work_item_id' => 'wi-1',
                'component_type' => 'service_fee',
                'component_ref_id' => 'wi-1',
                'component_amount_rupiah_snapshot' => 50000,
                'allocated_amount_rupiah' => 50000,
                'allocation_priority' => 1,
            ],
            [
                'id' => 'pca-2b',
                'customer_payment_id' => 'payment-2',
                'note_id' => 'note-2',
                'work_item_id' => 'wi-2',
                'component_type' => 'product_only_work_item',
                'component_ref_id' => 'wi-2',
                'component_amount_rupiah_snapshot' => 30000,
                'allocated_amount_rupiah' => 30000,
                'allocation_priority' => 2,
            ],
        ]);
    }

    private function seedProductForRefund(string $id, int $priceRupiah, int $avgCostRupiah, int $qtyOnHand): void
    {
        $this->seedNotePaymentProduct($id, strtoupper($id), 'Produk Refund', 'General', null, $priceRupiah);
        DB::table('product_inventory')->insert(['product_id' => $id, 'qty_on_hand' => $qtyOnHand]);
        DB::table('product_inventory_costing')->insert([
            'product_id' => $id,
            'avg_cost_rupiah' => $avgCostRupiah,
            'inventory_value_rupiah' => $avgCostRupiah * $qtyOnHand,
        ]);
    }

    private function seedStockOut(string $id, string $productId, string $lineId, string $date, int $unitCostRupiah): void
    {
        DB::table('inventory_movements')->insert([
            'id' => $id,
            'product_id' => $productId,
            'movement_type' => 'stock_out',
            'source_type' => 'work_item_store_stock_line',
            'source_id' => $lineId,
            'tanggal_mutasi' => $date,
            'qty_delta' => -1,
            'unit_cost_rupiah' => $unitCostRupiah,
            'total_cost_rupiah' => -$unitCostRupiah,
        ]);
    }
}
