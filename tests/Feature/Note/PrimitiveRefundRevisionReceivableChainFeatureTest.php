<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Support\BuildsPrimitiveLifecycleFixture;
use Tests\TestCase;

final class PrimitiveRefundRevisionReceivableChainFeatureTest extends TestCase
{
    use BuildsPrimitiveLifecycleFixture;
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_chain_b_split_refund_two_revisions_surplus_and_second_settlement(): void
    {
        $this->preparePrimitiveFixture();
        $cashier = $this->loginAsKasir();
        $payload = $this->primitiveWorkspace($this->primitiveItems(), 'chain-b-create');
        $payload['inline_payment'] = ['decision' => 'pay_partial', 'payment_method' => 'cash', 'paid_at' => '2026-09-15', 'amount_paid_rupiah' => 73129, 'amount_received_rupiah' => 100003];
        $this->post(route('notes.workspace.store'), $payload)->assertSessionHasNoErrors();
        $noteId = (string) DB::table('notes')->value('id');
        $paymentRoute = route('cashier.notes.payments.store', ['noteId' => $noteId]);
        $p1 = (string) DB::table('customer_payments')->value('id');
        $this->projection($noteId, 395933, 73129, 322804);
        $this->advancePrimitiveTime();
        $this->post($paymentRoute, $this->primitivePayment('chain-b-dp2', 89457, 'transfer'))->assertSessionHasNoErrors();
        $p2 = (string) DB::table('customer_payments')->where('id', '<>', $p1)->value('id');
        $this->projection($noteId, 395933, 162586, 233347);
        $this->advancePrimitiveTime();
        $this->post($paymentRoute, $this->primitivePayment('chain-b-full', 233347, 'cash', 250009))->assertSessionHasNoErrors();
        $p3 = (string) DB::table('customer_payments')->whereNotIn('id', [$p1, $p2])->value('id');
        $this->projection($noteId, 395933, 395933, 0);
        $payments = $this->primitiveRows('customer_payments');
        $cash = $this->primitiveRows('customer_payment_cash_details');
        $r1 = (string) DB::table('notes')->value('current_revision_id');
        $r1Rows = $this->primitiveRows('note_revision_lines');
        $oldP = (string) DB::table('work_items')->where('transaction_type', 'store_stock_sale_only')->value('id');
        $oldPart = (string) DB::table('work_item_store_stock_lines')->where('work_item_id', $oldP)->value('id');
        $this->advancePrimitiveTime();
        $refundRoute = route('cashier.notes.refunds.store', ['noteId' => $noteId]);
        $refund = ['selected_row_ids' => [$oldP], 'refunded_at' => '2026-09-15', 'reason' => 'Chain B one logical refund', 'idempotency_key' => 'chain-b-refund'];
        $this->post($refundRoute, $refund)->assertSessionHasNoErrors();
        $this->projection($noteId, 253394, 253394, 0);
        $this->assertDatabaseCount('customer_refunds', 3);
        $this->assertDatabaseCount('refund_component_allocations', 3);
        foreach ([$p1 => 20002, $p2 => 89457, $p3 => 33080] as $paymentId => $amount) {
            $this->assertDatabaseHas('customer_refunds', ['customer_payment_id' => $paymentId, 'amount_rupiah' => $amount]);
            $this->assertDatabaseHas('refund_component_allocations', ['customer_payment_id' => $paymentId, 'work_item_id' => $oldP, 'component_ref_id' => $oldP, 'refunded_amount_rupiah' => $amount]);
        }
        self::assertSame(142539, (int) DB::table('refund_component_allocations')->sum('refunded_amount_rupiah'));
        self::assertSame(1, DB::table('inventory_movements')->where('reversal_source_id', $oldPart)->count());
        $this->assertDatabaseHas('product_inventory', ['product_id' => 'primitive-p', 'qty_on_hand' => 17]);
        $record = DB::table('idempotency_records')->where('operation', 'record_selected_rows_refund')->where('idempotency_key', 'chain-b-refund')->first();
        self::assertNotNull($record);
        $receipt = json_decode($record->result_payload_json, true, flags: JSON_THROW_ON_ERROR);
        self::assertEqualsCanonicalizing(DB::table('customer_refunds')->pluck('id')->all(), $receipt['data']['refund_ids']);
        $refundRows = $this->primitiveRows('customer_refunds');
        $refundAllocations = $this->primitiveRows('refund_component_allocations');
        $movements = $this->primitiveRows('inventory_movements');
        $this->post($refundRoute, $refund)->assertSessionHasNoErrors();
        self::assertSame($refundRows, $this->primitiveRows('customer_refunds'));
        self::assertSame($movements, $this->primitiveRows('inventory_movements'));
        $this->post($refundRoute, array_replace($refund, ['idempotency_key' => 'chain-b-old-target']))->assertSessionHasErrors();
        self::assertSame($refundRows, $this->primitiveRows('customer_refunds'));

        $admin = $this->loginAsAuthorizedAdmin();
        $this->advancePrimitiveTime();
        $items = array_slice($this->primitiveItems(51983), 1);
        $this->actingAs($admin)->patch(route('admin.notes.workspace.update', ['noteId' => $noteId]), $this->primitiveWorkspace($items, 'chain-b-downward'))->assertSessionHasNoErrors();
        $this->projection($noteId, 241658, 241658, 0);
        self::assertSame(241658, (int) DB::table('payment_component_allocations')->sum('allocated_amount_rupiah'));
        $this->assertDatabaseCount('note_revision_surplus_dispositions', 1);
        $this->assertDatabaseCount('note_revision_surplus_refund_payments', 1);
        $this->assertDatabaseHas('note_revision_surplus_dispositions', ['amount_rupiah' => 11736]);
        $this->assertDatabaseHas('note_revision_surplus_refund_payments', ['amount_rupiah' => 11736]);
        $this->assertDatabaseHas('note_revision_surplus_refund_payments', ['note_revision_surplus_disposition_id' => DB::table('note_revision_surplus_dispositions')->value('id'), 'amount_rupiah' => 11736]);
        $surplusDue = $this->primitiveRows('note_revision_surplus_dispositions');
        $surplusPaid = $this->primitiveRows('note_revision_surplus_refund_payments');
        $r2 = (string) DB::table('notes')->value('current_revision_id');
        $this->assertDatabaseHas('note_revision_settlements', ['note_revision_id' => $r2, 'settlement_status' => 'overpaid_pending', 'surplus_rupiah' => 11736]);
        $r2Rows = DB::table('note_revision_lines')->where('note_revision_id', $r2)->orderBy('id')->get()->toJson();
        $oldActive = DB::table('work_items')->where('status', '<>', 'canceled')->pluck('id')->all();
        $this->advancePrimitiveTime();
        $items = $this->primitiveItems(70211);
        $reordered = [$items[3], $items[2], $items[1], ['entry_mode' => 'product', 'product_lines' => [['product_id' => 'primitive-r', 'qty' => 5, 'unit_price_rupiah' => 33571]]]];
        $this->patch(route('admin.notes.workspace.update', ['noteId' => $noteId]), $this->primitiveWorkspace($reordered, 'chain-b-upward'))->assertSessionHasNoErrors();
        $this->projection($noteId, 427741, 241658, 186083);
        self::assertSame(241658, (int) DB::table('payment_component_allocations')->sum('allocated_amount_rupiah'));
        $this->assertDatabaseHas('notes', ['id' => $noteId, 'note_state' => 'open']);
        self::assertSame(0, DB::table('payment_component_allocations')->whereIn('work_item_id', [...$oldActive, $oldP])->count());
        $this->assertDatabaseCount('note_revisions', 3);
        $this->assertDatabaseHas('note_revisions', ['id' => DB::table('notes')->value('current_revision_id'), 'parent_revision_id' => $r2]);
        self::assertSame($r1Rows, DB::table('note_revision_lines')->where('note_revision_id', $r1)->orderBy('id')->get()->toJson());
        self::assertSame($r2Rows, DB::table('note_revision_lines')->where('note_revision_id', $r2)->orderBy('id')->get()->toJson());
        $this->advancePrimitiveTime();
        $this->actingAs($cashier)->post($paymentRoute, $this->primitivePayment('chain-b-new-dp', 27119, 'transfer'))->assertSessionHasNoErrors();
        $this->projection($noteId, 427741, 268777, 158964);
        self::assertSame(268777, (int) DB::table('payment_component_allocations')->sum('allocated_amount_rupiah'));
        $this->assertDatabaseHas('notes', ['id' => $noteId, 'note_state' => 'open']);
        $this->advancePrimitiveTime();
        $this->post($paymentRoute, $this->primitivePayment('chain-b-second-close', 158964, 'cash', 170003))->assertSessionHasNoErrors();
        $this->projection($noteId, 427741, 427741, 0);
        self::assertSame(427741, (int) DB::table('payment_component_allocations')->sum('allocated_amount_rupiah'));
        self::assertSame(582016, (int) DB::table('customer_payments')->sum('amount_rupiah'));
        $this->assertDatabaseCount('customer_payments', 5);
        $this->assertDatabaseCount('customer_payment_cash_details', 3);
        $this->assertDatabaseHas('customer_payment_cash_details', ['amount_paid_rupiah' => 158964, 'amount_received_rupiah' => 170003, 'change_rupiah' => 11039]);
        $this->assertDatabaseHas('notes', ['id' => $noteId, 'note_state' => 'closed']);
        self::assertSame(2, DB::table('note_mutation_events')->where('mutation_type', 'note_closed')->count());
        self::assertSame(1, DB::table('note_mutation_events')->where('mutation_type', 'note_reopened')->count());
        self::assertSame($payments, DB::table('customer_payments')->whereIn('id', [$p1, $p2, $p3])->orderBy('id')->get()->toJson());
        self::assertSame($cash, DB::table('customer_payment_cash_details')->whereIn('customer_payment_id', [$p1, $p3])->orderBy('customer_payment_id')->get()->toJson());
        self::assertSame($surplusDue, $this->primitiveRows('note_revision_surplus_dispositions'));
        self::assertSame($surplusPaid, $this->primitiveRows('note_revision_surplus_refund_payments'));
        self::assertSame($refundRows, $this->primitiveRows('customer_refunds'));
        self::assertSame($refundAllocations, $this->primitiveRows('refund_component_allocations'));
        self::assertSame(1, DB::table('inventory_movements')->where('reversal_source_id', $oldPart)->count());
        $this->assertDatabaseHas('work_items', ['id' => $oldP, 'status' => 'canceled']);
        self::assertSame(0, DB::table('payment_component_allocations')->where('work_item_id', $oldP)->count());
    }

    private function projection(string $noteId, int $gross, int $paid, int $outstanding): void
    {
        $this->assertDatabaseHas('note_history_projection', ['note_id' => $noteId, 'total_rupiah' => $gross, 'net_paid_rupiah' => $paid, 'outstanding_rupiah' => $outstanding]);
    }
}
