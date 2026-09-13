<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\SeedsMinimalProductFixture;
use Tests\TestCase;

final class CashierProductRefundActionLifecycleFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalProductFixture;

    public static function rowKinds(): array
    {
        return ['product row' => [false], 'package with surviving service' => [true]];
    }

    #[DataProvider('rowKinds')]
    public function test_qty_three_paid_refund_refresh_cannot_repay_reversed_product(bool $package): void
    {
        Carbon::setTestNow('2026-09-14 09:00:00');
        try {
            $cashier = $this->loginAsKasir();
            $this->seedMinimalProduct('refund-product', 'REF3', 'Product qty three', 'Test', null, 100000);
            DB::table('product_inventory')->insert(['product_id' => 'refund-product', 'qty_on_hand' => 10]);
            DB::table('product_inventory_costing')->insert(['product_id' => 'refund-product', 'avg_cost_rupiah' => 40000, 'inventory_value_rupiah' => 400000]);
            $payload = [
                'idempotency_key' => 'qty-three-create',
                'note' => ['customer_name' => 'Refund three', 'transaction_date' => '2026-09-14'],
                'items' => [['entry_mode' => 'product', 'product_lines' => [['product_id' => 'refund-product', 'qty' => 3, 'unit_price_rupiah' => 100000]]]],
                'inline_payment' => ['decision' => 'pay_full', 'payment_method' => 'cash', 'paid_at' => '2026-09-14', 'amount_paid_rupiah' => 300000, 'amount_received_rupiah' => 400000],
            ];
            if ($package) {
                $payload['items'][0] += ['part_source' => 'store_stock', 'pricing_mode' => 'manual_split', 'service' => ['name' => 'Surviving service', 'price_rupiah' => 100000]];
                $payload['items'][0]['entry_mode'] = 'service';
                $payload['inline_payment']['amount_paid_rupiah'] = 400000;
            }
            $this->actingAs($cashier)->post(route('notes.workspace.store'), $payload)->assertSessionHasNoErrors();
            $noteId = (string) DB::table('notes')->value('id');
            $rowId = (string) DB::table('work_items')->value('id');
            $detail = route('cashier.notes.show', ['noteId' => $noteId]);
            $edit = route('cashier.notes.workspace.edit', ['noteId' => $noteId]);
            $this->actingAs($cashier)->get($edit)->assertOk();
            $this->actingAs($cashier)->patch(route('cashier.notes.workspace.update', ['noteId' => $noteId]), array_replace($payload, ['reason' => 'Attempt after paid', 'idempotency_key' => 'qty-three-edit']))->assertForbidden();
            $page = $this->actingAs($cashier)->get($detail)->assertOk();
            self::assertFalse($page->viewData('note')['can_edit_workspace'], 'Cashier must not advertise an edit whose submit is forbidden.');
            $this->assertDatabaseHas('product_inventory', ['product_id' => 'refund-product', 'qty_on_hand' => 7]);
            $refund = ['selected_row_ids' => [$rowId], 'refunded_at' => '2026-09-14', 'reason' => 'Whole row qty three refund', 'idempotency_key' => 'qty-three-refund'];
            $this->actingAs($cashier)->post(route('cashier.notes.refunds.store', ['noteId' => $noteId]), $refund)->assertSessionHasNoErrors();
            $this->actingAs($cashier)->post(route('cashier.notes.refunds.store', ['noteId' => $noteId]), $refund)->assertSessionHasNoErrors();
            $page = $this->actingAs($cashier)->get($detail)->assertOk();
            foreach (['can_show_payment_form', 'can_show_partial_payment_action', 'can_show_settle_payment_action', 'can_edit_workspace'] as $flag) self::assertFalse($page->viewData('note')[$flag], $flag);
            $tables = ['customer_payments', 'payment_component_allocations', 'customer_refunds', 'refund_component_allocations', 'inventory_movements', 'note_history_projection'];
            $before = [];
            foreach ($tables as $table) $before[$table] = DB::table($table)->get()->toJson();
            foreach (['partial', null] as $scope) {
                $this->actingAs($cashier)->post(route('cashier.notes.payments.store', ['noteId' => $noteId]), [
                    'selected_row_ids' => [$rowId], 'payment_scope' => $scope,
                    'payment_method' => 'cash', 'paid_at' => '2026-09-14',
                    'amount_paid' => $scope === 'partial' ? 100000 : 300000, 'amount_received' => 400000,
                ])->assertSessionHasErrors();
            }
            foreach ($tables as $table) self::assertSame($before[$table], DB::table($table)->get()->toJson(), $table.' changed after invalid repayment');
            self::assertSame($package ? 400000 : 300000, (int) DB::table('customer_payments')->sum('amount_rupiah'));
            self::assertSame(300000, (int) DB::table('customer_refunds')->sum('amount_rupiah'));
            self::assertSame(300000, (int) DB::table('refund_component_allocations')->sum('refunded_amount_rupiah'));
            $this->assertDatabaseHas('product_inventory', ['product_id' => 'refund-product', 'qty_on_hand' => 10]);
            $this->assertDatabaseHas('product_inventory_costing', ['product_id' => 'refund-product', 'inventory_value_rupiah' => 400000]);
            self::assertSame(2, DB::table('inventory_movements')->count());
        } finally { Carbon::setTestNow(); }
    }
}
