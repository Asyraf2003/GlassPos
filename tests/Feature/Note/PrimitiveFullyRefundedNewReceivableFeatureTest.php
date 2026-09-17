<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Application\Note\Services\NoteOutstandingPaymentAmountResolver;
use App\Application\Payment\UseCases\RecordAndAllocateNotePaymentHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsMinimalProductFixture;
use Tests\TestCase;

final class PrimitiveFullyRefundedNewReceivableFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalProductFixture;

    public function test_fully_refunded_root_can_settle_only_new_revision_work(): void
    {
        $this->loginAsAuthorizedAdmin();
        $date = date('Y-m-d');
        $this->seedMinimalProduct('fully-refunded-p', 'FR-P', 'Old product', 'QA', null, 47513);
        DB::table('product_inventory')->insert(['product_id' => 'fully-refunded-p', 'qty_on_hand' => 17]);
        DB::table('product_inventory_costing')->insert([
            'product_id' => 'fully-refunded-p', 'avg_cost_rupiah' => 19721, 'inventory_value_rupiah' => 335257,
        ]);
        $this->post(route('notes.workspace.store'), [
            'idempotency_key' => 'fully-refunded-create',
            'note' => ['customer_name' => 'New work after refund', 'transaction_date' => $date],
            'items' => [['entry_mode' => 'product', 'product_lines' => [
                ['product_id' => 'fully-refunded-p', 'qty' => 1, 'unit_price_rupiah' => 47513],
            ]]],
            'inline_payment' => ['decision' => 'pay_full', 'payment_method' => 'cash', 'paid_at' => $date,
                'amount_paid_rupiah' => 47513, 'amount_received_rupiah' => 50003],
        ])->assertRedirect()->assertSessionHasNoErrors();
        $noteId = (string) DB::table('notes')->value('id');
        $oldRowId = (string) DB::table('work_items')->value('id');
        $oldPayment = (array) DB::table('customer_payments')->first();
        $this->post(route('admin.notes.refunds.store', ['noteId' => $noteId]), [
            'selected_row_ids' => [$oldRowId], 'refunded_at' => $date,
            'reason' => 'Full old product refund', 'idempotency_key' => 'fully-refunded-refund',
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('notes', ['id' => $noteId, 'note_state' => 'refunded']);
        $refunds = DB::table('customer_refunds')->orderBy('id')->get()->toJson();
        $refundAllocations = DB::table('refund_component_allocations')->orderBy('id')->get()->toJson();
        $movements = DB::table('inventory_movements')->orderBy('id')->get()->toJson();
        $oldRevisions = DB::table('note_revisions')->orderBy('id')->get()->toJson();
        $oldRevisionId = (string) DB::table('notes')->value('current_revision_id');
        $this->patch(route('admin.notes.workspace.update', ['noteId' => $noteId]), [
            'base_revision_id' => $this->revisionBaseForTest($noteId),
            'idempotency_key' => 'fully-refunded-new-work', 'reason' => 'New current service only',
            'note' => ['customer_name' => 'New work after refund', 'transaction_date' => $date],
            'items' => [['entry_mode' => 'service', 'service' => ['name' => 'New service', 'price_rupiah' => 63719]]],
            'inline_payment' => ['decision' => 'skip'],
        ])->assertRedirect()->assertSessionHasNoErrors();
        self::assertSame(63719, app(NoteOutstandingPaymentAmountResolver::class)->resolveFull($noteId)->data()['outstanding_rupiah']);
        self::assertSame($oldPayment, (array) DB::table('customer_payments')->where('id', $oldPayment['id'])->first());
        self::assertSame($refunds, DB::table('customer_refunds')->orderBy('id')->get()->toJson());
        self::assertSame($refundAllocations, DB::table('refund_component_allocations')->orderBy('id')->get()->toJson());
        self::assertSame($movements, DB::table('inventory_movements')->orderBy('id')->get()->toJson());
        self::assertSame($oldRevisions, DB::table('note_revisions')->where('id', $oldRevisionId)->orderBy('id')->get()->toJson());
        self::assertSame(0, DB::table('payment_component_allocations')->where('work_item_id', $oldRowId)->count());
        $stateBeforePayment = DB::table('notes')->where('id', $noteId)->value('note_state');
        self::assertSame('open', $stateBeforePayment);
        self::assertSame(1, DB::table('note_mutation_events')->where('note_id', $noteId)->where('mutation_type', 'note_reopened')->count());
        $payment = app(RecordAndAllocateNotePaymentHandler::class)->handle($noteId, 63719, $date, [], 'cash', 70003);
        self::assertTrue($payment->isSuccess(), 'New work has outstanding63719; root='.$stateBeforePayment.'; '.$payment->message());
        $this->assertDatabaseHas('notes', ['id' => $noteId, 'note_state' => 'closed']);
        self::assertSame(63719, (int) DB::table('payment_component_allocations')->sum('allocated_amount_rupiah'));
        self::assertSame(0, DB::table('payment_component_allocations')->where('work_item_id', $oldRowId)->count());
        self::assertSame(2, DB::table('customer_payments')->count());
        self::assertSame($refunds, DB::table('customer_refunds')->orderBy('id')->get()->toJson());
        self::assertSame(2, DB::table('note_mutation_events')->where('note_id', $noteId)->where('mutation_type', 'note_closed')->count());
        self::assertSame($refundAllocations, DB::table('refund_component_allocations')->orderBy('id')->get()->toJson());
        self::assertSame($movements, DB::table('inventory_movements')->orderBy('id')->get()->toJson());
        $this->assertDatabaseHas('customer_payment_cash_details', ['amount_paid_rupiah' => 63719, 'amount_received_rupiah' => 70003, 'change_rupiah' => 6284]);
    }
}
