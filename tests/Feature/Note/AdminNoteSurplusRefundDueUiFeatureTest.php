<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Core\Note\WorkItem\ServiceDetail;
use App\Core\Note\WorkItem\WorkItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsMinimalNotePaymentFixture;
use Tests\TestCase;

final class AdminNoteSurplusRefundDueUiFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalNotePaymentFixture;

    public function test_admin_detail_renders_refund_due_action_when_pending_surplus_exists(): void
    {
        $admin = $this->loginAsAuthorizedAdmin();

        $this->seedClosedPaidCurrentRevisionNote(
            noteId: 'note-surplus-ui-001',
            revisionId: 'note-surplus-ui-001-r001',
            workItemId: 'wi-surplus-ui-001',
        );

        $this->seedPendingSurplusSettlement(
            settlementId: 'settlement-surplus-ui-001',
            noteId: 'note-surplus-ui-001',
            revisionId: 'note-surplus-ui-001-r001',
            surplusRupiah: 122000,
        );

        $response = $this->actingAs($admin)
            ->get(route('admin.notes.show', ['noteId' => 'note-surplus-ui-001']));

        $response->assertOk();
        $response->assertSee('Tandai Pengembalian Belum Dibayar');
        $response->assertSee('122.000');
        $response->assertSee(route('admin.notes.revision-settlements.refund-due.store', [
            'settlementId' => 'settlement-surplus-ui-001',
        ]), false);
        $response->assertSee('name="amount_rupiah"', false);
        $response->assertSee('value="122000"', false);
        $response->assertSee('max="122000"', false);
        $response->assertSee('data-refund-due-form', false);
        $response->assertSee('data-refund-due-max-rupiah="122000"', false);
        $response->assertSee('data-refund-due-amount', false);
        $response->assertSee('data-refund-due-submit', false);
        $response->assertSee('data-loading-text="Menyimpan Pengembalian Belum Dibayar..."', false);
        $response->assertSee('assets/static/js/pages/note-surplus-refund-due.js', false);
        $response->assertSee('name="reason"', false);
        $response->assertDontSee('refund_paid');
        $response->assertDontSee('customer_credit');
    }

    public function test_admin_detail_does_not_render_refund_due_action_without_pending_surplus(): void
    {
        $admin = $this->loginAsAuthorizedAdmin();

        $this->seedClosedPaidCurrentRevisionNote(
            noteId: 'note-surplus-ui-002',
            revisionId: 'note-surplus-ui-002-r001',
            workItemId: 'wi-surplus-ui-002',
        );

        $response = $this->actingAs($admin)
            ->get(route('admin.notes.show', ['noteId' => 'note-surplus-ui-002']));

        $response->assertOk();
        $response->assertDontSee('Tandai Pengembalian Belum Dibayar');
        $response->assertDontSee('/admin/notes/revision-settlements/settlement-surplus-ui-002/refund-due', false);
        $response->assertDontSee('data-refund-due-form', false);
        $response->assertDontSee('refund_paid');
        $response->assertDontSee('customer_credit');
    }

    private function seedClosedPaidCurrentRevisionNote(
        string $noteId,
        string $revisionId,
        string $workItemId,
    ): void {
        $this->seedNoteBase($noteId, 'Customer Surplus UI', '2026-05-13', 143000, 'closed');
        $this->seedWorkItemBase($workItemId, $noteId, 1, WorkItem::TYPE_SERVICE_ONLY, WorkItem::STATUS_OPEN, 143000);
        $this->seedServiceDetailBase($workItemId, 'Servis Surplus UI', 143000, ServiceDetail::PART_SOURCE_NONE);
        $this->seedCustomerPaymentBase('pay-' . $noteId, 143000, '2026-05-13');

        DB::table('payment_component_allocations')->insert([
            'id' => 'pca-' . $noteId,
            'customer_payment_id' => 'pay-' . $noteId,
            'note_id' => $noteId,
            'work_item_id' => $workItemId,
            'component_type' => 'service_fee',
            'component_ref_id' => $workItemId,
            'component_amount_rupiah_snapshot' => 143000,
            'allocated_amount_rupiah' => 143000,
            'allocation_priority' => 20,
        ]);

        $this->seedServiceOnlyCurrentRevision(
            noteId: $noteId,
            revisionId: $revisionId,
            workItemId: $workItemId,
            customerName: 'Customer Surplus UI',
            transactionDate: '2026-05-13',
            grandTotalRupiah: 143000,
            serviceName: 'Servis Surplus UI',
            servicePriceRupiah: 143000,
            status: WorkItem::STATUS_OPEN,
            customerPhone: '08123456789',
        );
    }

    private function seedPendingSurplusSettlement(
        string $settlementId,
        string $noteId,
        string $revisionId,
        int $surplusRupiah,
    ): void {
        DB::table('note_revision_settlements')->insert([
            'id' => $settlementId,
            'note_revision_id' => $revisionId,
            'note_root_id' => $noteId,
            'gross_total_rupiah' => 143000,
            'carry_forward_paid_rupiah' => 265000,
            'carry_forward_refunded_rupiah' => 0,
            'net_paid_rupiah' => 265000,
            'outstanding_rupiah' => 0,
            'surplus_rupiah' => $surplusRupiah,
            'settlement_status' => 'overpaid_pending',
            'created_at' => '2026-05-13 09:30:00',
            'updated_at' => null,
        ]);
    }
}
