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

final class ClosedNoteFullRefundExternalPurchaseLifecycleFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalNotePaymentFixture;

    public function test_selected_row_refund_for_closed_external_purchase_note_is_default_blocked(): void
    {
        $user = $this->seedKasir();
        $this->seedClosedPaidExternalPurchaseNote();

        $this->actingAs($user)
            ->from(route('cashier.notes.index'))
            ->post(route('cashier.notes.refunds.store', ['noteId' => 'note-1']), [
                'selected_row_ids' => ['wi-1'],
                'refunded_at' => date('Y-m-d'),
                'reason' => 'Refund full selected external row',
            ])
            ->assertRedirect(route('cashier.notes.index'))
            ->assertSessionHasErrors(['refund']);

        $this->assertDatabaseCount('customer_refunds', 0);
        $this->assertDatabaseCount('refund_component_allocations', 0);
        $this->assertDatabaseHas('notes', [
            'id' => 'note-1',
            'note_state' => 'closed',
        ]);

        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_full_selected_row_refund_for_closed_external_purchase_note_keeps_inventory_untouched_when_blocked(): void
    {
        $user = $this->seedKasir();
        $this->seedClosedPaidExternalPurchaseNote();

        $this->actingAs($user)
            ->from(route('cashier.notes.index'))
            ->post(route('cashier.notes.refunds.store', ['noteId' => 'note-1']), [
                'selected_row_ids' => ['wi-1'],
                'refunded_at' => date('Y-m-d'),
                'reason' => 'Refund full selected row and keep inventory untouched',
            ])
            ->assertRedirect(route('cashier.notes.index'))
            ->assertSessionHasErrors(['refund']);

        $this->assertDatabaseHas('notes', [
            'id' => 'note-1',
            'note_state' => 'closed',
        ]);

        $this->assertDatabaseCount('customer_refunds', 0);
        $this->assertDatabaseCount('refund_component_allocations', 0);
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_mixed_selection_with_refundable_product_and_blocked_external_row_is_rejected_atomically(): void
    {
        $user = $this->seedKasir();
        $today = date('Y-m-d');

        $this->seedNotePaymentProduct(
            'product-mixed-refund-1',
            'MIX-REF-1',
            'Produk Mixed Refund',
            'General',
            null,
            50000,
        );
        DB::table('product_inventory')->insert([
            'product_id' => 'product-mixed-refund-1',
            'qty_on_hand' => 0,
        ]);
        DB::table('product_inventory_costing')->insert([
            'product_id' => 'product-mixed-refund-1',
            'avg_cost_rupiah' => 30000,
            'inventory_value_rupiah' => 0,
        ]);

        $this->seedNoteBase('note-mixed-1', 'Budi Mixed Refund', $today, 61000, 'closed');

        $this->seedWorkItemBase(
            'wi-product-mixed-1',
            'note-mixed-1',
            1,
            WorkItem::TYPE_STORE_STOCK_SALE_ONLY,
            WorkItem::STATUS_OPEN,
            50000,
        );
        $this->seedStoreStockLineBase(
            'ssl-product-mixed-1',
            'wi-product-mixed-1',
            'product-mixed-refund-1',
            1,
            50000,
        );
        DB::table('inventory_movements')->insert([
            'id' => 'move-product-mixed-1',
            'product_id' => 'product-mixed-refund-1',
            'movement_type' => 'stock_out',
            'source_type' => 'work_item_store_stock_line',
            'source_id' => 'ssl-product-mixed-1',
            'tanggal_mutasi' => $today,
            'qty_delta' => -1,
            'unit_cost_rupiah' => 30000,
            'total_cost_rupiah' => -30000,
        ]);

        $this->seedWorkItemBase(
            'wi-external-mixed-1',
            'note-mixed-1',
            2,
            WorkItem::TYPE_SERVICE_WITH_EXTERNAL_PURCHASE,
            WorkItem::STATUS_OPEN,
            11000,
        );
        $this->seedServiceDetailBase(
            'wi-external-mixed-1',
            'Servis External Mixed Refund',
            9000,
            ServiceDetail::PART_SOURCE_NONE,
        );
        DB::table('work_item_external_purchase_lines')->insert([
            'id' => 'ext-mixed-1',
            'work_item_id' => 'wi-external-mixed-1',
            'cost_description' => 'Barang luar mixed',
            'unit_cost_rupiah' => 2000,
            'qty' => 1,
            'line_total_rupiah' => 2000,
        ]);

        $this->seedCustomerPaymentBase('payment-mixed-1', 61000, $today);
        $this->seedPaymentAllocationBase('allocation-mixed-1', 'payment-mixed-1', 'note-mixed-1', 61000);

        DB::table('payment_component_allocations')->insert([
            [
                'id' => 'pca-product-mixed-1',
                'customer_payment_id' => 'payment-mixed-1',
                'note_id' => 'note-mixed-1',
                'work_item_id' => 'wi-product-mixed-1',
                'component_type' => 'product_only_work_item',
                'component_ref_id' => 'wi-product-mixed-1',
                'component_amount_rupiah_snapshot' => 50000,
                'allocated_amount_rupiah' => 50000,
                'allocation_priority' => 1,
            ],
            [
                'id' => 'pca-service-mixed-1',
                'customer_payment_id' => 'payment-mixed-1',
                'note_id' => 'note-mixed-1',
                'work_item_id' => 'wi-external-mixed-1',
                'component_type' => 'service_fee',
                'component_ref_id' => 'wi-external-mixed-1',
                'component_amount_rupiah_snapshot' => 9000,
                'allocated_amount_rupiah' => 9000,
                'allocation_priority' => 2,
            ],
            [
                'id' => 'pca-external-mixed-1',
                'customer_payment_id' => 'payment-mixed-1',
                'note_id' => 'note-mixed-1',
                'work_item_id' => 'wi-external-mixed-1',
                'component_type' => 'service_external_purchase_part',
                'component_ref_id' => 'ext-mixed-1',
                'component_amount_rupiah_snapshot' => 2000,
                'allocated_amount_rupiah' => 2000,
                'allocation_priority' => 3,
            ],
        ]);

        $this->actingAs($user)
            ->from(route('cashier.notes.index'))
            ->post(route('cashier.notes.refunds.store', ['noteId' => 'note-mixed-1']), [
                'selected_row_ids' => ['wi-product-mixed-1', 'wi-external-mixed-1'],
                'refunded_at' => $today,
                'reason' => 'Mixed refundable + blocked row must fail atomically.',
            ])
            ->assertRedirect(route('cashier.notes.index'))
            ->assertSessionHasErrors(['refund']);

        $this->assertDatabaseCount('customer_refunds', 0);
        $this->assertDatabaseCount('refund_component_allocations', 0);
        $this->assertDatabaseHas('work_items', [
            'id' => 'wi-product-mixed-1',
            'status' => WorkItem::STATUS_OPEN,
        ]);
        $this->assertDatabaseHas('work_items', [
            'id' => 'wi-external-mixed-1',
            'status' => WorkItem::STATUS_OPEN,
        ]);
        $this->assertDatabaseHas('notes', [
            'id' => 'note-mixed-1',
            'note_state' => 'closed',
            'total_rupiah' => 61000,
        ]);
        $this->assertDatabaseHas('product_inventory', [
            'product_id' => 'product-mixed-refund-1',
            'qty_on_hand' => 0,
        ]);
        self::assertSame(
            0,
            DB::table('inventory_movements')
                ->where('source_type', 'work_item_store_stock_line_reversal')
                ->where('source_id', 'ssl-product-mixed-1')
                ->count(),
        );
    }


    private function seedKasir(): User
    {
        $this->loginAsKasir();

        $user = User::query()->create([
            'name' => 'Kasir Refund External Purchase',
            'email' => 'cashier-refund-external@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        return $user;
    }

    private function seedClosedPaidExternalPurchaseNote(): void
    {
        $today = date('Y-m-d');

        $this->seedNoteBase('note-1', 'Budi', $today, 11000, 'closed');
        $this->seedWorkItemBase(
            'wi-1',
            'note-1',
            1,
            WorkItem::TYPE_SERVICE_WITH_EXTERNAL_PURCHASE,
            WorkItem::STATUS_OPEN,
            11000
        );
        $this->seedServiceDetailBase('wi-1', 'Servis + Barang Luar', 9000, ServiceDetail::PART_SOURCE_NONE);

        DB::table('work_item_external_purchase_lines')->insert([
            'id' => 'ext-1',
            'work_item_id' => 'wi-1',
            'cost_description' => 'Beli luar',
            'unit_cost_rupiah' => 2000,
            'qty' => 1,
            'line_total_rupiah' => 2000,
        ]);

        $this->seedCustomerPaymentBase('payment-1', 11000, $today);
        $this->seedPaymentAllocationBase('allocation-1', 'payment-1', 'note-1', 11000);

        DB::table('payment_component_allocations')->insert([
            [
                'id' => 'pca-1',
                'customer_payment_id' => 'payment-1',
                'note_id' => 'note-1',
                'work_item_id' => 'wi-1',
                'component_type' => 'service_fee',
                'component_ref_id' => 'wi-1',
                'component_amount_rupiah_snapshot' => 9000,
                'allocated_amount_rupiah' => 9000,
                'allocation_priority' => 1,
            ],
            [
                'id' => 'pca-2',
                'customer_payment_id' => 'payment-1',
                'note_id' => 'note-1',
                'work_item_id' => 'wi-1',
                'component_type' => 'service_external_purchase_part',
                'component_ref_id' => 'ext-1',
                'component_amount_rupiah_snapshot' => 2000,
                'allocated_amount_rupiah' => 2000,
                'allocation_priority' => 2,
            ],
        ]);
    }
}
