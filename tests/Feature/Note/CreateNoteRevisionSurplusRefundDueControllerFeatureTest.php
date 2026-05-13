<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Models\User;
use App\Ports\Out\ClockPort;
use App\Ports\Out\UuidPort;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class CreateNoteRevisionSurplusRefundDueControllerFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_refund_due_from_pending_surplus_settlement(): void
    {
        $admin = $this->seedActor('admin-refund-due@example.test', 'admin');
        $this->seedSourceSettlement('settlement-refund-due-http-001', 122000);
        $this->bindDeterministicPorts();

        $response = $this->actingAs($admin)
            ->from(route('admin.notes.show', ['noteId' => 'note-root-http-001']))
            ->post(route('admin.notes.revision-settlements.refund-due.store', [
                'settlementId' => 'settlement-refund-due-http-001',
            ]), [
                'amount_rupiah' => 50000,
                'reason' => 'Customer requested refund due from admin transport.',
            ]);

        $response->assertRedirect(route('admin.notes.show', ['noteId' => 'note-root-http-001']));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('note_revision_surplus_dispositions', [
            'id' => 'disposition-refund-due-http-001',
            'note_revision_settlement_id' => 'settlement-refund-due-http-001',
            'note_root_id' => 'note-root-http-001',
            'note_revision_id' => 'note-revision-http-001',
            'disposition_type' => 'refund_due',
            'amount_rupiah' => 50000,
            'before_pending_rupiah' => 122000,
            'after_pending_rupiah' => 72000,
            'status' => 'active',
            'audit_event_id' => 'audit-event-refund-due-http-001',
        ]);

        $this->assertDatabaseHas('audit_events', [
            'id' => 'audit-event-refund-due-http-001',
            'aggregate_type' => 'note_revision_surplus_disposition',
            'aggregate_id' => 'disposition-refund-due-http-001',
            'event_name' => 'note_revision_surplus_refund_due_created',
            'actor_id' => (string) $admin->getAuthIdentifier(),
            'actor_role' => 'admin',
            'reason' => 'Customer requested refund due from admin transport.',
            'source_channel' => 'web_admin',
        ]);

        $this->assertDatabaseHas('audit_event_snapshots', [
            'audit_event_id' => 'audit-event-refund-due-http-001',
            'snapshot_kind' => 'before',
        ]);

        $this->assertDatabaseHas('audit_event_snapshots', [
            'audit_event_id' => 'audit-event-refund-due-http-001',
            'snapshot_kind' => 'after',
        ]);
    }

    public function test_refund_due_request_requires_valid_amount_and_reason(): void
    {
        $admin = $this->seedActor('admin-refund-due-validation@example.test', 'admin');
        $this->seedSourceSettlement('settlement-refund-due-http-002', 122000);

        $response = $this->actingAs($admin)
            ->from(route('admin.notes.show', ['noteId' => 'note-root-http-001']))
            ->post(route('admin.notes.revision-settlements.refund-due.store', [
                'settlementId' => 'settlement-refund-due-http-002',
            ]), [
                'amount_rupiah' => 0,
                'reason' => '',
            ]);

        $response->assertRedirect(route('admin.notes.show', ['noteId' => 'note-root-http-001']));
        $response->assertSessionHasErrors(['amount_rupiah', 'reason']);

        $this->assertDatabaseMissing('note_revision_surplus_dispositions', [
            'note_revision_settlement_id' => 'settlement-refund-due-http-002',
        ]);
    }

    public function test_use_case_failure_redirects_back_with_refund_due_error(): void
    {
        $admin = $this->seedActor('admin-refund-due-failure@example.test', 'admin');
        $this->seedSourceSettlement('settlement-refund-due-http-003', 122000);

        $response = $this->actingAs($admin)
            ->from(route('admin.notes.show', ['noteId' => 'note-root-http-001']))
            ->post(route('admin.notes.revision-settlements.refund-due.store', [
                'settlementId' => 'settlement-refund-due-http-003',
            ]), [
                'amount_rupiah' => 122001,
                'reason' => 'Amount exceeds pending surplus.',
            ]);

        $response->assertRedirect(route('admin.notes.show', ['noteId' => 'note-root-http-001']));
        $response->assertSessionHasErrors(['refund_due']);

        $this->assertDatabaseMissing('note_revision_surplus_dispositions', [
            'note_revision_settlement_id' => 'settlement-refund-due-http-003',
        ]);
    }

    public function test_cashier_cannot_access_admin_refund_due_route(): void
    {
        $cashier = $this->seedActor('cashier-refund-due@example.test', 'kasir');
        $this->seedSourceSettlement('settlement-refund-due-http-004', 122000);

        $response = $this->actingAs($cashier)
            ->post(route('admin.notes.revision-settlements.refund-due.store', [
                'settlementId' => 'settlement-refund-due-http-004',
            ]), [
                'amount_rupiah' => 50000,
                'reason' => 'Cashier must not create refund due.',
            ]);

        $response->assertRedirect(route('cashier.dashboard'));

        $this->assertDatabaseMissing('note_revision_surplus_dispositions', [
            'note_revision_settlement_id' => 'settlement-refund-due-http-004',
        ]);
    }

    public function test_admin_without_transaction_capability_can_create_refund_due(): void
    {
        $admin = $this->seedActor('admin-no-transaction-cap-refund-due@example.test', 'admin');
        $this->seedSourceSettlement('settlement-refund-due-http-005', 122000);
        $this->bindDeterministicPorts();

        $response = $this->actingAs($admin)
            ->from(route('admin.notes.show', ['noteId' => 'note-root-http-001']))
            ->post(route('admin.notes.revision-settlements.refund-due.store', [
                'settlementId' => 'settlement-refund-due-http-005',
            ]), [
                'amount_rupiah' => 122000,
                'reason' => 'Admin-only surplus disposition does not reuse transaction capability.',
            ]);

        $response->assertRedirect(route('admin.notes.show', ['noteId' => 'note-root-http-001']));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('note_revision_surplus_dispositions', [
            'note_revision_settlement_id' => 'settlement-refund-due-http-005',
            'disposition_type' => 'refund_due',
            'amount_rupiah' => 122000,
        ]);
    }

    private function seedActor(string $email, string $role): User
    {
        $user = User::query()->create([
            'name' => 'Refund Due Actor',
            'email' => $email,
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => $role,
        ]);

        return $user;
    }

    private function bindDeterministicPorts(): void
    {
        $this->app->instance(UuidPort::class, new SequentialHttpRefundDueUuidPort([
            'disposition-refund-due-http-001',
            'audit-event-refund-due-http-001',
            'audit-snapshot-refund-due-before-http-001',
            'audit-snapshot-refund-due-after-http-001',
        ]));

        $this->app->instance(
            ClockPort::class,
            new FixedHttpRefundDueClockPort(new DateTimeImmutable('2026-05-13 10:00:00')),
        );

        $this->app->forgetInstance(\App\Application\Note\UseCases\CreateNoteRevisionSurplusRefundDueHandler::class);
    }

    private function seedSourceSettlement(
        string $settlementId,
        int $surplusRupiah,
        string $status = 'overpaid_pending',
    ): void {
        DB::table('notes')->insert([
            'id' => 'note-root-http-001',
            'customer_name' => 'Customer HTTP',
            'customer_phone' => '08123456789',
            'transaction_date' => '2026-05-13',
            'note_state' => 'closed',
            'closed_at' => '2026-05-13 09:00:00',
            'closed_by_actor_id' => 'admin-http-001',
            'reopened_at' => null,
            'reopened_by_actor_id' => null,
            'total_rupiah' => 143000,
        ]);

        DB::table('note_revisions')->insert([
            'id' => 'note-revision-http-001',
            'note_root_id' => 'note-root-http-001',
            'revision_number' => 2,
            'parent_revision_id' => null,
            'created_by_actor_id' => 'admin-http-001',
            'reason' => 'HTTP refund due source revision.',
            'customer_name' => 'Customer HTTP',
            'customer_phone' => '08123456789',
            'transaction_date' => '2026-05-13',
            'grand_total_rupiah' => 143000,
            'line_count' => 1,
            'created_at' => '2026-05-13 09:30:00',
            'updated_at' => null,
        ]);

        DB::table('note_revision_settlements')->insert([
            'id' => $settlementId,
            'note_revision_id' => 'note-revision-http-001',
            'note_root_id' => 'note-root-http-001',
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
}

final class SequentialHttpRefundDueUuidPort implements UuidPort
{
    /** @param list<string> $ids */
    public function __construct(private array $ids)
    {
    }

    public function generate(): string
    {
        return array_shift($this->ids) ?? 'generated-http-refund-due-id';
    }
}

final class FixedHttpRefundDueClockPort implements ClockPort
{
    public function __construct(private readonly DateTimeImmutable $now)
    {
    }

    public function now(): DateTimeImmutable
    {
        return $this->now;
    }
}
