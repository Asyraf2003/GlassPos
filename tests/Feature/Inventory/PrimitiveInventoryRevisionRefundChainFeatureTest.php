<?php

declare(strict_types=1);

namespace Tests\Feature\Inventory;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Support\BuildsPrimitiveLifecycleFixture;
use Tests\TestCase;

final class PrimitiveInventoryRevisionRefundChainFeatureTest extends TestCase
{
    use BuildsPrimitiveLifecycleFixture;
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_chain_d_exact_inventory_sources_costs_and_insufficient_stock_rollback(): void
    {
        $this->preparePrimitiveFixture();
        $cashier = $this->loginAsKasir();
        $payload = $this->primitiveWorkspace($this->primitiveItems(), 'chain-d-create');
        $payload['inline_payment'] = ['decision' => 'pay_partial', 'payment_method' => 'cash', 'paid_at' => '2026-09-15', 'amount_paid_rupiah' => 73129, 'amount_received_rupiah' => 100003];
        $this->post(route('notes.workspace.store'), $payload)->assertSessionHasNoErrors();
        $id = (string) DB::table('notes')->value('id');
        $pay = route('cashier.notes.payments.store', ['noteId' => $id]);
        $p1 = $this->stockLine('primitive-p');
        $q1 = $this->stockLine('primitive-q');
        $original = DB::table('inventory_movements')->where('source_type', 'work_item_store_stock_line')->orderBy('id')->get();
        $this->checkpoint($id, 395933, 73129, 0, 322804, [14, 21, 19]);
        $this->advancePrimitiveTime();
        $items = $this->primitiveItems();
        $items[0]['product_lines'][0]['qty'] = 2;
        $items[2]['product_lines'][0]['qty'] = 3;
        // Actual edit mapper emits this supported historical-package path.
        $items[2]['requires_service_product_template'] = false;
        $items[2]['historical_package_snapshot'] = true;
        $items[2]['package_total_rupiah'] = 127894;
        $this->patch(route('cashier.notes.workspace.update', ['noteId' => $id]), array_replace($this->primitiveWorkspace($items, 'chain-d-qty-revision'), ['base_revision_id' => $this->revisionBaseForTest($id)]))->assertSessionHasNoErrors();
        $p2 = $this->stockLine('primitive-p');
        $q2 = $this->stockLine('primitive-q');
        self::assertNotSame($p1, $p2);
        self::assertNotSame($q1, $q2);
        $this->checkpoint($id, 377057, 73129, 0, 303928, [15, 20, 19]);
        $this->advancePrimitiveTime();
        $stock = $this->primitiveRows('inventory_movements');
        $this->post($pay, $this->primitivePayment('chain-d-settle', 303928, 'transfer'))->assertSessionHasNoErrors();
        self::assertSame($stock, $this->primitiveRows('inventory_movements'));
        $this->checkpoint($id, 377057, 377057, 0, 0, [15, 20, 19]);
        $p2Row = (string) DB::table('work_item_store_stock_lines')->where('id', $p2)->value('work_item_id');
        $this->advancePrimitiveTime();
        $refund = ['selected_row_ids' => [$p2Row], 'refunded_at' => '2026-09-15', 'reason' => 'Chain D paid P2 return', 'idempotency_key' => 'chain-d-refund'];
        $refundRoute = route('cashier.notes.refunds.store', ['noteId' => $id]);
        $this->post($refundRoute, $refund)->assertSessionHasNoErrors();
        $this->checkpoint($id, 282031, 377057, 95026, 0, [17, 20, 19]);
        $admin = $this->loginAsAuthorizedAdmin();
        $this->advancePrimitiveTime();
        $items[0]['product_lines'][0]['qty'] = 1;
        $items[2]['product_lines'] = [['product_id' => 'primitive-r', 'qty' => 20, 'unit_price_rupiah' => 33571]];
        $items[2]['package_total_rupiah'] = 713403;
        $accessBefore = DB::table('audit_logs')->where('event', 'admin_transaction_capability_used')->count();
        $before = $this->evidence();
        $update = route('admin.notes.workspace.update', ['noteId' => $id]);
        $this->actingAs($admin)->patch($update, array_replace($this->primitiveWorkspace($items, 'chain-d-insufficient'), ['base_revision_id' => $this->revisionBaseForTest($id)]))->assertSessionHasErrors();
        self::assertSame($accessBefore + 1, DB::table('audit_logs')->where('event', 'admin_transaction_capability_used')->count());
        foreach ($this->evidence() as $table => $rows) {
            self::assertSame($before[$table], $rows, 'Insufficient R stock rollback: '.$table);
        }
        $items[2]['product_lines'][0]['qty'] = 2;
        $items[2]['package_total_rupiah'] = 109125;
        $valid = $this->primitiveWorkspace($items, 'chain-d-replacement');
        $valid['base_revision_id'] = $this->revisionBaseForTest($id);
        $this->patch($update, $valid)->assertSessionHasNoErrors();
        $p3 = $this->stockLine('primitive-p', $p2);
        $r3 = $this->stockLine('primitive-r');
        self::assertNotContains($p3, [$p1, $p2]);
        $this->checkpoint($id, 310775, 377057, 95026, 28744, [16, 23, 17]);
        $expected = [
            ['primitive-p', 'work_item_store_stock_line', $p1, -3, 19721, -59163],
            ['primitive-p', 'transaction_workspace_updated', $p1, 3, 19721, 59163],
            ['primitive-p', 'work_item_store_stock_line', $p2, -2, 19721, -39442],
            ['primitive-p', 'work_item_store_stock_line_reversal', $p2, 2, 19721, 39442],
            ['primitive-p', 'work_item_store_stock_line', $p3, -1, 19721, -19721],
            ['primitive-q', 'work_item_store_stock_line', $q1, -2, 11503, -23006],
            ['primitive-q', 'transaction_workspace_updated', $q1, 2, 11503, 23006],
            ['primitive-q', 'work_item_store_stock_line', $q2, -3, 11503, -34509],
            ['primitive-q', 'transaction_workspace_updated', $q2, 3, 11503, 34509],
            ['primitive-r', 'work_item_store_stock_line', $r3, -2, 13709, -27418],
        ];
        $actual = DB::table('inventory_movements')->where('source_type', '<>', 'seed_fixture')->get()->map(fn ($row) => [
            $row->product_id, $row->source_type, $row->source_id, (int) $row->qty_delta, (int) $row->unit_cost_rupiah, (int) $row->total_cost_rupiah,
        ])->all();
        self::assertEqualsCanonicalizing($expected, $actual);
        self::assertSame($original->toJson(), DB::table('inventory_movements')->whereIn('id', $original->pluck('id'))->orderBy('id')->get()->toJson());
        self::assertSame(47139, -(int) DB::table('inventory_movements')->where('source_type', '<>', 'seed_fixture')->sum('total_cost_rupiah'));
        foreach ([['primitive-p', 19721, 315536], ['primitive-q', 11503, 264569], ['primitive-r', 13709, 233053]] as [$product, $cost, $value]) {
            $this->assertDatabaseHas('product_inventory_costing', ['product_id' => $product, 'avg_cost_rupiah' => $cost, 'inventory_value_rupiah' => $value]);
        }
        $before = $this->evidence();
        $this->patch($update, $valid)->assertSessionHasNoErrors();
        self::assertSame($before, $this->evidence(), 'D5 same-key replay');
        $this->actingAs($cashier)->post($refundRoute, array_replace($refund, ['idempotency_key' => 'chain-d-stale']))->assertSessionHasErrors();
        self::assertSame($before, $this->evidence(), 'Refunded P2 cannot acquire current rights');
        $stock = $this->primitiveRows('inventory_movements');
        $this->advancePrimitiveTime();
        $this->post($pay, $this->primitivePayment('chain-d-partial', 11009, 'cash', 20003))->assertSessionHasNoErrors();
        $this->checkpoint($id, 310775, 388066, 95026, 17735, [16, 23, 17]);
        $this->assertDatabaseHas('customer_payment_cash_details', ['amount_paid_rupiah' => 11009, 'amount_received_rupiah' => 20003, 'change_rupiah' => 8994]);
        $this->advancePrimitiveTime();
        $this->post($pay, $this->primitivePayment('chain-d-final', 17735, 'cash', 20009))->assertSessionHasNoErrors();
        $this->checkpoint($id, 310775, 405801, 95026, 0, [16, 23, 17]);
        $this->assertDatabaseHas('customer_payment_cash_details', ['amount_paid_rupiah' => 17735, 'amount_received_rupiah' => 20009, 'change_rupiah' => 2274]);
        self::assertSame($stock, $this->primitiveRows('inventory_movements'));
        $this->assertDatabaseHas('notes', ['id' => $id, 'note_state' => 'closed']);
    }

    private function stockLine(string $product, ?string $exclude = null): string
    {
        return (string) DB::table('work_item_store_stock_lines')->where('product_id', $product)->when($exclude, fn ($q) => $q->where('id', '<>', $exclude))->value('id');
    }

    private function checkpoint(string $id, int $gross, int $paid, int $refund, int $debt, array $stock): void
    {
        $this->assertDatabaseHas('note_history_projection', ['note_id' => $id, 'total_rupiah' => $gross, 'outstanding_rupiah' => $debt]);
        self::assertSame($paid, (int) DB::table('customer_payments')->sum('amount_rupiah'));
        self::assertSame($refund, (int) DB::table('customer_refunds')->sum('amount_rupiah'));
        self::assertSame($stock, DB::table('product_inventory')->orderBy('product_id')->pluck('qty_on_hand')->map(fn ($v) => (int) $v)->all());
        $this->assertDatabaseCount('note_revision_surplus_dispositions', 0);
        $this->assertDatabaseCount('note_revision_surplus_refund_payments', 0);
    }

    private function evidence(): array
    {
        $result = [];
        foreach (['notes', 'work_items', 'work_item_store_stock_lines', 'work_item_external_purchase_lines', 'note_revisions', 'note_revision_lines', 'note_revision_settlements', 'customer_payments', 'customer_payment_cash_details', 'payment_component_allocations', 'customer_refunds', 'refund_component_allocations', 'inventory_movements', 'note_mutation_events', 'audit_logs', 'audit_events', 'audit_outbox'] as $table) {
            // Access capability usage is audited before the domain transaction, including rejection/replay.
            $result[$table] = $table === 'audit_logs'
                ? DB::table($table)->where('event', '<>', 'admin_transaction_capability_used')->orderBy('id')->get()->toJson()
                : $this->primitiveRows($table);
        }
        foreach (['product_inventory', 'product_inventory_costing'] as $table) {
            $result[$table] = DB::table($table)->orderBy('product_id')->get()->toJson();
        }
        return $result;
    }
}
