<?php

declare(strict_types=1);

namespace Tests\Feature\AuditLog;

use App\Adapters\Out\Audit\DatabaseAuditOutboxWriterAdapter;
use App\Application\Audit\DTO\AuditEventSnapshotWrite;
use App\Application\Audit\DTO\AuditEventWrite;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class ProcessAuditOutboxCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_materializes_pending_outbox_row_to_canonical_audit_tables(): void
    {
        $this->writePendingOutboxEvent('audit-outbox-process-001');

        $this->artisan('audit:outbox:process', ['--limit' => 10])
            ->assertExitCode(0);

        $this->assertDatabaseHas('audit_events', [
            'id' => 'audit-outbox-process-001',
            'bounded_context' => 'expense',
            'aggregate_type' => 'expense_category',
            'aggregate_id' => 'cat-1',
            'event_name' => 'expense_category_updated',
            'actor_id' => 'admin-1',
        ]);

        $this->assertDatabaseHas('audit_event_snapshots', [
            'audit_event_id' => 'audit-outbox-process-001',
            'snapshot_kind' => 'before',
        ]);

        $this->assertDatabaseHas('audit_event_snapshots', [
            'audit_event_id' => 'audit-outbox-process-001',
            'snapshot_kind' => 'after',
        ]);

        $row = DB::table('audit_outbox')
            ->where('audit_event_id', 'audit-outbox-process-001')
            ->first();

        $this->assertNotNull($row);
        $this->assertSame('processed', $row->status);
        $this->assertSame(0, (int) $row->attempts);
        $this->assertNull($row->locked_at);
        $this->assertNotNull($row->processed_at);
    }

    public function test_command_second_run_does_not_duplicate_processed_canonical_audit(): void
    {
        $this->writePendingOutboxEvent('audit-outbox-process-duplicate-001');

        $this->artisan('audit:outbox:process', ['--limit' => 10])
            ->assertExitCode(0);

        $this->artisan('audit:outbox:process', ['--limit' => 10])
            ->assertExitCode(0);

        $this->assertSame(1, DB::table('audit_events')
            ->where('id', 'audit-outbox-process-duplicate-001')
            ->count());

        $this->assertSame(2, DB::table('audit_event_snapshots')
            ->where('audit_event_id', 'audit-outbox-process-duplicate-001')
            ->count());

        $this->assertDatabaseHas('audit_outbox', [
            'audit_event_id' => 'audit-outbox-process-duplicate-001',
            'status' => 'processed',
        ]);
    }

    public function test_command_marks_failed_row_when_materialization_fails(): void
    {
        $this->writePendingOutboxEvent('audit-outbox-process-fail-001');

        DB::table('audit_events')->insert([
            'id' => 'audit-outbox-process-fail-001',
            'bounded_context' => 'expense',
            'aggregate_type' => 'expense_category',
            'aggregate_id' => 'cat-1',
            'event_name' => 'expense_category_updated',
            'actor_id' => 'admin-1',
            'actor_role' => null,
            'reason' => null,
            'source_channel' => 'web_admin',
            'request_id' => null,
            'correlation_id' => null,
            'occurred_at' => new DateTimeImmutable('2026-05-23 10:00:00'),
            'metadata_json' => null,
        ]);

        $this->artisan('audit:outbox:process', [
            '--limit' => 10,
            '--max-attempts' => 1,
        ])->assertExitCode(1);

        $row = DB::table('audit_outbox')
            ->where('audit_event_id', 'audit-outbox-process-fail-001')
            ->first();

        $this->assertNotNull($row);
        $this->assertSame('failed', $row->status);
        $this->assertSame(1, (int) $row->attempts);
        $this->assertNotNull($row->last_error);
        $this->assertNull($row->locked_at);

        $this->assertSame(1, DB::table('audit_events')
            ->where('id', 'audit-outbox-process-fail-001')
            ->count());
    }

    private function writePendingOutboxEvent(string $auditEventId): void
    {
        $writer = $this->app->make(DatabaseAuditOutboxWriterAdapter::class);

        $writer->write(new AuditEventWrite(
            id: $auditEventId,
            boundedContext: 'expense',
            aggregateType: 'expense_category',
            aggregateId: 'cat-1',
            eventName: 'expense_category_updated',
            actorId: 'admin-1',
            actorRole: null,
            reason: null,
            sourceChannel: 'web_admin',
            requestId: 'request-1',
            correlationId: 'correlation-1',
            occurredAt: new DateTimeImmutable('2026-05-23 10:00:00'),
            metadata: [
                'category_id' => 'cat-1',
                'performed_by_actor_id' => 'admin-1',
            ],
            snapshots: [
                new AuditEventSnapshotWrite('before', [
                    'id' => 'cat-1',
                    'code' => 'EXP-ELEC',
                    'name' => 'Listrik',
                    'description' => null,
                    'is_active' => false,
                ]),
                new AuditEventSnapshotWrite('after', [
                    'id' => 'cat-1',
                    'code' => 'EXP-UTIL',
                    'name' => 'Utilitas',
                    'description' => null,
                    'is_active' => true,
                ]),
            ],
        ));
    }
}
