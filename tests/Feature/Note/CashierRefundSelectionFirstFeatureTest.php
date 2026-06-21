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

final class CashierRefundSelectionFirstFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalNotePaymentFixture;

    public function test_refund_only_allocates_to_selected_closed_line(): void
    {
        $user = $this->seedKasir();
        $this->seedClosedPaidTwoLineNote();

        $response = $this->actingAs($user)->post(route('cashier.notes.refunds.store', ['noteId' => 'note-1']), [
            'selected_row_ids' => ['wi-1'],
            'customer_payment_id' => 'payment-1',
            'refunded_at' => date('Y-m-d'),
            'amount_rupiah' => 20000,
            'reason' => 'Refund line 1 saja',
        ]);

        $response->assertRedirect(route('cashier.notes.index'));

        $this->assertDatabaseHas('refund_component_allocations', [
            'note_id' => 'note-1',
            'work_item_id' => 'wi-1',
            'refunded_amount_rupiah' => 20000,
        ]);

        $this->assertDatabaseMissing('refund_component_allocations', [
            'note_id' => 'note-1',
            'work_item_id' => 'wi-2',
            'refunded_amount_rupiah' => 20000,
        ]);
    }

    private function seedKasir(): User
    {
        $this->loginAsKasir();

        $user = User::query()->create([
            'name' => 'Kasir Refund Select',
            'email' => 'kasir-refund-select@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        return $user;
    }

    private function seedClosedPaidTwoLineNote(): void
    {
        $today = date('Y-m-d');

        $this->seedNoteBase('note-1', 'Budi', $today, 50000, 'closed');

        $this->seedNotePaymentProduct('product-select-refund-1', 'SEL-REF-1', 'Produk Select Refund', 'General', null, 20000);
        DB::table('product_inventory')->insert(['product_id' => 'product-select-refund-1', 'qty_on_hand' => 1]);
        DB::table('product_inventory_costing')->insert([
            'product_id' => 'product-select-refund-1',
            'avg_cost_rupiah' => 12000,
            'inventory_value_rupiah' => 12000,
        ]);
        $this->seedWorkItemBase('wi-1', 'note-1', 1, WorkItem::TYPE_STORE_STOCK_SALE_ONLY, WorkItem::STATUS_DONE, 20000);
        $this->seedStoreStockLineBase('ssl-select-refund-1', 'wi-1', 'product-select-refund-1', 1, 20000);
        DB::table('inventory_movements')->insert([
            'id' => 'move-select-refund-1',
            'product_id' => 'product-select-refund-1',
            'movement_type' => 'stock_out',
            'source_type' => 'work_item_store_stock_line',
            'source_id' => 'ssl-select-refund-1',
            'tanggal_mutasi' => $today,
            'qty_delta' => -1,
            'unit_cost_rupiah' => 12000,
            'total_cost_rupiah' => -12000,
        ]);

        $this->seedWorkItemBase('wi-2', 'note-1', 2, WorkItem::TYPE_SERVICE_ONLY, WorkItem::STATUS_DONE, 30000);
        $this->seedServiceDetailBase('wi-2', 'Servis B', 30000, ServiceDetail::PART_SOURCE_NONE);

        $this->seedCustomerPaymentBase('payment-1', 50000, $today);
        $this->seedPaymentAllocationBase('allocation-1', 'payment-1', 'note-1', 50000);

        DB::table('payment_component_allocations')->insert([
            [
                'id' => 'pca-1',
                'customer_payment_id' => 'payment-1',
                'note_id' => 'note-1',
                'work_item_id' => 'wi-1',
                'component_type' => 'product_only_work_item',
                'component_ref_id' => 'wi-1',
                'component_amount_rupiah_snapshot' => 20000,
                'allocated_amount_rupiah' => 20000,
                'allocation_priority' => 1,
            ],
            [
                'id' => 'pca-2',
                'customer_payment_id' => 'payment-1',
                'note_id' => 'note-1',
                'work_item_id' => 'wi-2',
                'component_type' => 'service_fee',
                'component_ref_id' => 'wi-2',
                'component_amount_rupiah_snapshot' => 30000,
                'allocated_amount_rupiah' => 30000,
                'allocation_priority' => 2,
            ],
        ]);
    }
}
