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
        $admin = $this->loginAsAuthorizedAdmin();
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
        $cashier = $this->loginAsKasir();
        $beforeClosedAttempt = $this->effects();
        $this->post(route('cashier.notes.corrections.status.store', ['noteId' => $noteId]), $payload)->assertForbidden();
        self::assertSame($beforeClosedAttempt, $this->effects());

        // Supported admin reopen changes operational state, not the accepted settlement.
        $this->actingAs($admin)->post(route('admin.notes.reopen', ['noteId' => $noteId]), ['reason' => 'Inspect paid correction boundary'])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('notes', ['id' => $noteId, 'note_state' => 'open']);
        self::assertSame(111232, (int) DB::table('customer_payments')->sum('amount_rupiah'));
        self::assertSame(111232, (int) DB::table('payment_component_allocations')->sum('allocated_amount_rupiah'));
        $before = $this->effects();
        $response = $this->actingAs($cashier)->from(route('cashier.notes.show', ['noteId' => $noteId]))
            ->post(route('cashier.notes.corrections.status.store', ['noteId' => $noteId]), $payload);
        $after = $this->effects();
        self::assertSame($before, $after, 'Paid cancellation must reject without money, stock, history or projection mutation.');
        $response->assertRedirect()->assertSessionHasErrors('correction');
    }

    public function test_nominal_correction_must_not_report_success_without_a_new_revision(): void
    {
        $admin = $this->loginAsAuthorizedAdmin();
        $date = date('Y-m-d');
        $this->post(route('notes.workspace.store'), [
            'idempotency_key' => 'nominal-boundary-create',
            'note' => ['customer_name' => 'Nominal correction boundary', 'transaction_date' => $date],
            'items' => [['entry_mode' => 'service', 'service' => ['name' => 'Paid service', 'price_rupiah' => 63719]]],
            'inline_payment' => ['decision' => 'pay_full', 'payment_method' => 'cash', 'paid_at' => $date,
                'amount_paid_rupiah' => 63719, 'amount_received_rupiah' => 70003],
        ])->assertRedirect()->assertSessionHasNoErrors();
        $noteId = (string) DB::table('notes')->value('id');
        $this->actingAs($admin)->post(route('admin.notes.reopen', ['noteId' => $noteId]), ['reason' => 'Inspect nominal correction boundary'])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->loginAsKasir();
        $before = $this->effects();
        $this->post(route('cashier.notes.corrections.service-only.store', ['noteId' => $noteId]), [
            'base_revision_id' => $this->revisionBaseForTest($noteId),
            'line_no' => 1, 'service_name' => 'Paid service', 'service_price_rupiah' => 61987,
            'part_source' => 'none', 'reason' => 'Slice 2b price correction 1732',
        ])->assertRedirect()->assertSessionHasNoErrors()->assertSessionHas('success');
        $after = $this->effects();
        self::assertSame($before['customer_payments'], $after['customer_payments']);
        self::assertSame($before['customer_refunds'], $after['customer_refunds']);
        self::assertSame($before['inventory_movements'], $after['inventory_movements']);
        $evidence = [
            'before_revision' => $before['notes'][0]['current_revision_id'],
            'after_revision' => $after['notes'][0]['current_revision_id'],
            'root_total' => $after['notes'][0]['total_rupiah'],
            'work_item_before' => $before['work_items'], 'work_item_after' => $after['work_items'],
            'revision_rows' => $after['note_revisions'],
            'payment_rows' => $after['customer_payments'], 'refund_rows' => $after['customer_refunds'],
            'due_rows' => $after['note_revision_surplus_dispositions'],
            'surplus_paid_rows' => $after['note_revision_surplus_refund_payments'],
            'correction_audit' => DB::table('audit_logs')->where('event', 'paid_service_only_work_item_corrected')->get()->all(),
        ];
        self::assertSame(2, count($after['note_revisions']),
            'ADR-0045 accepted edit requires a new revision: '.json_encode($evidence, JSON_THROW_ON_ERROR));
        foreach (['note_revisions', 'note_revision_lines'] as $table) {
            self::assertSame($before[$table], DB::table($table)->whereIn('id', array_column($before[$table], 'id'))
                ->orderBy('id')->get()->map(static fn ($row): array => (array) $row)->all());
        }
        self::assertNotSame($evidence['before_revision'], $evidence['after_revision']);
        self::assertNotSame($before['work_items'][0]['id'], $after['work_items'][0]['id']);
        $this->assertDatabaseHas('note_revisions', [
            'id' => $evidence['after_revision'], 'parent_revision_id' => $evidence['before_revision'],
            'grand_total_rupiah' => 61987, 'revision_number' => 2,
        ]);
        self::assertCount(1, $after['note_revision_surplus_dispositions']);
        self::assertCount(1, $after['note_revision_surplus_refund_payments']);
        self::assertSame(1732, (int) $after['note_revision_surplus_dispositions'][0]['amount_rupiah']);
        self::assertSame(1732, (int) $after['note_revision_surplus_refund_payments'][0]['amount_rupiah']);
        self::assertSame(61987, (int) DB::table('payment_component_allocations')->sum('allocated_amount_rupiah'));
        self::assertSame(1732, json_decode($evidence['correction_audit'][0]->context, true, flags: JSON_THROW_ON_ERROR)['refund_required_rupiah']);
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
