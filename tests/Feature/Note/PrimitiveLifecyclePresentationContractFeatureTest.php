<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Support\BuildsPrimitiveLifecycleFixture;
use Tests\TestCase;

final class PrimitiveLifecyclePresentationContractFeatureTest extends TestCase
{
    use BuildsPrimitiveLifecycleFixture;
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_chain_a_current_detail_editor_and_payment_history_present_the_same_checkpoint(): void
    {
        $this->preparePrimitiveFixture();
        $this->loginAsKasir();
        $create = $this->primitiveWorkspace($this->primitiveItems(), 'presentation-create');
        $create['inline_payment'] = ['decision' => 'pay_partial', 'payment_method' => 'cash', 'paid_at' => '2026-09-15',
            'amount_paid_rupiah' => 73129, 'amount_received_rupiah' => 100003];
        $this->post(route('notes.workspace.store'), $create)->assertSessionHasNoErrors();
        $id = (string) DB::table('notes')->value('id');
        $payment = route('cashier.notes.payments.store', ['noteId' => $id]);
        $show = route('cashier.notes.show', ['noteId' => $id]);
        $edit = route('cashier.notes.workspace.edit', ['noteId' => $id]);
        $this->advancePrimitiveTime();
        $this->post($payment, $this->primitivePayment('presentation-dp2', 89457, 'transfer'))->assertSessionHasNoErrors();
        $this->advancePrimitiveTime();
        $revision = $this->primitiveWorkspace($this->primitiveItems(81258), 'presentation-upward');
        $revision['base_revision_id'] = $this->revisionBaseForTest($id);
        $this->patch(route('cashier.notes.workspace.update', ['noteId' => $id]), $revision)->assertSessionHasNoErrors();
        $before = $this->readEvidence();
        $detail = $this->get($show)->assertOk()->assertSee('250.886')->assertSee('162.586')->assertSee('413.472');
        $this->exportBrowserPage('a3-detail', $detail->getContent(), $show);
        $note = $detail->viewData('note');
        self::assertSame([413472, 162586, 250886], [$note['grand_total_rupiah'], $note['net_paid_rupiah'], $note['outstanding_rupiah']]);
        self::assertTrue($note['can_show_settle_payment_action']);
        self::assertTrue($note['can_edit_workspace']);
        self::assertSame('open', $note['note_state']);
        $editor = $this->get($edit)->assertOk();
        $this->exportBrowserPage('a3-editor', $editor->getContent(), $edit);
        self::assertSame($note['current_revision_id'], $editor->viewData('currentRevisionId'));
        $settlement = $editor->viewData('workspacePaymentSettlement');
        self::assertSame([413472, 162586, 250886], [$settlement['grand_total_rupiah'], $settlement['net_paid_rupiah'], $settlement['amount_rupiah']]);
        self::assertSame($before, $this->readEvidence(), 'Reading current detail/editor must not repair lifecycle state');
        $invalid = $this->primitivePayment('presentation-invalid', 0, 'cash', 120011);
        $this->from($show)->post($payment, $invalid)->assertSessionHasErrors('amount_paid');
        $errorPage = $this->get($show)->assertOk();
        $this->exportBrowserPage('a3-validation', $errorPage->getContent(), $show, $errorPage->viewData('errors'));
        self::assertSame($before, $this->readEvidence(), 'Rejected payment and validation display must not mutate lifecycle');
        $this->get($show)->assertOk();
        $this->advancePrimitiveTime();
        $this->post($payment, $this->primitivePayment('presentation-dp3', 112903, 'cash', 120011))->assertSessionHasNoErrors();
        $this->advancePrimitiveTime();
        $this->post($payment, $this->primitivePayment('presentation-final', 137983, 'cash', 150007))->assertSessionHasNoErrors();
        $before = $this->readEvidence();
        $closedResponse = $this->get($show)->assertOk()->assertSee('Lunas');
        $this->exportBrowserPage('a5-detail', $closedResponse->getContent(), $show);
        $closed = $closedResponse->viewData('note');
        self::assertSame([413472, 413472, 0], [$closed['grand_total_rupiah'], $closed['net_paid_rupiah'], $closed['outstanding_rupiah']]);
        self::assertSame('closed', $closed['note_state']);
        self::assertFalse($closed['can_show_settle_payment_action']);
        self::assertFalse($closed['can_edit_workspace']);
        $events = array_reverse($closed['payment_timeline']);
        self::assertSame([73129, 89457, 112903, 137983], array_column($events, 'payment_amount_rupiah'));
        self::assertSame([100003, null, 120011, 150007], array_column($events, 'amount_received_rupiah'));
        self::assertSame([26874, null, 7108, 12024], array_column($events, 'change_rupiah'));
        self::assertSame($before, $this->readEvidence());
        // Existing access contract permits GET; the closed-note mutation must be rejected.
        $revision['base_revision_id'] = (string) DB::table('notes')->where('id', $id)->value('current_revision_id');
        $revision['idempotency_key'] = 'presentation-closed-edit';
        $this->patch(route('cashier.notes.workspace.update', ['noteId' => $id]), $revision)->assertForbidden();
        self::assertSame($before, $this->readEvidence());
    }

    public function test_chain_b_refund_and_reopened_receivable_present_current_money_separately_from_history(): void
    {
        $this->preparePrimitiveFixture();
        $cashier = $this->loginAsKasir();
        $create = $this->primitiveWorkspace($this->primitiveItems(), 'presentation-b-create');
        $create['inline_payment'] = ['decision' => 'pay_partial', 'payment_method' => 'cash', 'paid_at' => '2026-09-15',
            'amount_paid_rupiah' => 73129, 'amount_received_rupiah' => 100003];
        $this->post(route('notes.workspace.store'), $create)->assertSessionHasNoErrors();
        $id = (string) DB::table('notes')->value('id');
        $payment = route('cashier.notes.payments.store', ['noteId' => $id]);
        $show = route('cashier.notes.show', ['noteId' => $id]);
        $this->advancePrimitiveTime();
        $this->post($payment, $this->primitivePayment('presentation-b-dp2', 89457, 'transfer'))->assertSessionHasNoErrors();
        $this->advancePrimitiveTime();
        $this->post($payment, $this->primitivePayment('presentation-b-full', 233347, 'cash', 250009))->assertSessionHasNoErrors();
        $oldProduct = (string) DB::table('work_items')->where('transaction_type', 'store_stock_sale_only')->value('id');
        $this->post(route('cashier.notes.refunds.store', ['noteId' => $id]), [
            'selected_row_ids' => [$oldProduct], 'refunded_at' => '2026-09-15', 'reason' => 'Presentation source refund', 'idempotency_key' => 'presentation-b-refund',
        ])->assertSessionHasNoErrors();
        $before = $this->readEvidence();
        $b4Response = $this->get($show)->assertOk()->assertSee('Total Revisi')->assertSee('Tagihan Aktif')->assertSee('253.394');
        $this->exportBrowserPage('b4-detail', $b4Response->getContent(), $show);
        $b4 = $b4Response->viewData('note');
        self::assertSame(395933, $b4['grand_total_rupiah'], 'Immutable revision snapshot remains explicitly named');
        self::assertSame([253394, 253394, 0], [$b4['current_total_rupiah'], $b4['net_paid_rupiah'], $b4['outstanding_rupiah']]);
        self::assertEqualsCanonicalizing([20002, 89457, 33080], array_column($b4['refund_timeline'], 'amount_rupiah'));
        self::assertSame($before, $this->readEvidence());
        $this->loginAsAuthorizedAdmin();
        $down = $this->primitiveWorkspace(array_slice($this->primitiveItems(51983), 1), 'presentation-b-down');
        $down['base_revision_id'] = $this->revisionBaseForTest($id);
        $this->patch(route('admin.notes.workspace.update', ['noteId' => $id]), $down)->assertSessionHasNoErrors();
        $items = $this->primitiveItems(70211);
        $up = $this->primitiveWorkspace([$items[3], $items[2], $items[1], ['entry_mode' => 'product',
            'product_lines' => [['product_id' => 'primitive-r', 'qty' => 5, 'unit_price_rupiah' => 33571]]]], 'presentation-b-up');
        $up['base_revision_id'] = $this->revisionBaseForTest($id);
        $this->patch(route('admin.notes.workspace.update', ['noteId' => $id]), $up)->assertSessionHasNoErrors();
        $this->actingAs($cashier);
        $before = $this->readEvidence();
        $b6Response = $this->get($show)->assertOk()->assertSee('186.083')->assertSee('241.658')->assertSee('11.736');
        $this->exportBrowserPage('b6-detail', $b6Response->getContent(), $show);
        $b6 = $b6Response->viewData('note');
        self::assertSame([11736, 11736], array_column($b6['surplus_disposition_audit_timeline'], 'amount_rupiah'));
        self::assertFalse($b6['surplus_disposition']['has_pending_refund_paid_action']);
        self::assertSame([427741, 241658, 186083], [$b6['grand_total_rupiah'], $b6['net_paid_rupiah'], $b6['outstanding_rupiah']]);
        self::assertSame(395933, array_sum(array_column($b6['payment_timeline'], 'payment_amount_rupiah')));
        self::assertTrue($b6['can_show_settle_payment_action']);
        self::assertSame('open', $b6['note_state']);
        self::assertSame($b4['refund_timeline'], $b6['refund_timeline']);
        $editor = $this->get(route('cashier.notes.workspace.edit', ['noteId' => $id]))->assertOk();
        self::assertSame($b6['current_revision_id'], $editor->viewData('currentRevisionId'));
        $settlement = $editor->viewData('workspacePaymentSettlement');
        self::assertSame([427741, 241658, 186083], [$settlement['grand_total_rupiah'], $settlement['net_paid_rupiah'], $settlement['amount_rupiah']]);
        self::assertSame($before, $this->readEvidence());
    }

    private function exportBrowserPage(string $name, string $html, string $url, ?\Illuminate\Support\ViewErrorBag $errors = null): void
    {
        $directory = getenv('PRIMITIVE_PRESENTATION_EXPORT_DIR');
        if (is_string($directory) && is_dir($directory)) {
            file_put_contents($directory.'/'.$name.'.html', $html);
            if ($errors !== null) {
                $this->app['session.store']->flash('errors', $errors);
            }
            $handset = $this->withHeaders(['Sec-CH-UA-Mobile' => '?1'])->get($url)->assertOk();
            file_put_contents($directory.'/'.$name.'-handset.html', $handset->getContent());
            $this->withHeaders(['Sec-CH-UA-Mobile' => '?0']);
        }
    }

    private function readEvidence(): array
    {
        $evidence = [];
        foreach (['notes', 'work_items', 'note_revisions', 'note_revision_lines', 'customer_payments', 'payment_component_allocations',
            'customer_refunds', 'note_revision_surplus_dispositions', 'note_revision_surplus_refund_payments', 'inventory_movements',
            'note_mutation_events', 'note_mutation_snapshots', 'audit_logs', 'audit_outbox',
            'customer_payment_cash_details', 'payment_allocations', 'refund_component_allocations', 'note_revision_settlements',
            'work_item_service_details', 'work_item_store_stock_lines', 'work_item_external_purchase_lines'] as $table) {
            $evidence[$table] = $table === 'work_item_service_details'
                ? DB::table($table)->orderBy('work_item_id')->get()->map(fn ($row) => (array) $row)->all()
                : $this->primitiveRows($table);
        }
        return $evidence;
    }
}
