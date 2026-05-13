<?php

declare(strict_types=1);

namespace Tests\Feature\AuditLog;

use App\Application\Audit\DTO\AuditEventSnapshotWrite;
use App\Application\Audit\DTO\AuditEventWrite;
use App\Ports\Out\AuditEventWriterPort;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

final class DatabaseAuditEventWriterAdapterTest extends TestCase
{
    use RefreshDatabase;

    public function test_writer_persists_audit_event_with_before_and_after_snapshots(): void
    {
        $writer = $this->app->make(AuditEventWriterPort::class);

        $writer->write(new AuditEventWrite(
            id: 'audit-event-writer-test-001',
            boundedContext: 'note',
            aggregateType: 'note_revision_surplus_disposition',
            aggregateId: 'disposition-test-001',
            eventName: 'note_revision_surplus_refund_due_created',
            actorId: 'admin-test-001',
            actorRole: 'admin',
            reason: 'Customer requested refund due.',
            sourceChannel: 'web_admin',
            requestId: null,
            correlationId: null,
            occurredAt: new DateTimeImmutable('2026-05-13 10:00:00'),
            metadata: [
                'note_root_id' => 'note-root-test-001',
                'note_revision_id' => 'note-revision-test-001',
                'note_revision_settlement_id' => 'settlement-test-001',
                'disposition_type' => 'refund_due',
                'amount_rupiah' => 122000,
            ],
            snapshots: [
                new AuditEventSnapshotWrite('before', [
                    'pending_surplus_rupiah' => 122000,
                ]),
                new AuditEventSnapshotWrite('after', [
                    'pending_surplus_rupiah' => 0,
                ]),
            ],
        ));

        $this->assertDatabaseHas('audit_events', [
            'id' => 'audit-event-writer-test-001',
            'bounded_context' => 'note',
            'aggregate_type' => 'note_revision_surplus_disposition',
            'aggregate_id' => 'disposition-test-001',
            'event_name' => 'note_revision_surplus_refund_due_created',
            'actor_id' => 'admin-test-001',
            'actor_role' => 'admin',
            'reason' => 'Customer requested refund due.',
            'source_channel' => 'web_admin',
        ]);

        $this->assertDatabaseHas('audit_event_snapshots', [
            'audit_event_id' => 'audit-event-writer-test-001',
            'snapshot_kind' => 'before',
        ]);

        $this->assertDatabaseHas('audit_event_snapshots', [
            'audit_event_id' => 'audit-event-writer-test-001',
            'snapshot_kind' => 'after',
        ]);

        $after = DB::table('audit_event_snapshots')
            ->where('audit_event_id', 'audit-event-writer-test-001')
            ->where('snapshot_kind', 'after')
            ->first();

        self::assertNotNull($after);

        $payload = json_decode((string) $after->payload_json, true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(0, $payload['pending_surplus_rupiah']);
    }

    public function test_writer_rejects_duplicate_snapshot_kind_before_database_write(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('duplicate snapshot_kind: before');

        new AuditEventWrite(
            id: 'audit-event-duplicate-snapshot-test',
            boundedContext: 'note',
            aggregateType: 'note_revision_surplus_disposition',
            aggregateId: 'disposition-test-duplicate',
            eventName: 'note_revision_surplus_refund_due_created',
            actorId: 'admin-test-001',
            actorRole: 'admin',
            reason: 'Duplicate snapshot guard.',
            sourceChannel: 'web_admin',
            requestId: null,
            correlationId: null,
            occurredAt: new DateTimeImmutable('2026-05-13 10:00:00'),
            snapshots: [
                new AuditEventSnapshotWrite('before', ['value' => 1]),
                new AuditEventSnapshotWrite('before', ['value' => 2]),
            ],
        );
    }

    public function test_writer_participates_in_outer_database_transaction(): void
    {
        $writer = $this->app->make(AuditEventWriterPort::class);

        DB::beginTransaction();

        try {
            $writer->write(new AuditEventWrite(
                id: 'audit-event-rollback-test-001',
                boundedContext: 'note',
                aggregateType: 'note_revision_surplus_disposition',
                aggregateId: 'disposition-rollback-test-001',
                eventName: 'note_revision_surplus_refund_due_created',
                actorId: 'admin-test-001',
                actorRole: 'admin',
                reason: 'Rollback proof.',
                sourceChannel: 'web_admin',
                requestId: null,
                correlationId: null,
                occurredAt: new DateTimeImmutable('2026-05-13 10:00:00'),
                snapshots: [
                    new AuditEventSnapshotWrite('after', [
                        'pending_surplus_rupiah' => 0,
                    ]),
                ],
            ));

            throw new RuntimeException('force rollback after audit write');
        } catch (RuntimeException) {
            DB::rollBack();
        }

        $this->assertDatabaseMissing('audit_events', [
            'id' => 'audit-event-rollback-test-001',
        ]);

        $this->assertDatabaseMissing('audit_event_snapshots', [
            'audit_event_id' => 'audit-event-rollback-test-001',
        ]);
    }
}
