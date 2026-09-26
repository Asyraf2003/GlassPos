<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Application\Note\Services\NoteDetailPageDataBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\Support\BuildsPrimitiveLifecycleFixture;
use Tests\TestCase;

final class DetailRefundChoicesFeatureTest extends TestCase
{
    use BuildsPrimitiveLifecycleFixture;
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_paid_service_refund_preserves_payment_history_without_stock_effect(): void
    {
        [$note, $rows] = $this->paidNote([1]);
        $stock = $this->primitiveRows('inventory_movements');
        $payments = $this->primitiveRows('customer_payments');
        $this->refund($note, $rows, [])->assertSessionHasNoErrors();
        self::assertSame(63719, (int) DB::table('customer_refunds')->sum('amount_rupiah'));
        $this->assertDatabaseHas('refund_component_allocations', ['component_type' => 'service_fee', 'refunded_amount_rupiah' => 63719]);
        $this->assertDatabaseHas('notes', ['id' => $note, 'total_rupiah' => 0, 'note_state' => 'refunded']);
        self::assertSame($stock, $this->primitiveRows('inventory_movements'));
        self::assertSame($payments, $this->primitiveRows('customer_payments'));
    }

    public function test_product_refund_without_return_keeps_issued_stock_and_audits_the_choice(): void
    {
        [$note, $rows] = $this->paidNote([0]);
        $stock = $this->primitiveRows('inventory_movements');
        $this->refund($note, $rows, [$rows[0] => false])->assertSessionHasNoErrors();
        self::assertSame($stock, $this->primitiveRows('inventory_movements'));
        self::assertSame(142539, (int) DB::table('customer_refunds')->sum('amount_rupiah'));
        $this->assertDatabaseHas('product_inventory', ['product_id' => 'primitive-p', 'qty_on_hand' => 14]);
    }

    public function test_paid_row_can_be_refunded_on_mixed_root_without_mutating_unpaid_row(): void
    {
        [$note, $rows] = $this->paidNote([0, 1], 142539);
        $paid = (string) DB::table('work_items')->where('transaction_type', 'store_stock_sale_only')->value('id');
        $unpaid = (string) DB::table('work_items')->where('id', '<>', $paid)->value('id');
        $this->refund($note, [$unpaid], [])->assertSessionHasErrors();
        $this->assertDatabaseCount('customer_refunds', 0);
        $this->refund($note, [$paid], [$paid => true])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('notes', ['id' => $note, 'total_rupiah' => 63719, 'note_state' => 'open']);
        $this->assertDatabaseHas('work_items', ['id' => $unpaid, 'status' => 'open']);
        $this->assertDatabaseHas('product_inventory', ['product_id' => 'primitive-p', 'qty_on_hand' => 17]);
    }

    public function test_package_refunds_service_and_product_and_returns_stock_only_when_chosen(): void
    {
        [$note, $rows] = $this->paidNote([2]);
        $this->refund($note, $rows, [$rows[0] => true])->assertSessionHasNoErrors();
        self::assertSame(99257, (int) DB::table('customer_refunds')->sum('amount_rupiah'));
        $this->assertDatabaseHas('refund_component_allocations', ['component_type' => 'service_fee', 'refunded_amount_rupiah' => 41983]);
        $this->assertDatabaseHas('refund_component_allocations', ['component_type' => 'service_store_stock_part', 'refunded_amount_rupiah' => 57274]);
        $this->assertDatabaseHas('inventory_movements', ['source_type' => 'work_item_store_stock_line_reversal', 'qty_delta' => 2, 'unit_cost_rupiah' => 11503]);
        $this->assertDatabaseHas('notes', ['id' => $note, 'total_rupiah' => 0, 'note_state' => 'refunded']);
    }

    public function test_choices_are_required_scoped_and_included_in_semantic_replay(): void
    {
        [$note, $rows] = $this->paidNote([0]);
        foreach ([[], ['foreign-row' => true], [$rows[0] => 'invalid']] as $choices) {
            $this->refund($note, $rows, $choices)->assertSessionHasErrors();
            $this->assertDatabaseCount('customer_refunds', 0);
            $this->assertDatabaseHas('product_inventory', ['product_id' => 'primitive-p', 'qty_on_hand' => 14]);
        }
        $this->refund($note, $rows, [$rows[0] => false])->assertSessionHasNoErrors();
        $money = $this->primitiveRows('customer_refunds');
        $stock = $this->primitiveRows('inventory_movements');
        $this->refund($note, $rows, [$rows[0] => '0'])->assertSessionHasNoErrors();
        $this->refund($note, $rows, [$rows[0] => true])->assertSessionHasErrors();
        self::assertSame($money, $this->primitiveRows('customer_refunds'));
        self::assertSame($stock, $this->primitiveRows('inventory_movements'));
        $event = DB::table('audit_outbox')->where('event_name', 'selected_rows_refund_plan_recorded')->sole();
        $payload = json_decode($event->metadata_json, true, flags: JSON_THROW_ON_ERROR);
        self::assertSame([$rows[0] => false], $payload['stock_returns']);
    }

    public function test_no_return_package_shadow_is_not_returned_by_a_later_revision(): void
    {
        [$note, $rows] = $this->paidNote([2]);
        $this->refund($note, $rows, [$rows[0] => false])->assertSessionHasNoErrors();
        $stock = $this->primitiveRows('inventory_movements');
        $this->loginAsAuthorizedAdmin();
        $payload = $this->primitiveWorkspace([$this->primitiveItems()[1]], 'detail-after-refund');
        $payload['base_revision_id'] = $this->revisionBaseForTest($note);
        $this->patch(route('admin.notes.workspace.update', ['noteId' => $note]), $payload)->assertSessionHasNoErrors();
        self::assertSame($stock, $this->primitiveRows('inventory_movements'));
        $this->assertDatabaseHas('product_inventory', ['product_id' => 'primitive-q', 'qty_on_hand' => 21]);
        $this->assertDatabaseCount('note_revisions', 2);
    }

    public function test_service_component_refund_keeps_paid_stock_component_collectible_balance_zero(): void
    {
        [$note, $rows] = $this->paidNote([2]);
        $stock = $this->primitiveRows('inventory_movements');
        $this->refund($note, [$rows[0].'::service_fee::'.$rows[0]], [])->assertSessionHasNoErrors();
        $page = app(NoteDetailPageDataBuilder::class)->build($note);
        self::assertSame(0, $page['note']['outstanding_rupiah']);
        self::assertCount(1, $page['note']['billing_rows']);
        self::assertSame('service_store_stock_part', $page['note']['billing_rows'][0]['component_type']);
        self::assertSame(0, $page['note']['billing_rows'][0]['outstanding_rupiah']);
        self::assertSame($stock, $this->primitiveRows('inventory_movements'));
    }

    private function paidNote(array $indexes, ?int $payment = null): array
    {
        $this->preparePrimitiveFixture();
        $this->loginAsKasir();
        $items = $this->primitiveItems();
        $items = array_map(static fn (int $index): array => $items[$index], $indexes);
        $this->post(route('notes.workspace.store'), $this->primitiveWorkspace($items, 'detail-create'))->assertSessionHasNoErrors();
        $note = (string) DB::table('notes')->value('id');
        $rows = DB::table('work_items')->pluck('id')->all();
        $amount = $payment ?? (int) DB::table('notes')->value('total_rupiah');
        $this->post(route('cashier.notes.payments.store', ['noteId' => $note]), $this->primitivePayment('detail-pay', $amount, 'transfer'))->assertSessionHasNoErrors();

        return [$note, $rows];
    }

    private function refund(string $note, array $rows, array $returns): TestResponse
    {
        return $this->post(route('cashier.notes.refunds.store', ['noteId' => $note]), [
            'selected_row_ids' => $rows, 'stock_returns' => $returns,
            'refunded_at' => '2026-09-15', 'reason' => 'Detail: customer decision',
            'idempotency_key' => 'detail-refund-'.implode('-', $rows),
        ]);
    }
}
