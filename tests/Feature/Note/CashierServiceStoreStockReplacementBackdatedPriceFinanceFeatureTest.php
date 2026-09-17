<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Core\Note\WorkItem\WorkItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsMinimalNotePaymentFixture;
use Tests\TestCase;

final class CashierServiceStoreStockReplacementBackdatedPriceFinanceFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalNotePaymentFixture;

    public function test_service_store_stock_replacement_rejects_downward_overpaid_replay_instead_of_capping_old_payment(): void
    {
        $user = $this->loginAsAuthorizedAdmin();
        $oldDate = date('Y-m-d', strtotime('-4 days'));
        $today = date('Y-m-d');

        $this->seedPaidServiceStoreStockNote($oldDate);

        $this->actingAs($user)
            ->get(route('admin.notes.show', ['noteId' => 'note-service-stock-1']))
            ->assertOk();

        DB::table('products')
            ->where('id', 'product-1')
            ->update(['harga_jual' => 110000]);

        $edit = $this->actingAs($user)
            ->get(route('admin.notes.workspace.edit', ['noteId' => 'note-service-stock-1']));

        $edit->assertOk();
        $edit->assertSee('100000');
        $edit->assertSee('revision_snapshot');

        $response = $this->actingAs($user)->patch(
            route('admin.notes.workspace.update', ['noteId' => 'note-service-stock-1']),
            [
            'base_revision_id' => $this->revisionBaseForTest('note-service-stock-1'),
                'note' => [
                    'customer_name' => 'Budi Revised Service Stock',
                    'customer_phone' => '08123456789',
                    'transaction_date' => $today,
                ],
                'items' => [
                    [
                        'entry_mode' => 'service',
                        'description' => null,
                        'part_source' => 'store_stock',
                        'service' => [
                            'name' => 'Servis Rem Revised',
                            'price_rupiah' => 50000,
                            'notes' => null,
                        ],
                        'product_lines' => [
                            [
                                'product_id' => 'product-1',
                                'qty' => 2,
                                'unit_price_rupiah' => 100000,
                                'price_basis' => 'revision_snapshot',
                            ],
                        ],
                        'external_purchase_lines' => [],
                    ],
                ],
                'inline_payment' => [
                    'decision' => 'skip',
                    'payment_method' => null,
                    'paid_at' => null,
                    'amount_paid_rupiah' => null,
                    'amount_received_rupiah' => null,
                ],
            ],
        );

        $response->assertRedirect(route('admin.notes.workspace.edit', ['noteId' => 'note-service-stock-1']));
        $response->assertSessionHasErrors();

        $this->assertDatabaseHas('notes', [
            'id' => 'note-service-stock-1',
            'customer_name' => 'Budi Service Stock Lama',
            'transaction_date' => $oldDate,
            'total_rupiah' => 350000,
        ]);

        $this->assertDatabaseMissing('note_revisions', [
            'note_root_id' => 'note-service-stock-1',
            'revision_number' => 2,
        ]);

        $this->assertDatabaseHas('work_items', [
            'id' => 'wi-service-stock-old-1',
            'note_id' => 'note-service-stock-1',
            'transaction_type' => WorkItem::TYPE_SERVICE_WITH_STORE_STOCK_PART,
            'subtotal_rupiah' => 350000,
        ]);

        $this->assertDatabaseHas('work_item_service_details', [
            'work_item_id' => 'wi-service-stock-old-1',
            'service_name' => 'Servis Rem Lama',
            'service_price_rupiah' => 50000,
            'part_source' => 'none',
        ]);

        $this->assertDatabaseHas('work_item_store_stock_lines', [
            'id' => 'ssl-service-stock-old-1',
            'work_item_id' => 'wi-service-stock-old-1',
            'product_id' => 'product-1',
            'qty' => 3,
            'line_total_rupiah' => 300000,
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'id' => 'move-service-stock-old-1',
            'product_id' => 'product-1',
            'movement_type' => 'stock_out',
            'source_type' => 'work_item_store_stock_line',
            'source_id' => 'ssl-service-stock-old-1',
            'tanggal_mutasi' => $oldDate,
            'qty_delta' => -3,
        ]);

        $this->assertDatabaseMissing('inventory_movements', [
            'movement_type' => 'stock_in',
            'source_type' => 'transaction_workspace_updated',
            'source_id' => 'ssl-service-stock-old-1',
            'tanggal_mutasi' => $today,
        ]);

        $this->assertDatabaseHas('product_inventory', [
            'product_id' => 'product-1',
            'qty_on_hand' => 7,
        ]);

        $this->assertSame(
            350000,
            (int) DB::table('payment_component_allocations')
                ->where('note_id', 'note-service-stock-1')
                ->where('customer_payment_id', 'payment-service-stock-1')
                ->sum('allocated_amount_rupiah')
        );

        $this->assertDatabaseMissing('payment_component_allocations', [
            'note_id' => 'note-service-stock-1',
            'customer_payment_id' => 'payment-service-stock-1',
            'allocated_amount_rupiah' => 250000,
        ]);

        $this->assertDatabaseHas('customer_payments', [
            'id' => 'payment-service-stock-1',
            'amount_rupiah' => 350000,
        ]);
    }

    private function seedPaidServiceStoreStockNote(string $oldDate): void
    {
        $this->seedNoteBase('note-service-stock-1', 'Budi Service Stock Lama', $oldDate, 350000, 'closed');
        $this->seedNotePaymentProduct('product-1', 'PRD-1', 'Produk A', 'Merek A', 100, 100000);
        $this->seedWorkItemBase(
            'wi-service-stock-old-1',
            'note-service-stock-1',
            1,
            WorkItem::TYPE_SERVICE_WITH_STORE_STOCK_PART,
            WorkItem::STATUS_OPEN,
            350000
        );
        $this->seedServiceDetailBase('wi-service-stock-old-1', 'Servis Rem Lama', 50000, 'none');
        $this->seedStoreStockLineBase('ssl-service-stock-old-1', 'wi-service-stock-old-1', 'product-1', 3, 300000);

        DB::table('product_inventory')->insert([
            'product_id' => 'product-1',
            'qty_on_hand' => 7,
        ]);

        DB::table('product_inventory_costing')->insert([
            'product_id' => 'product-1',
            'avg_cost_rupiah' => 60000,
            'inventory_value_rupiah' => 420000,
        ]);

        DB::table('inventory_movements')->insert([
            'id' => 'move-service-stock-old-1',
            'product_id' => 'product-1',
            'movement_type' => 'stock_out',
            'source_type' => 'work_item_store_stock_line',
            'source_id' => 'ssl-service-stock-old-1',
            'tanggal_mutasi' => $oldDate,
            'qty_delta' => -3,
            'unit_cost_rupiah' => 60000,
            'total_cost_rupiah' => -180000,
        ]);

        $this->seedCustomerPaymentBase('payment-service-stock-1', 350000, $oldDate);
        $this->seedPaymentAllocationBase(
            'payment-allocation-service-stock-1',
            'payment-service-stock-1',
            'note-service-stock-1',
            350000
        );

        DB::table('payment_component_allocations')->insert([
            [
                'id' => 'pca-service-stock-old-part-1',
                'customer_payment_id' => 'payment-service-stock-1',
                'note_id' => 'note-service-stock-1',
                'work_item_id' => 'wi-service-stock-old-1',
                'component_type' => 'service_store_stock_part',
                'component_ref_id' => 'ssl-service-stock-old-1',
                'component_amount_rupiah_snapshot' => 300000,
                'allocated_amount_rupiah' => 300000,
                'allocation_priority' => 1,
            ],
            [
                'id' => 'pca-service-stock-old-fee-1',
                'customer_payment_id' => 'payment-service-stock-1',
                'note_id' => 'note-service-stock-1',
                'work_item_id' => 'wi-service-stock-old-1',
                'component_type' => 'service_fee',
                'component_ref_id' => 'wi-service-stock-old-1',
                'component_amount_rupiah_snapshot' => 50000,
                'allocated_amount_rupiah' => 50000,
                'allocation_priority' => 2,
            ],
        ]);
    }
}
