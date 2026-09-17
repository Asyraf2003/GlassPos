<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Application\Note\Services\NoteOutstandingPaymentAmountResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Support\BuildsPrimitiveLifecycleFixture;
use Tests\TestCase;

final class PrimitivePaymentDebtCashChainFeatureTest extends TestCase
{
    use BuildsPrimitiveLifecycleFixture;
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_chain_a_repeated_dp_revision_and_final_cash_preserve_money_events(): void
    {
        $this->preparePrimitiveFixture();
        $cashier = $this->loginAsKasir();
        $payload = $this->primitiveWorkspace($this->primitiveItems(), 'chain-a-create');
        $payload['inline_payment'] = ['decision' => 'pay_partial', 'payment_method' => 'cash', 'paid_at' => '2026-09-15',
            'amount_paid_rupiah' => 73129, 'amount_received_rupiah' => 100003];
        $this->post(route('notes.workspace.store'), $payload)->assertRedirect()->assertSessionHasNoErrors();
        $noteId = (string) DB::table('notes')->value('id');
        $route = route('cashier.notes.payments.store', ['noteId' => $noteId]);
        $p1 = (string) DB::table('customer_payments')->value('id');
        $this->checkpoint($noteId, 395933, 73129, 322804, 1, 2, 'open');
        $this->assertFunding($p1, ['product_only_work_item' => 20002, 'service_external_purchase_part' => 53127]);
        $this->assertDatabaseHas('customer_payment_cash_details', ['customer_payment_id' => $p1, 'amount_paid_rupiah' => 73129, 'amount_received_rupiah' => 100003, 'change_rupiah' => 26874]);

        $this->advancePrimitiveTime();
        $dp2 = $this->primitivePayment('chain-a-dp2', 89457, 'transfer');
        $this->post($route, $dp2)->assertSessionHasNoErrors();
        $p2 = (string) DB::table('customer_payments')->where('id', '<>', $p1)->value('id');
        $this->checkpoint($noteId, 395933, 162586, 233347, 2, 3, 'open');
        $this->assertFunding($p2, ['product_only_work_item' => 89457]);
        $this->assertDatabaseMissing('customer_payment_cash_details', ['customer_payment_id' => $p2]);
        $before = $this->effects();
        $this->post($route, $dp2)->assertSessionHasNoErrors();
        self::assertSame($before, $this->effects(), 'A2 replay');
        foreach ([['amount_paid' => 89458], ['payment_method' => 'cash', 'amount_received' => 100003]] as $change) {
            $this->post($route, array_replace($dp2, $change))->assertSessionHasErrors('payment');
            self::assertSame($before, $this->effects(), 'A2 changed semantic payload');
        }
        $payments = $this->primitiveRows('customer_payments');
        $cash = $this->primitiveRows('customer_payment_cash_details');
        $r1 = (string) DB::table('notes')->value('current_revision_id');
        $revision = $this->primitiveRows('note_revisions');
        $lines = $this->primitiveRows('note_revision_lines');
        $oldRowIds = DB::table('work_items')->pluck('id')->all();
        $this->advancePrimitiveTime();
        $this->actingAs($cashier)->patch(route('cashier.notes.workspace.update', ['noteId' => $noteId]), array_replace($this->primitiveWorkspace($this->primitiveItems(81258), 'chain-a-upward'), ['base_revision_id' => $this->revisionBaseForTest($noteId)]))
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->checkpoint($noteId, 413472, 162586, 250886, 2, 3, 'open');
        self::assertSame($payments, $this->primitiveRows('customer_payments'));
        self::assertSame($cash, $this->primitiveRows('customer_payment_cash_details'));
        self::assertSame($revision, DB::table('note_revisions')->where('id', $r1)->orderBy('id')->get()->toJson());
        self::assertSame($lines, DB::table('note_revision_lines')->where('note_revision_id', $r1)->orderBy('id')->get()->toJson());
        self::assertSame(0, DB::table('payment_component_allocations')->whereIn('work_item_id', $oldRowIds)->count());
        $this->assertDatabaseCount('note_revisions', 2);
        $this->assertDatabaseHas('note_revisions', ['id' => DB::table('notes')->value('current_revision_id'), 'parent_revision_id' => $r1, 'revision_number' => 2]);
        // ADR-0045 permits current allocation redistribution, not payment-event rewriting.
        foreach ([$p1 => 73129, $p2 => 89457] as $id => $amount) {
            self::assertSame($amount, (int) DB::table('payment_component_allocations')->where('customer_payment_id', $id)->sum('allocated_amount_rupiah'));
        }
        self::assertSame(['product_only_work_item' => 109459, 'service_external_purchase_part' => 53127], DB::table('payment_component_allocations')->selectRaw('component_type, SUM(allocated_amount_rupiah) AS amount')->groupBy('component_type')->orderBy('component_type')->pluck('amount', 'component_type')->map(fn ($v) => (int) $v)->all());

        $this->advancePrimitiveTime();
        $this->post($route, $this->primitivePayment('chain-a-dp3', 112903, 'cash', 120011))->assertSessionHasNoErrors();
        $p3 = (string) DB::table('customer_payments')->whereNotIn('id', [$p1, $p2])->value('id');
        $this->checkpoint($noteId, 413472, 275489, 137983, 3, 6, 'open');
        $this->assertFunding($p3, ['product_only_work_item' => 33080, 'service_fee' => 22549, 'service_store_stock_part' => 57274]);
        $this->assertDatabaseHas('customer_payment_cash_details', ['customer_payment_id' => $p3, 'amount_paid_rupiah' => 112903, 'amount_received_rupiah' => 120011, 'change_rupiah' => 7108]);
        $serviceId = DB::table('work_item_service_details')->where('service_name', 'QA service')->value('work_item_id');
        $this->assertDatabaseHas('payment_component_allocations', ['customer_payment_id' => $p3, 'work_item_id' => $serviceId, 'component_type' => 'service_fee', 'allocated_amount_rupiah' => 22549]);
        $stockBeforeFinal = $this->primitiveRows('inventory_movements');
        $before = $this->effects();
        foreach ([$this->primitivePayment('chain-a-over', 137984, 'transfer'), $this->primitivePayment('chain-a-short', 137983, 'cash', 137982)] as $invalid) {
            $this->post($route, $invalid)->assertSessionHasErrors();
            self::assertSame($before, $this->effects(), 'A5 invalid payment is atomic');
        }
        $this->advancePrimitiveTime();
        $this->post($route, $this->primitivePayment('chain-a-final', 137983, 'cash', 150007))->assertSessionHasNoErrors();
        self::assertSame($stockBeforeFinal, $this->primitiveRows('inventory_movements'));
        $this->assertDatabaseCount('customer_payment_cash_details', 3);
        $p4 = (string) DB::table('customer_payments')->whereNotIn('id', [$p1, $p2, $p3])->value('id');
        $this->checkpoint($noteId, 413472, 413472, 0, 4, 9, 'closed');
        self::assertSame([58709, 41983, 37291], DB::table('payment_component_allocations')->join('work_items', 'work_items.id', '=', 'payment_component_allocations.work_item_id')->where('customer_payment_id', $p4)->orderBy('line_no')->pluck('allocated_amount_rupiah')->map(fn ($v) => (int) $v)->all());
        $this->assertDatabaseHas('customer_payment_cash_details', ['customer_payment_id' => $p4, 'amount_paid_rupiah' => 137983, 'amount_received_rupiah' => 150007, 'change_rupiah' => 12024]);
        self::assertSame($payments, DB::table('customer_payments')->whereIn('id', [$p1, $p2])->orderBy('id')->get()->toJson());
        self::assertSame($cash, DB::table('customer_payment_cash_details')->where('customer_payment_id', $p1)->orderBy('customer_payment_id')->get()->toJson());
        self::assertSame(324015, (int) DB::table('customer_payments')->where('payment_method', 'cash')->sum('amount_rupiah'));
        self::assertSame(370021, (int) DB::table('customer_payment_cash_details')->sum('amount_received_rupiah'));
        self::assertSame(46006, (int) DB::table('customer_payment_cash_details')->sum('change_rupiah'));
    }

    private function checkpoint(string $noteId, int $gross, int $paid, int $outstanding, int $events, int $allocations, string $state): void
    {
        $this->assertDatabaseHas('notes', ['id' => $noteId, 'total_rupiah' => $gross, 'note_state' => $state]);
        self::assertSame($paid, (int) DB::table('customer_payments')->sum('amount_rupiah'));
        self::assertSame($paid, (int) DB::table('payment_component_allocations')->sum('allocated_amount_rupiah'));
        $this->assertDatabaseCount('customer_payments', $events);
        $this->assertDatabaseCount('payment_component_allocations', $allocations);
        $payable = app(NoteOutstandingPaymentAmountResolver::class)->resolveFull($noteId);
        if ($state === 'closed') {
            self::assertTrue($payable->isFailure(), 'Closed note cannot accept another full payment');
        } else {
            self::assertTrue($payable->isSuccess(), $payable->message() ?? 'Payable resolution');
            self::assertSame($outstanding, $payable->data()['outstanding_rupiah']);
        }
        $this->assertDatabaseHas('note_history_projection', ['note_id' => $noteId, 'net_paid_rupiah' => $paid, 'outstanding_rupiah' => $outstanding]);
        foreach (['customer_refunds', 'refund_component_allocations', 'note_revision_surplus_dispositions', 'note_revision_surplus_refund_payments'] as $table) {
            $this->assertDatabaseCount($table, 0);
        }
        self::assertSame($state === 'closed' ? 1 : 0, DB::table('note_mutation_events')->where('mutation_type', 'note_closed')->count());
    }

    private function assertFunding(string $paymentId, array $expected): void
    {
        self::assertSame($expected, DB::table('payment_component_allocations')->where('customer_payment_id', $paymentId)->orderBy('component_type')->pluck('allocated_amount_rupiah', 'component_type')->map(fn ($v) => (int) $v)->all());
    }

    private function effects(): array
    {
        $result = [];
        foreach (['notes', 'customer_payments', 'customer_payment_cash_details', 'payment_component_allocations', 'inventory_movements', 'note_revisions', 'note_mutation_events', 'audit_logs'] as $table) {
            $result[$table] = $this->primitiveRows($table);
        }
        return $result;
    }
}
