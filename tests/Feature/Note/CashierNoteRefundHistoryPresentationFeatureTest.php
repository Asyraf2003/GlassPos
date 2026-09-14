<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsMinimalProductFixture;
use Tests\TestCase;

final class CashierNoteRefundHistoryPresentationFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalProductFixture;

    public function test_full_product_refund_refresh_exposes_ledger_history_on_desktop_and_handset(): void
    {
        Carbon::setTestNow('2026-09-14 09:00:00');

        try {
            $cashier = $this->loginAsKasir();
            $this->seedMinimalProduct('refund-history-product', 'RH-001', 'Produk Refund History', 'Test', null, 100000);
            DB::table('product_inventory')->insert([
                'product_id' => 'refund-history-product',
                'qty_on_hand' => 5,
            ]);
            DB::table('product_inventory_costing')->insert([
                'product_id' => 'refund-history-product',
                'avg_cost_rupiah' => 40000,
                'inventory_value_rupiah' => 200000,
            ]);

            $this->actingAs($cashier)
                ->post(route('notes.workspace.store'), [
                    'idempotency_key' => 'refund-history-create',
                    'note' => [
                        'customer_name' => 'Refund History UI',
                        'transaction_date' => '2026-09-14',
                    ],
                    'items' => [[
                        'entry_mode' => 'product',
                        'product_lines' => [[
                            'product_id' => 'refund-history-product',
                            'qty' => 1,
                            'unit_price_rupiah' => 100000,
                        ]],
                    ]],
                    'inline_payment' => [
                        'decision' => 'pay_full',
                        'payment_method' => 'cash',
                        'paid_at' => '2026-09-14',
                        'amount_paid_rupiah' => 100000,
                        'amount_received_rupiah' => 100000,
                    ],
                ])
                ->assertSessionHasNoErrors();

            $noteId = (string) DB::table('notes')->value('id');
            $rowId = (string) DB::table('work_items')->where('note_id', $noteId)->value('id');
            $reason = 'Refund history harus terlihat setelah reload';

            $this->actingAs($cashier)
                ->post(route('cashier.notes.refunds.store', ['noteId' => $noteId]), [
                    'selected_row_ids' => [$rowId],
                    'refunded_at' => '2026-09-14',
                    'reason' => $reason,
                    'idempotency_key' => 'refund-history-refund',
                ])
                ->assertSessionHasNoErrors();

            foreach (['?0' => 'desktop', '?1' => 'handset'] as $mobileHeader => $device) {
                $response = $this->actingAs($cashier)
                    ->withHeaders(['Sec-CH-UA-Mobile' => $mobileHeader])
                    ->get(route('cashier.notes.show', ['noteId' => $noteId]))
                    ->assertOk();

                $note = $response->viewData('note');
                $timeline = $note['refund_timeline'];

                self::assertCount(1, $timeline, $device);
                self::assertSame('2026-09-14', $timeline[0]['refunded_at'], $device);
                self::assertSame(100000, $timeline[0]['amount_rupiah'], $device);
                self::assertSame($reason, $timeline[0]['reason'], $device);
                self::assertCount(1, $timeline[0]['components'], $device);
                self::assertSame($rowId, $timeline[0]['components'][0]['work_item_id'], $device);
                self::assertSame('product_only_work_item', $timeline[0]['components'][0]['component_type'], $device);
                self::assertSame(100000, $timeline[0]['components'][0]['refunded_amount_rupiah'], $device);
                self::assertSame('Produk Refund History', $timeline[0]['components'][0]['label'], $device);

                self::assertSame(0, $note['outstanding_rupiah'], $device);
                self::assertFalse($note['can_show_payment_form'], $device);
                self::assertFalse($note['can_show_partial_payment_action'], $device);
                self::assertFalse($note['can_show_settle_payment_action'], $device);
                self::assertFalse($note['can_edit_workspace'], $device);
                self::assertFalse($note['can_show_refund_form'], $device);

                $response
                    ->assertSee('Riwayat Pengembalian Dana')
                    ->assertSee('14 September 2026', false)
                    ->assertSee('100.000', false)
                    ->assertSee($reason)
                    ->assertSee('Produk Refund History')
                    ->assertDontSee('Bayar Sebagian')
                    ->assertDontSee('Lunasi')
                    ->assertDontSee('Edit Nota')
                    ->assertDontSee('id="note-refund-open-button"', false);
            }
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_package_component_refund_refresh_keeps_service_current_and_product_historical(): void
    {
        Carbon::setTestNow('2026-09-14 10:00:00');

        try {
            $cashier = $this->loginAsKasir();
            $this->seedMinimalProduct('package-refund-product', 'PR-001', 'Sparepart Refund Package', 'Test', null, 100000);
            DB::table('product_inventory')->insert([
                'product_id' => 'package-refund-product',
                'qty_on_hand' => 5,
            ]);
            DB::table('product_inventory_costing')->insert([
                'product_id' => 'package-refund-product',
                'avg_cost_rupiah' => 40000,
                'inventory_value_rupiah' => 200000,
            ]);

            $this->actingAs($cashier)
                ->post(route('notes.workspace.store'), [
                    'idempotency_key' => 'package-refund-create',
                    'note' => [
                        'customer_name' => 'Package Refund UI',
                        'transaction_date' => '2026-09-14',
                    ],
                    'items' => [[
                        'entry_mode' => 'service',
                        'part_source' => 'store_stock',
                        'pricing_mode' => 'manual_split',
                        'service' => [
                            'name' => 'Servis Tetap Aktif',
                            'price_rupiah' => 50000,
                        ],
                        'product_lines' => [[
                            'product_id' => 'package-refund-product',
                            'qty' => 1,
                            'unit_price_rupiah' => 100000,
                        ]],
                    ]],
                    'inline_payment' => [
                        'decision' => 'pay_full',
                        'payment_method' => 'cash',
                        'paid_at' => '2026-09-14',
                        'amount_paid_rupiah' => 150000,
                        'amount_received_rupiah' => 150000,
                    ],
                ])
                ->assertSessionHasNoErrors();

            $noteId = (string) DB::table('notes')->value('id');
            $rowId = (string) DB::table('work_items')->where('note_id', $noteId)->value('id');
            $stockLineId = (string) DB::table('work_item_store_stock_lines')
                ->where('work_item_id', $rowId)
                ->value('id');
            $reason = 'Refund sparepart package, jasa tetap sah';

            $this->actingAs($cashier)
                ->post(route('cashier.notes.refunds.store', ['noteId' => $noteId]), [
                    'selected_row_ids' => [$rowId],
                    'refunded_at' => '2026-09-14',
                    'reason' => $reason,
                    'idempotency_key' => 'package-refund-component',
                ])
                ->assertSessionHasNoErrors();

            $this->assertDatabaseHas('customer_refunds', [
                'note_id' => $noteId,
                'amount_rupiah' => 100000,
                'reason' => $reason,
            ]);
            $this->assertDatabaseHas('refund_component_allocations', [
                'note_id' => $noteId,
                'work_item_id' => $rowId,
                'component_type' => 'service_store_stock_part',
                'component_ref_id' => $stockLineId,
                'refunded_amount_rupiah' => 100000,
            ]);

            foreach (['?0' => 'desktop', '?1' => 'handset'] as $mobileHeader => $device) {
                $response = $this->actingAs($cashier)
                    ->withHeaders(['Sec-CH-UA-Mobile' => $mobileHeader])
                    ->get(route('cashier.notes.show', ['noteId' => $noteId]))
                    ->assertOk();

                $note = $response->viewData('note');
                self::assertCount(1, $note['refund_timeline'], $device);
                self::assertSame(100000, $note['refund_timeline'][0]['amount_rupiah'], $device);
                self::assertSame($reason, $note['refund_timeline'][0]['reason'], $device);
                self::assertSame('service_store_stock_part', $note['refund_timeline'][0]['components'][0]['component_type'], $device);
                self::assertSame($stockLineId, $note['refund_timeline'][0]['components'][0]['component_ref_id'], $device);
                self::assertSame('Sparepart Refund Package', $note['refund_timeline'][0]['components'][0]['label'], $device);

                self::assertCount(1, $note['rows'], $device);
                self::assertSame('Servis Tetap Aktif', $note['rows'][0]['line_label'], $device);
                self::assertSame('refund', $note['rows'][0]['line_status'], $device);
                self::assertSame(100000, $note['rows'][0]['refunded_rupiah'], $device);
                self::assertSame(50000, $note['rows'][0]['net_paid_rupiah'], $device);
                self::assertSame(0, $note['rows'][0]['outstanding_rupiah'], $device);

                self::assertCount(1, $note['billing_rows'], $device);
                self::assertSame('service_fee', $note['billing_rows'][0]['component_type'], $device);
                self::assertSame($rowId, $note['billing_rows'][0]['work_item_id'], $device);
                self::assertSame(0, $note['billing_rows'][0]['outstanding_rupiah'], $device);

                self::assertSame(0, $note['outstanding_rupiah'], $device);
                self::assertFalse($note['can_show_payment_form'], $device);
                self::assertFalse($note['can_show_partial_payment_action'], $device);
                self::assertFalse($note['can_show_settle_payment_action'], $device);
                self::assertFalse($note['can_edit_workspace'], $device);
                self::assertFalse($note['can_show_refund_form'], $device);

                $response
                    ->assertSee('Riwayat Pengembalian Dana')
                    ->assertSee('Sparepart Refund Package')
                    ->assertSee('Servis Tetap Aktif')
                    ->assertSee($reason)
                    ->assertDontSee('Bayar Sebagian')
                    ->assertDontSee('Lunasi')
                    ->assertDontSee('Edit Nota')
                    ->assertDontSee('id="note-refund-open-button"', false);
            }
        } finally {
            Carbon::setTestNow();
        }
    }


    public function test_refund_then_revision_refresh_keeps_refund_historical_and_new_revision_current(): void
    {
        Carbon::setTestNow('2026-09-14 11:00:00');

        try {
            $admin = $this->loginAsAuthorizedAdmin();
            $noteId = 'note-refund-ui-revision';
            $oldWorkItemId = 'wi-refund-ui-revision-old';
            $oldStoreLineId = 'ssl-refund-ui-revision-old';

            $this->seedMinimalProduct(
                'refund-ui-revision-product',
                'RUR-001',
                'Produk Refund Historis',
                'Test',
                null,
                100000,
            );

            DB::table('notes')->insert([
                'id' => $noteId,
                'customer_name' => 'Sebelum Revision UI',
                'customer_phone' => null,
                'transaction_date' => '2026-09-13',
                'note_state' => 'closed',
                'total_rupiah' => 300000,
                'current_revision_id' => $noteId.'-r001',
                'latest_revision_number' => 1,
            ]);

            DB::table('work_items')->insert([
                'id' => $oldWorkItemId,
                'note_id' => $noteId,
                'line_no' => 1,
                'transaction_type' => 'store_stock_sale_only',
                'status' => 'open',
                'subtotal_rupiah' => 300000,
            ]);

            DB::table('work_item_store_stock_lines')->insert([
                'id' => $oldStoreLineId,
                'work_item_id' => $oldWorkItemId,
                'product_id' => 'refund-ui-revision-product',
                'qty' => 3,
                'line_total_rupiah' => 300000,
            ]);

            DB::table('note_revisions')->insert([
                'id' => $noteId.'-r001',
                'note_root_id' => $noteId,
                'revision_number' => 1,
                'parent_revision_id' => null,
                'created_by_actor_id' => null,
                'reason' => 'Initial UI revision fixture',
                'customer_name' => 'Sebelum Revision UI',
                'customer_phone' => null,
                'transaction_date' => '2026-09-13',
                'grand_total_rupiah' => 300000,
                'line_count' => 1,
                'created_at' => '2026-09-13 09:00:00',
                'updated_at' => null,
            ]);

            DB::table('note_revision_lines')->insert([
                'id' => $noteId.'-r001-l001',
                'note_revision_id' => $noteId.'-r001',
                'work_item_root_id' => $oldWorkItemId,
                'line_no' => 1,
                'transaction_type' => 'store_stock_sale_only',
                'status' => 'open',
                'service_label' => null,
                'service_price_rupiah' => null,
                'subtotal_rupiah' => 300000,
                'payload' => json_encode([
                    'work_item_root_id' => $oldWorkItemId,
                    'transaction_type' => 'store_stock_sale_only',
                    'status' => 'open',
                    'external_purchase_lines' => [],
                    'store_stock_lines' => [[
                        'id' => $oldStoreLineId,
                        'product_id' => 'refund-ui-revision-product',
                        'qty' => 3,
                        'line_total_rupiah' => 300000,
                    ]],
                ], JSON_THROW_ON_ERROR),
                'created_at' => '2026-09-13 09:00:00',
                'updated_at' => null,
            ]);

            DB::table('customer_payments')->insert([
                'id' => 'payment-refund-ui-revision',
                'amount_rupiah' => 300000,
                'paid_at' => '2026-09-13',
                'payment_method' => 'cash',
            ]);
            DB::table('payment_allocations')->insert([
                'id' => 'payment-allocation-refund-ui-revision',
                'customer_payment_id' => 'payment-refund-ui-revision',
                'note_id' => $noteId,
                'amount_rupiah' => 300000,
            ]);
            DB::table('payment_component_allocations')->insert([
                'id' => 'pca-refund-ui-revision-old',
                'customer_payment_id' => 'payment-refund-ui-revision',
                'note_id' => $noteId,
                'work_item_id' => $oldWorkItemId,
                'component_type' => 'product_only_work_item',
                'component_ref_id' => $oldWorkItemId,
                'component_amount_rupiah_snapshot' => 300000,
                'allocated_amount_rupiah' => 300000,
                'allocation_priority' => 1,
            ]);

            DB::table('customer_refunds')->insert([
                'id' => 'refund-refund-ui-revision',
                'customer_payment_id' => 'payment-refund-ui-revision',
                'note_id' => $noteId,
                'amount_rupiah' => 100000,
                'refunded_at' => '2026-09-13',
                'reason' => 'Refund historis sebelum revision UI',
            ]);
            DB::table('refund_component_allocations')->insert([
                'id' => 'rca-refund-ui-revision-old',
                'customer_refund_id' => 'refund-refund-ui-revision',
                'customer_payment_id' => 'payment-refund-ui-revision',
                'note_id' => $noteId,
                'work_item_id' => $oldWorkItemId,
                'component_type' => 'product_only_work_item',
                'component_ref_id' => $oldWorkItemId,
                'refunded_amount_rupiah' => 100000,
                'refund_priority' => 1,
            ]);

            $this->actingAs($admin)
                ->patch(route('admin.notes.workspace.update', ['noteId' => $noteId]), [
                    'note' => [
                        'customer_name' => 'Sesudah Revision UI',
                        'customer_phone' => '08123456789',
                        'transaction_date' => '2026-09-14',
                    ],
                    'items' => [[
                        'entry_mode' => 'service',
                        'description' => null,
                        'part_source' => 'none',
                        'service' => [
                            'name' => 'Servis Revision Baru',
                            'price_rupiah' => 250000,
                            'notes' => null,
                        ],
                        'product_lines' => [],
                        'external_purchase_lines' => [],
                    ]],
                    'inline_payment' => [
                        'decision' => 'skip',
                    ],
                ])
                ->assertRedirect(route('admin.notes.show', ['noteId' => $noteId]))
                ->assertSessionHasNoErrors();

            $newWorkItemId = (string) DB::table('work_items')
                ->where('note_id', $noteId)
                ->where('id', '!=', $oldWorkItemId)
                ->value('id');

            self::assertNotSame('', $newWorkItemId);

            foreach (['?0' => 'desktop', '?1' => 'handset'] as $mobileHeader => $device) {
                $response = $this->actingAs($admin)
                    ->withHeaders(['Sec-CH-UA-Mobile' => $mobileHeader])
                    ->get(route('admin.notes.show', ['noteId' => $noteId]))
                    ->assertOk();

                $note = $response->viewData('note');

                self::assertCount(1, $note['refund_timeline'], $device);
                self::assertSame(100000, $note['refund_timeline'][0]['amount_rupiah'], $device);
                self::assertSame('Refund historis sebelum revision UI', $note['refund_timeline'][0]['reason'], $device);
                self::assertSame($oldWorkItemId, $note['refund_timeline'][0]['components'][0]['work_item_id'], $device);
                self::assertSame('Produk Refund Historis', $note['refund_timeline'][0]['components'][0]['label'], $device);

                self::assertCount(1, $note['rows'], $device);
                self::assertSame($newWorkItemId, $note['rows'][0]['id'], $device);
                self::assertSame('Servis Revision Baru', $note['rows'][0]['line_label'], $device);
                self::assertNotSame($oldWorkItemId, $note['rows'][0]['id'], $device);

                self::assertSame(250000, $note['grand_total_rupiah'], $device);
                self::assertSame(200000, $note['net_paid_rupiah'], $device);
                self::assertSame(50000, $note['outstanding_rupiah'], $device);
                self::assertTrue($note['can_show_payment_form'], $device);
                self::assertTrue($note['can_show_partial_payment_action'], $device);
                self::assertTrue($note['can_show_settle_payment_action'], $device);
                self::assertTrue($note['can_edit_workspace'], $device);
                self::assertFalse($note['can_show_refund_form'], $device);

                $response
                    ->assertSee('Riwayat Pengembalian Dana')
                    ->assertSee('Produk Refund Historis')
                    ->assertSee('Refund historis sebelum revision UI')
                    ->assertSee('Servis Revision Baru')
                    ->assertSee('50.000', false)
                    ->assertSee('Bayar Sebagian')
                    ->assertSee('Lunasi')
                    ->assertSee('Edit Nota')
                    ->assertDontSee('id="note-refund-open-button"', false);
            }
        } finally {
            Carbon::setTestNow();
        }
    }

}
