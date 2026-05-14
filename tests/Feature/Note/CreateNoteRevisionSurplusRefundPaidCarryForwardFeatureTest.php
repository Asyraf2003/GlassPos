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

final class CreateNoteRevisionSurplusRefundPaidCarryForwardFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalNotePaymentFixture;

    public function test_later_revision_does_not_reclaim_surplus_refund_paid_as_available_payment(): void
    {
        $this->seedNoteWithSurplusRefundPaid();

        $result = $this->app->make(CreateNoteRevisionHandler::class)->handle(
            'note-s12-001',
            [
                'reason' => 'Later revision after surplus refund_paid.',
                'note' => [
                    'customer_name' => 'Budi S12 Revised',
                    'customer_phone' => '08123',
                    'transaction_date' => '2026-05-14',
                ],
                'items' => [
                    [
                        'entry_mode' => 'service',
                        'description' => null,
                        'part_source' => 'none',
                        'service' => [
                            'name' => 'Servis Later Revision',
                            'price_rupiah' => '230000',
                            'notes' => null,
                        ],
                        'product_lines' => [],
                        'external_purchase_lines' => [],
                    ],
                ],
            ],
            'admin-test-001',
            false,
        );

        self::assertTrue($result->isSuccess(), $result->message());

        $this->assertDatabaseHas('note_revision_settlements', [
            'id' => 'note-s12-001-r002-settlement',
            'note_revision_id' => 'note-s12-001-r002',
            'note_root_id' => 'note-s12-001',
            'gross_total_rupiah' => 230000,
            'carry_forward_paid_rupiah' => 265000,
            'carry_forward_refunded_rupiah' => 50000,
            'net_paid_rupiah' => 215000,
            'outstanding_rupiah' => 15000,
            'surplus_rupiah' => 0,
            'settlement_status' => 'underpaid',
        ]);

        $this->assertDatabaseHas('note_revision_surplus_refund_payments', [
            'id' => 'surplus-refund-paid-s12-001',
            'note_revision_surplus_disposition_id' => 'surplus-disposition-s12-001',
            'amount_rupiah' => 50000,
            'status' => 'active',
        ]);
    }

    private function seedNoteWithSurplusRefundPaid(): void
    {
        $this->seedNoteBase('note-s12-001', 'Budi S12', '2026-05-13', 143000, 'open');
        $this->seedWorkItemBase(
            'wi-s12-001',
            'note-s12-001',
            1,
            WorkItem::TYPE_SERVICE_ONLY,
            WorkItem::STATUS_OPEN,
            143000,
        );
        $this->seedServiceDetailBase(
            'wi-s12-001',
            'Servis Before Refund Paid',
            143000,
            ServiceDetail::PART_SOURCE_NONE,
        );
        $this->seedServiceOnlyCurrentRevision(
            'note-s12-001',
            'note-s12-001-r001',
            'wi-s12-001',
            'Budi S12',
            '2026-05-13',
            143000,
            'Servis Before Refund Paid',
            143000,
        );

        $this->seedCustomerPaymentBase('payment-s12-001', 265000, '2026-05-13');
        $this->seedPaymentAllocationBase(
            'allocation-s12-001',
            'payment-s12-001',
            'note-s12-001',
            265000,
        );

        DB::table('note_revision_settlements')->insert([
            'id' => 'settlement-s12-001',
            'note_revision_id' => 'note-s12-001-r001',
            'note_root_id' => 'note-s12-001',
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
            [
                'id' => 'audit-event-disposition-s12-001',
                'bounded_context' => 'note',
                'aggregate_type' => 'note_revision_surplus_disposition',
                'aggregate_id' => 'surplus-disposition-s12-001',
                'event_name' => 'note_revision_surplus_refund_due_created',
                'actor_id' => 'admin-test-001',
                'actor_role' => 'admin',
                'reason' => 'Customer requested refund due.',
                'source_channel' => 'web_admin',
                'request_id' => null,
                'correlation_id' => null,
                'occurred_at' => '2026-05-13 10:00:00',
                'metadata_json' => null,
            ],
            [
                'id' => 'audit-event-refund-paid-s12-001',
                'bounded_context' => 'note',
                'aggregate_type' => 'note_revision_surplus_refund_payment',
                'aggregate_id' => 'surplus-refund-paid-s12-001',
                'event_name' => 'note_revision_surplus_refund_paid_recorded',
                'actor_id' => 'admin-test-001',
                'actor_role' => 'admin',
                'reason' => 'Customer received surplus refund.',
                'source_channel' => 'web_admin',
                'request_id' => null,
                'correlation_id' => null,
                'occurred_at' => '2026-05-13 11:00:00',
                'metadata_json' => null,
            ],
        ]);

        DB::table('note_revision_surplus_dispositions')->insert([
            'id' => 'surplus-disposition-s12-001',
            'note_revision_settlement_id' => 'settlement-s12-001',
            'note_root_id' => 'note-s12-001',
            'note_revision_id' => 'note-s12-001-r001',
            'disposition_type' => 'refund_due',
            'amount_rupiah' => 122000,
            'before_pending_rupiah' => 122000,
            'after_pending_rupiah' => 0,
            'status' => 'active',
            'occurred_at' => '2026-05-13 10:00:00',
            'created_at' => '2026-05-13 10:00:00',
            'updated_at' => null,
            'audit_event_id' => 'audit-event-disposition-s12-001',
        ]);

        DB::table('note_revision_surplus_refund_payments')->insert([
            'id' => 'surplus-refund-paid-s12-001',
            'note_revision_surplus_disposition_id' => 'surplus-disposition-s12-001',
            'note_revision_settlement_id' => 'settlement-s12-001',
            'note_root_id' => 'note-s12-001',
            'note_revision_id' => 'note-s12-001-r001',
            'amount_rupiah' => 50000,
            'effective_date' => '2026-05-13',
            'occurred_at' => '2026-05-13 11:00:00',
            'status' => 'active',
            'idempotency_key' => 'refund-paid-s12-001',
            'audit_event_id' => 'audit-event-refund-paid-s12-001',
            'created_at' => '2026-05-13 11:00:00',
            'updated_at' => null,
        ]);
    }
}
