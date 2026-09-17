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
        $this->exportBrowserPage('a3-detail', $detail->getContent());
        $note = $detail->viewData('note');
        self::assertSame([413472, 162586, 250886], [$note['grand_total_rupiah'], $note['net_paid_rupiah'], $note['outstanding_rupiah']]);
        self::assertTrue($note['can_show_settle_payment_action']);
        self::assertTrue($note['can_edit_workspace']);
        self::assertSame('open', $note['note_state']);
        $editor = $this->get($edit)->assertOk();
        $this->exportBrowserPage('a3-editor', $editor->getContent());
        $settlement = $editor->viewData('workspacePaymentSettlement');
        self::assertSame([413472, 162586, 250886], [$settlement['grand_total_rupiah'], $settlement['net_paid_rupiah'], $settlement['amount_rupiah']]);
        self::assertSame($before, $this->readEvidence(), 'Reading current detail/editor must not repair lifecycle state');
        $this->advancePrimitiveTime();
        $this->post($payment, $this->primitivePayment('presentation-dp3', 112903, 'cash', 120011))->assertSessionHasNoErrors();
        $this->advancePrimitiveTime();
        $this->post($payment, $this->primitivePayment('presentation-final', 137983, 'cash', 150007))->assertSessionHasNoErrors();
        $before = $this->readEvidence();
        $closedResponse = $this->get($show)->assertOk()->assertSee('Lunas');
        $this->exportBrowserPage('a5-detail', $closedResponse->getContent());
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

    private function exportBrowserPage(string $name, string $html): void
    {
        $directory = getenv('PRIMITIVE_PRESENTATION_EXPORT_DIR');
        if (is_string($directory) && is_dir($directory)) {
            file_put_contents($directory.'/'.$name.'.html', $html);
        }
    }

    private function readEvidence(): array
    {
        $evidence = [];
        foreach (['notes', 'work_items', 'note_revisions', 'note_revision_lines', 'customer_payments', 'payment_component_allocations',
            'customer_refunds', 'note_revision_surplus_dispositions', 'note_revision_surplus_refund_payments', 'inventory_movements',
            'note_mutation_events', 'audit_logs'] as $table) {
            $evidence[$table] = $this->primitiveRows($table);
        }
        return $evidence;
    }
}
