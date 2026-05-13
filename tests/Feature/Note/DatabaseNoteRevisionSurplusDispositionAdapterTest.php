<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Application\Note\DTO\NoteRevisionSurplusDisposition;
use App\Ports\Out\Note\NoteRevisionSurplusDispositionReaderPort;
use App\Ports\Out\Note\NoteRevisionSurplusDispositionWriterPort;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class DatabaseNoteRevisionSurplusDispositionAdapterTest extends TestCase
{
    use RefreshDatabase;

    public function test_writer_persists_refund_due_surplus_disposition(): void
    {
        $this->seedSourceSettlement('settlement-test-001', 122000);
        $this->seedAuditEvent('audit-event-disposition-test-001');

        $writer = $this->app->make(NoteRevisionSurplusDispositionWriterPort::class);

        $writer->create(NoteRevisionSurplusDisposition::create(
            'disposition-test-001',
            'settlement-test-001',
            'note-root-test-001',
            'note-revision-test-001',
            NoteRevisionSurplusDisposition::TYPE_REFUND_DUE,
            122000,
            122000,
            0,
            NoteRevisionSurplusDisposition::STATUS_ACTIVE,
            new DateTimeImmutable('2026-05-13 10:00:00'),
            new DateTimeImmutable('2026-05-13 10:00:00'),
            'audit-event-disposition-test-001',
        ));

        $this->assertDatabaseHas('note_revision_surplus_dispositions', [
            'id' => 'disposition-test-001',
            'note_revision_settlement_id' => 'settlement-test-001',
            'note_root_id' => 'note-root-test-001',
            'note_revision_id' => 'note-revision-test-001',
            'disposition_type' => 'refund_due',
            'amount_rupiah' => 122000,
            'before_pending_rupiah' => 122000,
            'after_pending_rupiah' => 0,
            'status' => 'active',
            'audit_event_id' => 'audit-event-disposition-test-001',
        ]);
    }

    public function test_reader_returns_unresolved_pending_after_active_disposition(): void
    {
        $this->seedSourceSettlement('settlement-test-002', 122000);
        $this->seedAuditEvent('audit-event-disposition-test-002');

        $reader = $this->app->make(NoteRevisionSurplusDispositionReaderPort::class);
        $writer = $this->app->make(NoteRevisionSurplusDispositionWriterPort::class);

        $before = $reader->findPendingBySettlementId('settlement-test-002');

        self::assertNotNull($before);
        self::assertSame(122000, $before->surplusRupiah);
        self::assertSame(0, $before->activeDispositionRupiah);
        self::assertSame(122000, $before->unresolvedPendingRupiah);

        $writer->create(NoteRevisionSurplusDisposition::create(
            'disposition-test-002',
            'settlement-test-002',
            'note-root-test-001',
            'note-revision-test-001',
            NoteRevisionSurplusDisposition::TYPE_REFUND_DUE,
            50000,
            122000,
            72000,
            NoteRevisionSurplusDisposition::STATUS_ACTIVE,
            new DateTimeImmutable('2026-05-13 10:00:00'),
            new DateTimeImmutable('2026-05-13 10:00:00'),
            'audit-event-disposition-test-002',
        ));

        $after = $reader->findPendingBySettlementId('settlement-test-002');

        self::assertNotNull($after);
        self::assertSame(122000, $after->surplusRupiah);
        self::assertSame(50000, $after->activeDispositionRupiah);
        self::assertSame(72000, $after->unresolvedPendingRupiah);
    }

    public function test_reader_ignores_non_overpaid_pending_settlement(): void
    {
        $this->seedSourceSettlement('settlement-test-003', 0, 'paid');

        $reader = $this->app->make(NoteRevisionSurplusDispositionReaderPort::class);

        self::assertNull($reader->findPendingBySettlementId('settlement-test-003'));
    }

    private function seedSourceSettlement(
        string $settlementId,
        int $surplusRupiah,
        string $status = 'overpaid_pending',
    ): void {
        DB::table('notes')->insert([
            'id' => 'note-root-test-001',
            'customer_name' => 'Customer Test',
            'customer_phone' => '08123456789',
            'transaction_date' => '2026-05-13',
            'note_state' => 'closed',
            'closed_at' => '2026-05-13 09:00:00',
            'closed_by_actor_id' => 'admin-test-001',
            'reopened_at' => null,
            'reopened_by_actor_id' => null,
            'total_rupiah' => 143000,
        ]);

        DB::table('note_revisions')->insert([
            'id' => 'note-revision-test-001',
            'note_root_id' => 'note-root-test-001',
            'revision_number' => 2,
            'parent_revision_id' => null,
            'created_by_actor_id' => 'admin-test-001',
            'reason' => 'Test revision surplus.',
            'customer_name' => 'Customer Test',
            'customer_phone' => '08123456789',
            'transaction_date' => '2026-05-13',
            'grand_total_rupiah' => 143000,
            'line_count' => 1,
            'created_at' => '2026-05-13 09:30:00',
            'updated_at' => null,
        ]);

        DB::table('note_revision_settlements')->insert([
            'id' => $settlementId,
            'note_revision_id' => 'note-revision-test-001',
            'note_root_id' => 'note-root-test-001',
            'gross_total_rupiah' => 143000,
            'carry_forward_paid_rupiah' => 265000,
            'carry_forward_refunded_rupiah' => 0,
            'net_paid_rupiah' => 265000,
            'outstanding_rupiah' => 0,
            'surplus_rupiah' => $surplusRupiah,
            'settlement_status' => $status,
            'created_at' => '2026-05-13 09:30:00',
            'updated_at' => null,
        ]);
    }

    private function seedAuditEvent(string $auditEventId): void
    {
        DB::table('audit_events')->insert([
            'id' => $auditEventId,
            'bounded_context' => 'note',
            'aggregate_type' => 'note_revision_surplus_disposition',
            'aggregate_id' => 'disposition-test',
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
    }
}
