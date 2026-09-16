<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsMinimalProductFixture;
use Tests\TestCase;

final class PrimitiveMutationBoundaryCharacterizationTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalProductFixture;

    public function test_paid_status_correction_cannot_cancel_even_after_operational_reopen(): void
    {
        $this->loginAsAuthorizedAdmin();
        $date = date('Y-m-d');
        $this->seedMinimalProduct('boundary-product', 'BOUND-P', 'Paid boundary product', 'QA', null, 47513);
        DB::table('product_inventory')->insert(['product_id' => 'boundary-product', 'qty_on_hand' => 17]);
        DB::table('product_inventory_costing')->insert([
            'product_id' => 'boundary-product', 'avg_cost_rupiah' => 19721, 'inventory_value_rupiah' => 335257,
        ]);
        DB::table('inventory_movements')->insert([
            'id' => 'boundary-opening', 'product_id' => 'boundary-product', 'movement_type' => 'stock_in',
            'source_type' => 'test_opening_balance', 'source_id' => 'boundary-opening', 'tanggal_mutasi' => $date,
            'qty_delta' => 17, 'unit_cost_rupiah' => 19721, 'total_cost_rupiah' => 335257,
        ]);
        $this->post(route('notes.workspace.store'), [
            'idempotency_key' => 'boundary-paid-create',
            'note' => ['customer_name' => 'Paid cancel boundary', 'transaction_date' => $date],
            'items' => [
                ['entry_mode' => 'product', 'product_lines' => [
                    ['product_id' => 'boundary-product', 'qty' => 1, 'unit_price_rupiah' => 47513],
                ]],
                ['entry_mode' => 'service', 'service' => ['name' => 'Paid service', 'price_rupiah' => 63719]],
            ],
            'inline_payment' => ['decision' => 'pay_full', 'payment_method' => 'cash', 'paid_at' => $date,
                'amount_paid_rupiah' => 111232, 'amount_received_rupiah' => 120003],
        ])->assertRedirect()->assertSessionHasNoErrors();
        $noteId = (string) DB::table('notes')->value('id');
        $lineNo = (int) DB::table('work_items')->where('transaction_type', 'store_stock_sale_only')->value('line_no');
        $payload = ['line_no' => $lineNo, 'target_status' => 'canceled', 'reason' => 'Slice 2a paid cancel probe'];
        $beforeClosedAttempt = $this->effects();
        $this->post(route('cashier.notes.corrections.status.store', ['noteId' => $noteId]), $payload)->assertForbidden();
        self::assertSame($beforeClosedAttempt, $this->effects());

        // Supported admin reopen changes operational state, not the accepted settlement.
        $this->post(route('admin.notes.reopen', ['noteId' => $noteId]), ['reason' => 'Inspect paid correction boundary'])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('notes', ['id' => $noteId, 'note_state' => 'open']);
        self::assertSame(111232, (int) DB::table('customer_payments')->sum('amount_rupiah'));
        self::assertSame(111232, (int) DB::table('payment_component_allocations')->sum('allocated_amount_rupiah'));
        $before = $this->effects();
        $response = $this->from(route('cashier.notes.show', ['noteId' => $noteId]))
            ->post(route('cashier.notes.corrections.status.store', ['noteId' => $noteId]), $payload);
        $after = $this->effects();
        self::assertSame($before, $after, 'Paid cancellation must reject without money, stock, history or projection mutation.');
        $response->assertRedirect()->assertSessionHasErrors('correction');
    }

    private function effects(): array
    {
        $result = [];
        foreach (['notes', 'work_items', 'work_item_store_stock_lines', 'note_revisions', 'note_revision_lines',
            'customer_payments', 'customer_refunds', 'payment_component_allocations', 'refund_component_allocations',
            'inventory_movements', 'note_mutation_events', 'note_revision_surplus_dispositions',
            'note_revision_surplus_refund_payments', 'audit_logs'] as $table) {
            $result[$table] = DB::table($table)->orderBy('id')->get()->map(static fn ($row): array => (array) $row)->all();
        }
        foreach (['product_inventory', 'product_inventory_costing'] as $table) {
            $result[$table] = DB::table($table)->orderBy('product_id')->get()->map(static fn ($row): array => (array) $row)->all();
        }
        $result['projection'] = DB::table('note_history_projection')->orderBy('note_id')->get()->toJson();

        return $result;
    }
}
