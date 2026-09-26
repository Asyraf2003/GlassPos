<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use App\Core\Note\WorkItem\WorkItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsMinimalNotePaymentFixture;
use Tests\TestCase;

final class ClosedNoteFullRefundProductOnlyInventoryLifecycleFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalNotePaymentFixture;

    public function test_full_refund_for_closed_product_only_note_reverses_inventory(): void
    {
        $user = $this->seedKasir();
        $this->seedClosedPaidProductOnlyNote();

        $this->actingAs($user)
            ->from(route('cashier.notes.index'))
            ->post(route('cashier.notes.refunds.store', ['noteId' => 'note-1']), [
                'selected_row_ids' => ['wi-1'],
                'stock_returns' => ['wi-1' => true],
                'customer_payment_id' => 'payment-1',
                'amount_rupiah' => 50000,
                'refunded_at' => date('Y-m-d'),
                'reason' => 'Refund penuh produk toko',
            ])
            ->assertRedirect(route('cashier.notes.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('notes', [
            'id' => 'note-1',
            'note_state' => 'refunded',
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
    }

    private function seedKasir(): User
    {
        $this->loginAsKasir();

        $user = User::query()->create([
            'name' => 'Kasir Refund Product Only',
            'email' => 'cashier-refund-product-only@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        return $user;
    }

    private function seedClosedPaidProductOnlyNote(): void
    {
        $today = date('Y-m-d');

        $this->seedNoteBase('note-1', 'Budi', $today, 50000, 'closed');
        $this->seedNotePaymentProduct('product-1', 'PRD-1', 'Produk A', 'Merek A', 100, 25000);
        $this->seedWorkItemBase('wi-1', 'note-1', 1, WorkItem::TYPE_STORE_STOCK_SALE_ONLY, WorkItem::STATUS_OPEN, 50000);
        $this->seedStoreStockLineBase('ssl-1', 'wi-1', 'product-1', 2, 50000);

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
            'component_type' => 'product_only_work_item',
            'component_ref_id' => 'wi-1',
            'component_amount_rupiah_snapshot' => 50000,
            'allocated_amount_rupiah' => 50000,
            'allocation_priority' => 1,
        ]);
    }
}
