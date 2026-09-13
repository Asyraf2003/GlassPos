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
                    ->assertSee('14 Sep 2026', false)
                    ->assertSee('100.000', false)
                    ->assertSee($reason)
                    ->assertSee('Produk Refund History')
                    ->assertDontSee('Bayar Sebagian')
                    ->assertDontSee('Lunasi')
                    ->assertDontSee('Edit Nota')
                    ->assertDontSee('Pengembalian Dana Rincian Terpilih');
            }
        } finally {
            Carbon::setTestNow();
        }
    }
}
