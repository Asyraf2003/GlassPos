<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Core\Note\WorkItem\ServiceDetail;
use App\Core\Note\WorkItem\WorkItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsMinimalNotePaymentFixture;
use Tests\TestCase;

final class AdminNoteSurplusRefundDueAuditTimelineUiFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalNotePaymentFixture;

    public function test_admin_detail_renders_refund_due_audit_timeline(): void
    {
        $admin = $this->loginAsAuthorizedAdmin();

        $this->seedClosedPaidCurrentRevisionNote(
            noteId: 'note-surplus-audit-ui-001',
            revisionId: 'note-surplus-audit-ui-001-r001',
            workItemId: 'wi-surplus-audit-ui-001',
        );

        $this->seedPendingSurplusSettlement(
            settlementId: 'settlement-surplus-audit-ui-001',
            noteId: 'note-surplus-audit-ui-001',
            revisionId: 'note-surplus-audit-ui-001-r001',
            surplusRupiah: 122000,
        );

        $this->seedRefundDueAuditTimeline(
            dispositionId: 'disp-surplus-audit-ui-001',
            auditEventId: 'audit-surplus-audit-ui-001',
            settlementId: 'settlement-surplus-audit-ui-001',
            noteId: 'note-surplus-audit-ui-001',
            revisionId: 'note-surplus-audit-ui-001-r001',
            amountRupiah: 122000,
            beforePendingRupiah: 122000,
            afterPendingRupiah: 0,
            reason: 'Customer minta refund due setelah koreksi nota.',
        );

        $response = $this->actingAs($admin)
            ->get(route('admin.notes.show', ['noteId' => 'note-surplus-audit-ui-001']));

        $response->assertOk();
        $response->assertSee('Timeline Audit Surplus');
        $response->assertSee('Riwayat Pengembalian Belum Dibayar');
        $response->assertSee('Pengembalian Belum Dibayar Ditandai');
        $response->assertSee('Amount 122.000');
        $response->assertSee('Sisa pending 0');
        $response->assertSee('Customer minta refund due setelah koreksi nota.');
        $response->assertSee('admin');
        $response->assertDontSee('refund_paid');
        $response->assertDontSee('customer_credit');
    }

    public function test_admin_detail_renders_refund_paid_audit_timeline(): void
{
    $admin = $this->loginAsAuthorizedAdmin();

    $this->seedClosedPaidCurrentRevisionNote(
        noteId: 'note-surplus-paid-audit-ui-001',
        revisionId: 'note-surplus-paid-audit-ui-001-r001',
        workItemId: 'wi-surplus-paid-audit-ui-001',
    );

    $this->seedPendingSurplusSettlement(
        settlementId: 'settlement-surplus-paid-audit-ui-001',
        noteId: 'note-surplus-paid-audit-ui-001',
        revisionId: 'note-surplus-paid-audit-ui-001-r001',
        surplusRupiah: 122000,
    );

    $this->seedRefundDueAuditTimeline(
        dispositionId: 'disp-surplus-paid-audit-ui-001',
        auditEventId: 'audit-surplus-paid-due-ui-001',
        settlementId: 'settlement-surplus-paid-audit-ui-001',
        noteId: 'note-surplus-paid-audit-ui-001',
        revisionId: 'note-surplus-paid-audit-ui-001-r001',
        amountRupiah: 122000,
        beforePendingRupiah: 122000,
        afterPendingRupiah: 0,
        reason: 'Customer minta refund due sebelum kas keluar.',
    );

    $this->seedRefundPaidAuditTimeline(
        paymentId: 'surplus-refund-payment-audit-ui-001',
        dispositionId: 'disp-surplus-paid-audit-ui-001',
        auditEventId: 'audit-surplus-paid-ui-001',
        settlementId: 'settlement-surplus-paid-audit-ui-001',
        noteId: 'note-surplus-paid-audit-ui-001',
        revisionId: 'note-surplus-paid-audit-ui-001-r001',
        amountRupiah: 50000,
        refundDueRupiah: 122000,
        activeRefundPaidBeforeRupiah: 0,
        activeRefundPaidAfterRupiah: 50000,
        remainingBeforeRupiah: 122000,
        remainingAfterRupiah: 72000,
        reason: 'Refund paid dibayar tunai sebagian.',
    );

    $response = $this->actingAs($admin)
        ->get(route('admin.notes.show', ['noteId' => 'note-surplus-paid-audit-ui-001']));

    $response->assertOk();
    $response->assertSee('Timeline Audit Surplus');
    $response->assertSee('Pengembalian Belum Dibayar Ditandai');
    $response->assertSee('Pengembalian Sudah Dibayar Dicatat');
    $response->assertSee('Amount 50.000');
    $response->assertSee('Sisa refund due 72.000');
    $response->assertSee('Refund paid dibayar tunai sebagian.');
    $response->assertSee('admin');
    $response->assertDontSee('customer_credit');
}

    public function test_admin_detail_does_not_render_refund_due_audit_timeline_without_audit_event(): void
    {
        $admin = $this->loginAsAuthorizedAdmin();

        $this->seedClosedPaidCurrentRevisionNote(
            noteId: 'note-surplus-audit-ui-002',
            revisionId: 'note-surplus-audit-ui-002-r001',
            workItemId: 'wi-surplus-audit-ui-002',
        );

        $response = $this->actingAs($admin)
            ->get(route('admin.notes.show', ['noteId' => 'note-surplus-audit-ui-002']));

        $response->assertOk();
        $response->assertDontSee('Timeline Audit Surplus');
        $response->assertDontSee('Riwayat Pengembalian Belum Dibayar');
        $response->assertDontSee('Pengembalian Belum Dibayar Ditandai');
        $response->assertDontSee('refund_paid');
        $response->assertDontSee('customer_credit');
    }

    private function seedClosedPaidCurrentRevisionNote(
        string $noteId,
        string $revisionId,
        string $workItemId,
    ): void {
        $this->seedNoteBase($noteId, 'Customer Surplus Audit UI', '2026-05-13', 143000, 'closed');
        $this->seedWorkItemBase($workItemId, $noteId, 1, WorkItem::TYPE_SERVICE_ONLY, WorkItem::STATUS_OPEN, 143000);
        $this->seedServiceDetailBase($workItemId, 'Servis Surplus Audit UI', 143000, ServiceDetail::PART_SOURCE_NONE);
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
            customerName: 'Customer Surplus Audit UI',
            transactionDate: '2026-05-13',
            grandTotalRupiah: 143000,
            serviceName: 'Servis Surplus Audit UI',
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

    private function seedRefundDueAuditTimeline(
        string $dispositionId,
        string $auditEventId,
        string $settlementId,
        string $noteId,
        string $revisionId,
        int $amountRupiah,
        int $beforePendingRupiah,
        int $afterPendingRupiah,
        string $reason,
    ): void {
        DB::table('audit_events')->insert([
            'id' => $auditEventId,
            'bounded_context' => 'note',
            'aggregate_type' => 'note_revision_surplus_disposition',
            'aggregate_id' => $dispositionId,
            'event_name' => 'note_revision_surplus_refund_due_created',
            'actor_id' => 'admin-1',
            'actor_role' => 'admin',
            'reason' => $reason,
            'source_channel' => 'test',
            'request_id' => null,
            'correlation_id' => null,
            'occurred_at' => '2026-05-13 10:15:00',
            'metadata_json' => json_encode([
                'note_root_id' => $noteId,
                'note_revision_id' => $revisionId,
                'note_revision_settlement_id' => $settlementId,
                'disposition_id' => $dispositionId,
                'disposition_type' => 'refund_due',
                'amount_rupiah' => $amountRupiah,
            ], JSON_THROW_ON_ERROR),
        ]);

        DB::table('note_revision_surplus_dispositions')->insert([
            'id' => $dispositionId,
            'note_revision_settlement_id' => $settlementId,
            'note_root_id' => $noteId,
            'note_revision_id' => $revisionId,
            'disposition_type' => 'refund_due',
            'amount_rupiah' => $amountRupiah,
            'before_pending_rupiah' => $beforePendingRupiah,
            'after_pending_rupiah' => $afterPendingRupiah,
            'status' => 'active',
            'occurred_at' => '2026-05-13 10:15:00',
            'created_at' => '2026-05-13 10:15:00',
            'updated_at' => null,
            'audit_event_id' => $auditEventId,
        ]);
    }
    private function seedRefundPaidAuditTimeline(
    string $paymentId,
    string $dispositionId,
    string $auditEventId,
    string $settlementId,
    string $noteId,
    string $revisionId,
    int $amountRupiah,
    int $refundDueRupiah,
    int $activeRefundPaidBeforeRupiah,
    int $activeRefundPaidAfterRupiah,
    int $remainingBeforeRupiah,
    int $remainingAfterRupiah,
    string $reason,
): void {
    DB::table('audit_events')->insert([
        'id' => $auditEventId,
        'bounded_context' => 'note',
        'aggregate_type' => 'note_revision_surplus_refund_payment',
        'aggregate_id' => $paymentId,
        'event_name' => 'note_revision_surplus_refund_paid_recorded',
        'actor_id' => 'admin-1',
        'actor_role' => 'admin',
        'reason' => $reason,
        'source_channel' => 'test',
        'request_id' => null,
        'correlation_id' => null,
        'occurred_at' => '2026-05-13 11:00:00',
        'metadata_json' => json_encode([
            'note_root_id' => $noteId,
            'note_revision_id' => $revisionId,
            'note_revision_settlement_id' => $settlementId,
            'note_revision_surplus_disposition_id' => $dispositionId,
            'note_revision_surplus_refund_payment_id' => $paymentId,
            'amount_rupiah' => $amountRupiah,
            'effective_date' => '2026-05-13',
            'disposition_type' => 'refund_due',
            'idempotency_key' => 'idem-surplus-paid-audit-ui-001',
        ], JSON_THROW_ON_ERROR),
    ]);

    DB::table('audit_event_snapshots')->insert([
        [
            'id' => $auditEventId . '-before',
            'audit_event_id' => $auditEventId,
            'snapshot_kind' => 'before',
            'payload_json' => json_encode([
                'refund_due_rupiah' => $refundDueRupiah,
                'active_refund_paid_rupiah' => $activeRefundPaidBeforeRupiah,
                'remaining_refund_due_rupiah' => $remainingBeforeRupiah,
            ], JSON_THROW_ON_ERROR),
            'created_at' => '2026-05-13 11:00:00',
        ],
        [
            'id' => $auditEventId . '-after',
            'audit_event_id' => $auditEventId,
            'snapshot_kind' => 'after',
            'payload_json' => json_encode([
                'refund_due_rupiah' => $refundDueRupiah,
                'active_refund_paid_rupiah' => $activeRefundPaidAfterRupiah,
                'remaining_refund_due_rupiah' => $remainingAfterRupiah,
            ], JSON_THROW_ON_ERROR),
            'created_at' => '2026-05-13 11:00:00',
        ],
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
        'idempotency_key' => 'idem-surplus-paid-audit-ui-001',
        'audit_event_id' => $auditEventId,
        'created_at' => '2026-05-13 11:00:00',
        'updated_at' => null,
    ]);
}


}
