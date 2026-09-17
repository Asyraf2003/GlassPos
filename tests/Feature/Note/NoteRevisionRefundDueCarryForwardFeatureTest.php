<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Application\Note\UseCases\CreateNoteRevisionHandler;
use App\Core\Note\WorkItem\ServiceDetail;
use App\Core\Note\WorkItem\WorkItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsMinimalNotePaymentFixture;
use Tests\TestCase;

final class NoteRevisionRefundDueCarryForwardFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalNotePaymentFixture;

    public function test_later_revision_keeps_refund_due_unavailable_in_settlement(): void
    {
        $this->seedNoteWithRefundDue();

        $result = $this->app->make(CreateNoteRevisionHandler::class)->handle(
            'note-refund-due-001',
            array_replace([
                'reason' => 'Later revision after refund_due.',
                'note' => [
                    'customer_name' => 'Budi Refund Due Revised',
                    'customer_phone' => '08123456789',
                    'transaction_date' => '2026-05-14',
                ],
                'items' => [
                    [
                        'entry_mode' => 'service',
                        'description' => null,
                        'part_source' => 'none',
                        'service' => [
                            'name' => 'Servis Later Refund Due Revision',
                            'price_rupiah' => 230000,
                            'notes' => null,
                        ],
                        'product_lines' => [],
                        'external_purchase_lines' => [],
                    ],
                ],
            ], ['base_revision_id' => $this->revisionBaseForTest('note-refund-due-001')]),
            'admin-test-001',
            false,
        );

        self::assertTrue($result->isSuccess(), $result->message());

        $this->assertDatabaseHas('note_revision_surplus_dispositions', [
            'id' => 'surplus-disposition-refund-due-001',
            'note_root_id' => 'note-refund-due-001',
            'note_revision_id' => 'note-refund-due-001-r001',
            'disposition_type' => 'refund_due',
            'amount_rupiah' => 122000,
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('note_revision_settlements', [
            'id' => 'note-refund-due-001-r002-settlement',
            'note_revision_id' => 'note-refund-due-001-r002',
            'note_root_id' => 'note-refund-due-001',
            'gross_total_rupiah' => 230000,
            'carry_forward_paid_rupiah' => 265000,
            'carry_forward_refunded_rupiah' => 122000,
            'net_paid_rupiah' => 143000,
            'outstanding_rupiah' => 87000,
            'surplus_rupiah' => 0,
            'settlement_status' => 'underpaid',
        ]);
    }

    private function seedNoteWithRefundDue(): void
    {
        $this->seedNoteBase('note-refund-due-001', 'Budi Refund Due', '2026-05-13', 143000, 'open');

        $this->seedWorkItemBase(
            'wi-refund-due-001',
            'note-refund-due-001',
            1,
            WorkItem::TYPE_SERVICE_ONLY,
            WorkItem::STATUS_OPEN,
            143000,
        );

        $this->seedServiceDetailBase(
            'wi-refund-due-001',
            'Servis Before Refund Due',
            143000,
            ServiceDetail::PART_SOURCE_NONE,
        );

        $this->seedServiceOnlyCurrentRevision(
            'note-refund-due-001',
            'note-refund-due-001-r001',
            'wi-refund-due-001',
            'Budi Refund Due',
            '2026-05-13',
            143000,
            'Servis Before Refund Due',
            143000,
        );

        $this->seedCustomerPaymentBase('payment-refund-due-001', 265000, '2026-05-13');

        $this->seedPaymentAllocationBase(
            'allocation-refund-due-001',
            'payment-refund-due-001',
            'note-refund-due-001',
            265000,
        );

        DB::table('note_revision_settlements')->insert([
            'id' => 'settlement-refund-due-001',
            'note_revision_id' => 'note-refund-due-001-r001',
            'note_root_id' => 'note-refund-due-001',
            'gross_total_rupiah' => 143000,
            'carry_forward_paid_rupiah' => 265000,
            'carry_forward_refunded_rupiah' => 0,
            'net_paid_rupiah' => 265000,
            'outstanding_rupiah' => 0,
            'surplus_rupiah' => 122000,
            'settlement_status' => 'overpaid_pending',
            'created_at' => '2026-05-13 09:30:00',
            'updated_at' => null,
        ]);

        DB::table('audit_events')->insert([
            'id' => 'audit-event-refund-due-001',
            'bounded_context' => 'note',
            'aggregate_type' => 'note_revision_surplus_disposition',
            'aggregate_id' => 'surplus-disposition-refund-due-001',
            'event_name' => 'note_revision_surplus_refund_due_created',
            'actor_id' => 'admin-test-001',
            'actor_role' => 'admin',
            'reason' => 'Customer requested refund due.',
            'source_channel' => 'web_admin',
            'request_id' => null,
            'correlation_id' => null,
            'occurred_at' => '2026-05-13 10:00:00',
            'metadata_json' => null,
        ]);

        DB::table('note_revision_surplus_dispositions')->insert([
            'id' => 'surplus-disposition-refund-due-001',
            'note_revision_settlement_id' => 'settlement-refund-due-001',
            'note_root_id' => 'note-refund-due-001',
            'note_revision_id' => 'note-refund-due-001-r001',
            'disposition_type' => 'refund_due',
            'amount_rupiah' => 122000,
            'before_pending_rupiah' => 122000,
            'after_pending_rupiah' => 0,
            'status' => 'active',
            'occurred_at' => '2026-05-13 10:00:00',
            'created_at' => '2026-05-13 10:00:00',
            'updated_at' => null,
            'audit_event_id' => 'audit-event-refund-due-001',
        ]);
    }
}
