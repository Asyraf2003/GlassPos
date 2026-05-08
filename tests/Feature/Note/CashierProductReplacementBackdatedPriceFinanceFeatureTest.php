<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Core\Note\WorkItem\WorkItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsMinimalNotePaymentFixture;
use Tests\TestCase;

final class CashierProductReplacementBackdatedPriceFinanceFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalNotePaymentFixture;

    public function test_cashier_product_replacement_rejects_downward_overpaid_replay_instead_of_capping_old_payment(): void
    {
        $user = $this->loginAsAuthorizedAdmin();
        $oldDate = date('Y-m-d', strtotime('-4 days'));
        $today = date('Y-m-d');

        $this->seedPaidProductOnlyNote($oldDate);

        $this->actingAs($user)
            ->get(route('admin.notes.show', ['noteId' => 'note-1']))
            ->assertOk();

        DB::table('products')
            ->where('id', 'product-1')
            ->update(['harga_jual' => 110000]);

        $edit = $this->actingAs($user)
            ->get(route('admin.notes.workspace.edit', ['noteId' => 'note-1']));

        $edit->assertOk();
        $edit->assertSee('100000');
        $edit->assertSee('revision_snapshot');

        $response = $this->actingAs($user)->patch(
            route('admin.notes.workspace.update', ['noteId' => 'note-1']),
            [
                'note' => [
                    'customer_name' => 'Budi Revised Product',
                    'customer_phone' => '08123456789',
                    'transaction_date' => $today,
                ],
                'items' => [
                    [
                        'entry_mode' => 'product',
                        'description' => null,
                        'part_source' => 'store_stock',
                        'service' => [
                            'name' => null,
                            'price_rupiah' => null,
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

        $response->assertRedirect(route('admin.notes.workspace.edit', ['noteId' => 'note-1']));
        $response->assertSessionHasErrors();

        $this->assertDatabaseHas('notes', [
            'id' => 'note-1',
            'customer_name' => 'Budi Product Lama',
            'transaction_date' => $oldDate,
            'total_rupiah' => 300000,
        ]);

        $this->assertDatabaseMissing('note_revisions', [
            'note_root_id' => 'note-1',
            'revision_number' => 2,
        ]);

        $this->assertDatabaseHas('work_items', [
            'id' => 'wi-old-1',
            'note_id' => 'note-1',
            'transaction_type' => WorkItem::TYPE_STORE_STOCK_SALE_ONLY,
            'subtotal_rupiah' => 300000,
        ]);

        $this->assertDatabaseHas('work_item_store_stock_lines', [
            'id' => 'ssl-old-1',
            'work_item_id' => 'wi-old-1',
            'product_id' => 'product-1',
            'qty' => 3,
            'line_total_rupiah' => 300000,
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'id' => 'move-old-1',
            'product_id' => 'product-1',
            'movement_type' => 'stock_out',
            'source_type' => 'work_item_store_stock_line',
            'source_id' => 'ssl-old-1',
            'tanggal_mutasi' => $oldDate,
            'qty_delta' => -3,
        ]);

        $this->assertDatabaseMissing('inventory_movements', [
            'movement_type' => 'stock_in',
            'source_type' => 'transaction_workspace_updated',
            'source_id' => 'ssl-old-1',
            'tanggal_mutasi' => $today,
        ]);

        $this->assertDatabaseHas('product_inventory', [
            'product_id' => 'product-1',
            'qty_on_hand' => 7,
        ]);

        $this->assertSame(
            300000,
            (int) DB::table('payment_component_allocations')
                ->where('note_id', 'note-1')
                ->where('customer_payment_id', 'payment-1')
                ->sum('allocated_amount_rupiah')
        );

        $this->assertDatabaseMissing('payment_component_allocations', [
            'note_id' => 'note-1',
            'customer_payment_id' => 'payment-1',
            'allocated_amount_rupiah' => 200000,
        ]);

        $this->assertDatabaseHas('customer_payments', [
            'id' => 'payment-1',
            'amount_rupiah' => 300000,
        ]);
    }

    public function test_cashier_product_replacement_reuses_only_net_payment_after_refund(): void
    {
        $user = $this->loginAsAuthorizedAdmin();
        $oldDate = date('Y-m-d', strtotime('-4 days'));
        $today = date('Y-m-d');

        $this->seedPaidProductOnlyNote($oldDate);

        DB::table('customer_refunds')->insert([
            'id' => 'refund-1',
            'customer_payment_id' => 'payment-1',
            'note_id' => 'note-1',
            'amount_rupiah' => 100000,
            'refunded_at' => $oldDate,
            'reason' => 'Refund sebagian sebelum revisi',
        ]);

        DB::table('refund_component_allocations')->insert([
            'id' => 'rca-1',
            'customer_refund_id' => 'refund-1',
            'customer_payment_id' => 'payment-1',
            'note_id' => 'note-1',
            'work_item_id' => 'wi-old-1',
            'component_type' => 'product_only_work_item',
            'component_ref_id' => 'wi-old-1',
            'refunded_amount_rupiah' => 100000,
            'refund_priority' => 1,
        ]);

        DB::table('products')
            ->where('id', 'product-1')
            ->update(['harga_jual' => 110000]);

        $this->actingAs($user)
            ->get(route('admin.notes.show', ['noteId' => 'note-1']))
            ->assertOk();

        $edit = $this->actingAs($user)
            ->get(route('admin.notes.workspace.edit', ['noteId' => 'note-1']));

        $edit->assertOk();
        $edit->assertSee('revision_snapshot');

        $response = $this->actingAs($user)->patch(
            route('admin.notes.workspace.update', ['noteId' => 'note-1']),
            [
                'note' => [
                    'customer_name' => 'Budi Revised Product Net Refund',
                    'customer_phone' => '08123456789',
                    'transaction_date' => $today,
                ],
                'items' => [
                    [
                        'entry_mode' => 'product',
                        'description' => null,
                        'part_source' => 'store_stock',
                        'service' => [
                            'name' => null,
                            'price_rupiah' => null,
                            'notes' => null,
                        ],
                        'product_lines' => [
                            [
                                'product_id' => 'product-1',
                                'qty' => 3,
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

        $response->assertRedirect(route('admin.notes.show', ['noteId' => 'note-1']));
        $response->assertSessionHasNoErrors();

        $this->assertSame(
            200000,
            (int) DB::table('payment_component_allocations')
                ->where('note_id', 'note-1')
                ->where('customer_payment_id', 'payment-1')
                ->sum('allocated_amount_rupiah')
        );

        $this->assertSame(
            100000,
            (int) DB::table('refund_component_allocations')
                ->where('note_id', 'note-1')
                ->where('customer_payment_id', 'payment-1')
                ->sum('refunded_amount_rupiah')
        );

        $this->assertSame(
            300000,
            (int) DB::table('customer_payments')
                ->where('id', 'payment-1')
                ->value('amount_rupiah')
        );
    }



    public function test_product_replacement_rejects_underpriced_current_catalog_store_stock_line(): void
    {
        $user = $this->loginAsAuthorizedAdmin();
        $oldDate = date('Y-m-d', strtotime('-4 days'));
        $today = date('Y-m-d');

        $this->seedPaidProductOnlyNote($oldDate);

        $response = $this->patchProductReplacement(
            $user,
            $today,
            1,
            1,
            'current_catalog',
            'Budi Underpriced Current Catalog'
        );

        $response->assertRedirect(route('admin.notes.workspace.edit', ['noteId' => 'note-1']));
        $response->assertSessionHasErrors([
            'revision' => 'Harga jual pada store stock line tidak boleh di bawah harga jual minimum.',
        ]);

        $this->assertRejectedProductReplacementRolledBack($oldDate);
    }

    public function test_product_replacement_rejects_underpriced_revision_snapshot_store_stock_line_before_inventory_issue(): void
    {
        $user = $this->loginAsAuthorizedAdmin();
        $oldDate = date('Y-m-d', strtotime('-4 days'));
        $today = date('Y-m-d');

        $this->seedPaidProductOnlyNote($oldDate);

        $response = $this->patchProductReplacement(
            $user,
            $today,
            1,
            1,
            'revision_snapshot',
            'Budi Underpriced Revision Snapshot'
        );

        $response->assertRedirect(route('admin.notes.workspace.edit', ['noteId' => 'note-1']));
        $response->assertSessionHasErrors([
            'revision' => 'Harga jual pada store stock line tidak boleh di bawah harga jual minimum.',
        ]);

        $this->assertRejectedProductReplacementRolledBack($oldDate);
    }

    public function test_product_replacement_accepts_valid_revision_snapshot_store_stock_line(): void
    {
        $user = $this->loginAsAuthorizedAdmin();
        $oldDate = date('Y-m-d', strtotime('-4 days'));
        $today = date('Y-m-d');

        $this->seedPaidProductOnlyNote($oldDate);

        $response = $this->patchProductReplacement(
            $user,
            $today,
            3,
            100000,
            'revision_snapshot',
            'Budi Valid Revision Snapshot'
        );

        $response->assertRedirect(route('admin.notes.show', ['noteId' => 'note-1']));
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('note_revisions', [
            'note_root_id' => 'note-1',
            'revision_number' => 2,
        ]);

        $this->assertDatabaseHas('notes', [
            'id' => 'note-1',
            'customer_name' => 'Budi Valid Revision Snapshot',
            'transaction_date' => $today,
            'total_rupiah' => 300000,
        ]);
    }

    private function patchProductReplacement(
        mixed $user,
        string $today,
        int $qty,
        int $unitPriceRupiah,
        string $priceBasis,
        string $customerName
    ): \Illuminate\Testing\TestResponse {
        return $this->actingAs($user)->patch(
            route('admin.notes.workspace.update', ['noteId' => 'note-1']),
            [
                'note' => [
                    'customer_name' => $customerName,
                    'customer_phone' => '08123456789',
                    'transaction_date' => $today,
                ],
                'items' => [
                    [
                        'entry_mode' => 'product',
                        'description' => null,
                        'part_source' => 'store_stock',
                        'service' => [
                            'name' => null,
                            'price_rupiah' => null,
                            'notes' => null,
                        ],
                        'product_lines' => [
                            [
                                'product_id' => 'product-1',
                                'qty' => $qty,
                                'unit_price_rupiah' => $unitPriceRupiah,
                                'price_basis' => $priceBasis,
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
    }

    private function assertRejectedProductReplacementRolledBack(string $oldDate): void
    {
        $this->assertDatabaseHas('notes', [
            'id' => 'note-1',
            'customer_name' => 'Budi Product Lama',
            'transaction_date' => $oldDate,
            'total_rupiah' => 300000,
        ]);

        $this->assertDatabaseMissing('note_revisions', [
            'note_root_id' => 'note-1',
            'revision_number' => 2,
        ]);

        $this->assertSame(
            1,
            DB::table('work_items')
                ->where('note_id', 'note-1')
                ->count()
        );

        $this->assertSame(
            1,
            DB::table('work_item_store_stock_lines')
                ->where('product_id', 'product-1')
                ->count()
        );

        $this->assertDatabaseHas('work_item_store_stock_lines', [
            'id' => 'ssl-old-1',
            'work_item_id' => 'wi-old-1',
            'product_id' => 'product-1',
            'qty' => 3,
            'line_total_rupiah' => 300000,
        ]);

        $this->assertSame(
            1,
            DB::table('inventory_movements')
                ->where('product_id', 'product-1')
                ->count()
        );

        $this->assertDatabaseHas('inventory_movements', [
            'id' => 'move-old-1',
            'product_id' => 'product-1',
            'movement_type' => 'stock_out',
            'source_type' => 'work_item_store_stock_line',
            'source_id' => 'ssl-old-1',
            'tanggal_mutasi' => $oldDate,
            'qty_delta' => -3,
        ]);

        $this->assertDatabaseHas('product_inventory', [
            'product_id' => 'product-1',
            'qty_on_hand' => 7,
        ]);
    }



    public function test_product_replacement_price_floor_rejection_rolls_back_without_issuing_inventory(): void
    {
        $user = $this->loginAsAuthorizedAdmin();
        $oldDate = date('Y-m-d', strtotime('-4 days'));
        $today = date('Y-m-d');

        $this->seedPaidProductOnlyNote($oldDate);

        $this->actingAs($user)
            ->get(route('admin.notes.show', ['noteId' => 'note-1']))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('admin.notes.workspace.edit', ['noteId' => 'note-1']))
            ->assertOk();

        $response = $this->actingAs($user)
            ->from(route('admin.notes.workspace.edit', ['noteId' => 'note-1']))
            ->patch(
                route('admin.notes.workspace.update', ['noteId' => 'note-1']),
                [
                    'note' => [
                        'customer_name' => 'Budi Underpriced Revision Snapshot',
                        'customer_phone' => '08123456789',
                        'transaction_date' => $today,
                    ],
                    'items' => [
                        [
                            'entry_mode' => 'product',
                            'description' => null,
                            'part_source' => 'store_stock',
                            'service' => [
                                'name' => null,
                                'price_rupiah' => null,
                                'notes' => null,
                            ],
                            'product_lines' => [
                                [
                                    'product_id' => 'product-1',
                                    'qty' => 1,
                                    'unit_price_rupiah' => 1,
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

        $response->assertRedirect(route('admin.notes.workspace.edit', ['noteId' => 'note-1']));
        $response->assertSessionHasErrors([
            'revision' => 'Harga jual pada store stock line tidak boleh di bawah harga jual minimum.',
        ]);

        $this->assertDatabaseHas('notes', [
            'id' => 'note-1',
            'customer_name' => 'Budi Product Lama',
            'transaction_date' => $oldDate,
            'total_rupiah' => 300000,
        ]);

        $this->assertDatabaseMissing('note_revisions', [
            'note_root_id' => 'note-1',
            'revision_number' => 2,
        ]);

        $this->assertSame(
            1,
            DB::table('work_items')
                ->where('note_id', 'note-1')
                ->count()
        );

        $this->assertSame(
            1,
            DB::table('work_item_store_stock_lines')
                ->where('product_id', 'product-1')
                ->count()
        );

        $this->assertDatabaseHas('work_item_store_stock_lines', [
            'id' => 'ssl-old-1',
            'work_item_id' => 'wi-old-1',
            'product_id' => 'product-1',
            'qty' => 3,
            'line_total_rupiah' => 300000,
        ]);

        $this->assertSame(
            1,
            DB::table('inventory_movements')
                ->where('product_id', 'product-1')
                ->count()
        );

        $this->assertDatabaseHas('inventory_movements', [
            'id' => 'move-old-1',
            'product_id' => 'product-1',
            'movement_type' => 'stock_out',
            'source_type' => 'work_item_store_stock_line',
            'source_id' => 'ssl-old-1',
            'tanggal_mutasi' => $oldDate,
            'qty_delta' => -3,
        ]);

        $this->assertDatabaseHas('product_inventory', [
            'product_id' => 'product-1',
            'qty_on_hand' => 7,
        ]);
    }


    private function seedPaidProductOnlyNote(string $oldDate): void
    {
        $this->seedNoteBase('note-1', 'Budi Product Lama', $oldDate, 300000, 'closed');
        $this->seedNotePaymentProduct('product-1', 'PRD-1', 'Produk A', 'Merek A', 100, 100000);
        $this->seedWorkItemBase('wi-old-1', 'note-1', 1, WorkItem::TYPE_STORE_STOCK_SALE_ONLY, WorkItem::STATUS_OPEN, 300000);
        $this->seedStoreStockLineBase('ssl-old-1', 'wi-old-1', 'product-1', 3, 300000);

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
            'id' => 'move-old-1',
            'product_id' => 'product-1',
            'movement_type' => 'stock_out',
            'source_type' => 'work_item_store_stock_line',
            'source_id' => 'ssl-old-1',
            'tanggal_mutasi' => $oldDate,
            'qty_delta' => -3,
            'unit_cost_rupiah' => 60000,
            'total_cost_rupiah' => -180000,
        ]);

        $this->seedCustomerPaymentBase('payment-1', 300000, $oldDate);
        $this->seedPaymentAllocationBase('payment-allocation-1', 'payment-1', 'note-1', 300000);

        DB::table('payment_component_allocations')->insert([
            'id' => 'pca-old-1',
            'customer_payment_id' => 'payment-1',
            'note_id' => 'note-1',
            'work_item_id' => 'wi-old-1',
            'component_type' => 'product_only_work_item',
            'component_ref_id' => 'wi-old-1',
            'component_amount_rupiah_snapshot' => 300000,
            'allocated_amount_rupiah' => 300000,
            'allocation_priority' => 1,
        ]);
    }

}
