<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Core\Note\WorkItem\WorkItem;
use App\Core\Shared\Exceptions\DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsMinimalNotePaymentFixture;
use Tests\TestCase;

final class NoteReplacementOverpaidAllocationReplayFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalNotePaymentFixture;

    public function test_downward_replacement_caps_overpaid_replay_and_preserves_customer_payment(): void
    {
        $user = $this->loginAsAuthorizedAdmin();
        $oldDate = date('Y-m-d', strtotime('-4 days'));
        $today = date('Y-m-d');

        $this->seedPaidProductOnlyNote($oldDate);

        $response = $this->actingAs($user)->patch(
            route('admin.notes.workspace.update', ['noteId' => 'note-1']),
            [
                'note' => [
                    'customer_name' => 'Budi Capped Downward Revision',
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

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('notes', [
            'id' => 'note-1',
            'customer_name' => 'Budi Capped Downward Revision',
            'total_rupiah' => 200000,
        ]);

        $this->assertSame(
            200000,
            (int) DB::table('payment_component_allocations')
                ->where('note_id', 'note-1')
                ->where('customer_payment_id', 'payment-1')
                ->sum('allocated_amount_rupiah'),
        );

        $this->assertDatabaseHas('customer_payments', [
            'id' => 'payment-1',
            'amount_rupiah' => 300000,
        ]);

        $this->assertDatabaseMissing('payment_component_allocations', [
            'note_id' => 'note-1',
            'customer_payment_id' => 'payment-1',
            'allocated_amount_rupiah' => 300000,
        ]);
    }

    public function test_downward_replacement_commits_with_pending_surplus_settlement(): void
    {
        $user = $this->loginAsAuthorizedAdmin();
        $oldDate = date('Y-m-d', strtotime('-4 days'));
        $today = date('Y-m-d');

        $this->seedPaidProductOnlyNote($oldDate);

        $response = $this->actingAs($user)->patch(
            route('admin.notes.workspace.update', ['noteId' => 'note-1']),
            [
                'note' => [
                    'customer_name' => 'Budi Downward Surplus Revision',
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

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('notes', [
            'id' => 'note-1',
            'customer_name' => 'Budi Downward Surplus Revision',
            'total_rupiah' => 200000,
            'current_revision_id' => 'note-1-r002',
            'latest_revision_number' => 2,
        ]);

        $this->assertDatabaseHas('note_revisions', [
            'id' => 'note-1-r002',
            'note_root_id' => 'note-1',
            'revision_number' => 2,
            'customer_name' => 'Budi Downward Surplus Revision',
            'grand_total_rupiah' => 200000,
        ]);

        $this->assertDatabaseHas('note_revision_settlements', [
            'id' => 'note-1-r002-settlement',
            'note_revision_id' => 'note-1-r002',
            'note_root_id' => 'note-1',
            'gross_total_rupiah' => 200000,
            'carry_forward_paid_rupiah' => 300000,
            'carry_forward_refunded_rupiah' => 0,
            'net_paid_rupiah' => 300000,
            'outstanding_rupiah' => 0,
            'surplus_rupiah' => 100000,
            'settlement_status' => 'overpaid_pending',
        ]);

        $this->assertSame(
            200000,
            (int) DB::table('payment_component_allocations')
                ->where('note_id', 'note-1')
                ->where('customer_payment_id', 'payment-1')
                ->sum('allocated_amount_rupiah'),
        );

        $this->assertDatabaseHas('customer_payments', [
            'id' => 'payment-1',
            'amount_rupiah' => 300000,
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

        $this->seedCurrentRevision(
            'note-1',
            'note-1-r001',
            'Budi Product Lama',
            '08123456789',
            $oldDate,
            300000,
            [[
                'id' => 'note-1-r001-l001',
                'work_item_root_id' => 'wi-old-1',
                'line_no' => 1,
                'transaction_type' => WorkItem::TYPE_STORE_STOCK_SALE_ONLY,
                'status' => WorkItem::STATUS_OPEN,
                'service_label' => null,
                'service_price_rupiah' => 0,
                'subtotal_rupiah' => 300000,
                'payload' => [
                    'work_item_root_id' => 'wi-old-1',
                    'transaction_type' => WorkItem::TYPE_STORE_STOCK_SALE_ONLY,
                    'status' => WorkItem::STATUS_OPEN,
                    'external_purchase_lines' => [],
                    'store_stock_lines' => [[
                        'id' => 'ssl-old-1',
                        'product_id' => 'product-1',
                        'qty' => 3,
                        'line_total_rupiah' => 300000,
                    ]],
                    'service' => null,
                ],
            ]],
        );
    }
}
