<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Core\Note\WorkItem\ServiceDetail;
use App\Core\Note\WorkItem\WorkItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsMinimalNotePaymentFixture;
use Tests\TestCase;

final class AdminNoteSurplusRefundPaidUiFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalNotePaymentFixture;

    public function test_admin_detail_renders_refund_paid_action_when_refund_due_has_remaining_amount(): void
    {
        $admin = $this->loginAsAuthorizedAdmin();

        $this->seedClosedPaidCurrentRevisionNote(
            noteId: 'note-surplus-paid-ui-001',
            revisionId: 'note-surplus-paid-ui-001-r001',
            workItemId: 'wi-surplus-paid-ui-001',
        );

        $this->seedRefundDueDisposition(
            dispositionId: 'disp-surplus-paid-ui-001',
            settlementId: 'settlement-surplus-paid-ui-001',
            noteId: 'note-surplus-paid-ui-001',
            revisionId: 'note-surplus-paid-ui-001-r001',
            refundDueRupiah: 122000,
        );

        $this->seedExistingRefundPaid(
            paymentId: 'surplus-refund-payment-ui-existing-001',
            dispositionId: 'disp-surplus-paid-ui-001',
            settlementId: 'settlement-surplus-paid-ui-001',
            noteId: 'note-surplus-paid-ui-001',
            revisionId: 'note-surplus-paid-ui-001-r001',
            amountRupiah: 50000,
        );

        $response = $this->actingAs($admin)
            ->get(route('admin.notes.show', ['noteId' => 'note-surplus-paid-ui-001']));

        $response->assertOk();
        $response->assertSee('Catat Pengembalian Sudah Dibayar');
        $response->assertSee('72.000');
        $response->assertSee(route('admin.notes.revision-surplus-dispositions.refund-paid.store', [
            'dispositionId' => 'disp-surplus-paid-ui-001',
        ]), false);
        $response->assertSee('name="amount_rupiah"', false);
        $response->assertSee('value="72000"', false);
        $response->assertSee('max="72000"', false);
        $response->assertSee('name="effective_date"', false);
        $response->assertSee('name="reason"', false);
        $response->assertSee('name="idempotency_key"', false);
        $response->assertSee('data-refund-paid-form', false);
        $response->assertSee('data-refund-paid-max-rupiah="72000"', false);
        $response->assertDontSee('customer_credit');
        $response->assertDontSee('customer_balance_entries');
    }

    private function seedClosedPaidCurrentRevisionNote(
        string $noteId,
        string $revisionId,
        string $workItemId,
    ): void {
        $this->seedNoteBase($noteId, 'Customer Surplus Paid UI', '2026-05-13', 143000, 'closed');
        $this->seedWorkItemBase($workItemId, $noteId, 1, WorkItem::TYPE_SERVICE_ONLY, WorkItem::STATUS_OPEN, 143000);
        $this->seedServiceDetailBase($workItemId, 'Servis Surplus Paid UI', 143000, ServiceDetail::PART_SOURCE_NONE);
        $this->seedCustomerPaymentBase('pay-' . $noteId, 265000, '2026-05-13');

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
            customerName: 'Customer Surplus Paid UI',
            transactionDate: '2026-05-13',
            grandTotalRupiah: 143000,
            serviceName: 'Servis Surplus Paid UI',
            servicePriceRupiah: 143000,
            status: WorkItem::STATUS_OPEN,
            customerPhone: '08123456789',
        );
    }

    private function seedRefundDueDisposition(
        string $dispositionId,
        string $settlementId,
        string $noteId,
        string $revisionId,
        int $refundDueRupiah,
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
            'surplus_rupiah' => $refundDueRupiah,
            'settlement_status' => 'overpaid_pending',
            'created_at' => '2026-05-13 09:30:00',
            'updated_at' => null,
        ]);

        DB::table('audit_events')->insert([
            'id' => 'audit-' . $dispositionId,
            'bounded_context' => 'note',
            'aggregate_type' => 'note_revision_surplus_disposition',
            'aggregate_id' => $dispositionId,
            'event_name' => 'note_revision_surplus_refund_due_created',
            'actor_id' => 'admin-1',
            'actor_role' => 'admin',
            'reason' => 'Customer requested refund due before cash out.',
            'source_channel' => 'test',
            'request_id' => null,
            'correlation_id' => null,
            'occurred_at' => '2026-05-13 10:00:00',
            'metadata_json' => null,
        ]);

        DB::table('note_revision_surplus_dispositions')->insert([
            'id' => $dispositionId,
            'note_revision_settlement_id' => $settlementId,
            'note_root_id' => $noteId,
            'note_revision_id' => $revisionId,
            'disposition_type' => 'refund_due',
            'amount_rupiah' => $refundDueRupiah,
            'before_pending_rupiah' => $refundDueRupiah,
            'after_pending_rupiah' => 0,
            'status' => 'active',
            'occurred_at' => '2026-05-13 10:00:00',
            'created_at' => '2026-05-13 10:00:00',
            'updated_at' => null,
            'audit_event_id' => 'audit-' . $dispositionId,
        ]);
    }

    private function seedExistingRefundPaid(
        string $paymentId,
        string $dispositionId,
        string $settlementId,
        string $noteId,
        string $revisionId,
        int $amountRupiah,
    ): void {
        DB::table('audit_events')->insert([
            'id' => 'audit-' . $paymentId,
            'bounded_context' => 'note',
            'aggregate_type' => 'note_revision_surplus_refund_payment',
            'aggregate_id' => $paymentId,
            'event_name' => 'note_revision_surplus_refund_paid_recorded',
            'actor_id' => 'admin-1',
            'actor_role' => 'admin',
            'reason' => 'Existing partial refund paid.',
            'source_channel' => 'test',
            'request_id' => null,
            'correlation_id' => null,
            'occurred_at' => '2026-05-13 11:00:00',
            'metadata_json' => null,
        ]);

        DB::table('note_revision_surplus_refund_payments')->insert([
            'id' => $paymentId,
            'note_revision_surplus_disposition_id' => $dispositionId,
            'note_revision_settlement_id' => $settlementId,
            'note_root_id' => $noteId,
            'note_revision_id' => $revisionId,
            'amount_rupiah' => $amountRupiah,
            'effective_date' => '2026-05-13',
            'occurred_at' => '2026-05-13 11:00:00',
            'status' => 'active',
            'idempotency_key' => 'existing-refund-paid-ui-idem-001',
            'audit_event_id' => 'audit-' . $paymentId,
            'created_at' => '2026-05-13 11:00:00',
            'updated_at' => null,
        ]);
    }
}
