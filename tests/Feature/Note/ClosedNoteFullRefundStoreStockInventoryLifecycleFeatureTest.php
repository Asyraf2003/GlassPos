<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use App\Core\Note\WorkItem\ServiceDetail;
use App\Core\Note\WorkItem\WorkItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsMinimalNotePaymentFixture;
use Tests\TestCase;

final class ClosedNoteFullRefundStoreStockInventoryLifecycleFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalNotePaymentFixture;

    public function test_refund_for_closed_store_stock_package_refunds_service_and_product_components_and_reverses_inventory(): void
    {
        $user = $this->seedKasir();
        $this->seedClosedPaidStoreStockNote();

        $this->actingAs($user)
            ->from(route('cashier.notes.index'))
            ->post(route('cashier.notes.refunds.store', ['noteId' => 'note-1']), [
                'selected_row_ids' => ['wi-1'],
                'stock_returns' => ['wi-1' => true],
                'refunded_at' => date('Y-m-d'),
                'reason' => 'Refund penuh part stok toko',
            ])
            ->assertRedirect(route('cashier.notes.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('notes', [
            'id' => 'note-1',
            'note_state' => 'refunded',
        ]);

        $this->assertDatabaseHas('customer_refunds', [
            'customer_payment_id' => 'payment-1',
            'note_id' => 'note-1',
            'amount_rupiah' => 50000,
        ]);

        $this->assertDatabaseHas('refund_component_allocations', [
            'component_type' => 'service_fee',
            'component_ref_id' => 'wi-1',
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => 'product-1',
            'movement_type' => 'stock_in',
            'source_type' => 'work_item_store_stock_line_reversal',
            'source_id' => 'ssl-1',
            'qty_delta' => 2,
            'unit_cost_rupiah' => 15000,
            'total_cost_rupiah' => 30000,
        ]);

        $this->assertDatabaseHas('product_inventory', [
            'product_id' => 'product-1',
            'qty_on_hand' => 5,
        ]);

        $this->assertDatabaseHas('product_inventory_costing', [
            'product_id' => 'product-1',
            'avg_cost_rupiah' => 15000,
            'inventory_value_rupiah' => 75000,
        ]);
    }

    public function test_selected_row_refund_for_closed_store_stock_note_includes_service_fee(): void
    {
        $user = $this->seedKasir();
        $this->seedClosedPaidStoreStockNote();

        $this->actingAs($user)
            ->from(route('cashier.notes.index'))
            ->post(route('cashier.notes.refunds.store', ['noteId' => 'note-1']), [
                'selected_row_ids' => ['wi-1'],
                'stock_returns' => ['wi-1' => true],
                'refunded_at' => date('Y-m-d'),
                'reason' => 'Refund full selected stock row',
            ])
            ->assertRedirect(route('cashier.notes.index'))
            ->assertSessionHas('success');

        $refundId = (string) DB::table('customer_refunds')->value('id');

        $this->assertDatabaseHas('refund_component_allocations', [
            'customer_refund_id' => $refundId,
            'component_type' => 'service_fee',
            'component_ref_id' => 'wi-1',
        ]);

        $this->assertDatabaseHas('refund_component_allocations', [
            'customer_refund_id' => $refundId,
            'component_type' => 'service_store_stock_part',
            'component_ref_id' => 'ssl-1',
            'refunded_amount_rupiah' => 30000,
        ]);

        $this->assertDatabaseHas('notes', [
            'id' => 'note-1',
            'note_state' => 'refunded',
        ]);
    }

    private function seedKasir(): User
    {
        $this->loginAsKasir();

        $user = User::query()->create([
            'name' => 'Kasir Refund Inventory',
            'email' => 'cashier-refund-inventory@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        return $user;
    }

    private function seedClosedPaidStoreStockNote(): void
    {
        $today = date('Y-m-d');

        $this->seedNoteBase('note-1', 'Budi', $today, 50000, 'closed');
        $this->seedWorkItemBase(
            'wi-1',
            'note-1',
            1,
            WorkItem::TYPE_SERVICE_WITH_STORE_STOCK_PART,
            WorkItem::STATUS_OPEN,
            50000
        );
        $this->seedServiceDetailBase('wi-1', 'Servis + Part', 20000, ServiceDetail::PART_SOURCE_NONE);

        DB::table('products')->insert([
            'id' => 'product-1',
            'kode_barang' => 'PRD-1',
            'nama_barang' => 'Produk A',
            'merek' => 'Merek A',
            'ukuran' => null,
            'harga_jual' => 25000,
        ]);

        DB::table('work_item_store_stock_lines')->insert([
            'id' => 'ssl-1',
            'work_item_id' => 'wi-1',
            'product_id' => 'product-1',
            'qty' => 2,
            'line_total_rupiah' => 30000,
        ]);

        DB::table('product_inventory')->insert([
            'product_id' => 'product-1',
            'qty_on_hand' => 3,
        ]);

        DB::table('product_inventory_costing')->insert([
            'product_id' => 'product-1',
            'avg_cost_rupiah' => 15000,
            'inventory_value_rupiah' => 45000,
        ]);

        DB::table('inventory_movements')->insert([
            'id' => 'move-1',
            'product_id' => 'product-1',
            'movement_type' => 'stock_out',
            'source_type' => 'work_item_store_stock_line',
            'source_id' => 'ssl-1',
            'tanggal_mutasi' => $today,
            'qty_delta' => -2,
            'unit_cost_rupiah' => 15000,
            'total_cost_rupiah' => -30000,
        ]);

        $this->seedCustomerPaymentBase('payment-1', 50000, $today);
        $this->seedPaymentAllocationBase('allocation-1', 'payment-1', 'note-1', 50000);

        DB::table('payment_component_allocations')->insert([
            'id' => 'pca-1',
            'customer_payment_id' => 'payment-1',
            'note_id' => 'note-1',
            'work_item_id' => 'wi-1',
            'component_type' => 'service_store_stock_part',
            'component_ref_id' => 'ssl-1',
            'component_amount_rupiah_snapshot' => 30000,
            'allocated_amount_rupiah' => 30000,
            'allocation_priority' => 1,
        ]);

        DB::table('payment_component_allocations')->insert([
            'id' => 'pca-2',
            'customer_payment_id' => 'payment-1',
            'note_id' => 'note-1',
            'work_item_id' => 'wi-1',
            'component_type' => 'service_fee',
            'component_ref_id' => 'wi-1',
            'component_amount_rupiah_snapshot' => 20000,
            'allocated_amount_rupiah' => 20000,
            'allocation_priority' => 2,
        ]);
    }
}
