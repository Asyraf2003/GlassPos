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

final class RecordNoteRevisionSurplusRefundPaymentControllerFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_record_surplus_refund_paid_from_refund_due_disposition(): void
    {
        $admin = $this->seedActor('admin-refund-paid@example.test', 'admin');
        $this->seedRefundDueDisposition();
        $this->bindDeterministicPorts();

        $response = $this->actingAs($admin)
            ->from(route('admin.notes.show', ['noteId' => 'note-root-paid-http-001']))
            ->post('/admin/notes/revision-surplus-dispositions/surplus-disposition-paid-http-001/refund-paid', [
                'amount_rupiah' => 50000,
                'effective_date' => '2026-05-13',
                'reason' => 'Customer received surplus refund paid from admin transport.',
                'idempotency_key' => 'refund-paid-http-idem-001',
            ]);

        $response->assertRedirect(route('admin.notes.show', ['noteId' => 'note-root-paid-http-001']));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('note_revision_surplus_refund_payments', [
            'id' => 'surplus-refund-payment-http-001',
            'note_revision_surplus_disposition_id' => 'surplus-disposition-paid-http-001',
            'note_revision_settlement_id' => 'settlement-paid-http-001',
            'note_root_id' => 'note-root-paid-http-001',
            'note_revision_id' => 'note-revision-paid-http-001',
            'amount_rupiah' => 50000,
            'effective_date' => '2026-05-13',
            'status' => 'active',
            'idempotency_key' => 'refund-paid-http-idem-001',
            'audit_event_id' => 'audit-event-refund-paid-http-001',
        ]);

        $this->assertDatabaseHas('audit_events', [
            'id' => 'audit-event-refund-paid-http-001',
            'bounded_context' => 'note',
            'aggregate_type' => 'note_revision_surplus_refund_payment',
            'aggregate_id' => 'surplus-refund-payment-http-001',
            'event_name' => 'note_revision_surplus_refund_paid_recorded',
            'actor_id' => (string) $admin->getAuthIdentifier(),
            'actor_role' => 'admin',
            'reason' => 'Customer received surplus refund paid from admin transport.',
            'source_channel' => 'web_admin',
        ]);

        $this->assertDatabaseHas('audit_event_snapshots', [
            'audit_event_id' => 'audit-event-refund-paid-http-001',
            'snapshot_kind' => 'before',
        ]);

        $this->assertDatabaseHas('audit_event_snapshots', [
            'audit_event_id' => 'audit-event-refund-paid-http-001',
            'snapshot_kind' => 'after',
        ]);

        self::assertSame(0, DB::table('customer_refunds')->count());
        self::assertSame(0, DB::table('refund_component_allocations')->count());
    }

    public function test_refund_paid_request_requires_valid_amount_effective_date_reason_and_idempotency_key(): void
    {
        $admin = $this->seedActor('admin-refund-paid-validation@example.test', 'admin');
        $this->seedRefundDueDisposition();

        $response = $this->actingAs($admin)
            ->from(route('admin.notes.show', ['noteId' => 'note-root-paid-http-001']))
            ->post('/admin/notes/revision-surplus-dispositions/surplus-disposition-paid-http-001/refund-paid', [
                'amount_rupiah' => 0,
                'effective_date' => '',
                'reason' => '',
                'idempotency_key' => '',
            ]);

        $response->assertRedirect(route('admin.notes.show', ['noteId' => 'note-root-paid-http-001']));
        $response->assertSessionHasErrors(['amount_rupiah', 'effective_date', 'reason', 'idempotency_key']);

        $this->assertDatabaseMissing('note_revision_surplus_refund_payments', [
            'note_revision_surplus_disposition_id' => 'surplus-disposition-paid-http-001',
        ]);
    }

    public function test_use_case_failure_redirects_back_with_refund_paid_error(): void
    {
        $admin = $this->seedActor('admin-refund-paid-failure@example.test', 'admin');
        $this->seedRefundDueDisposition();

        $response = $this->actingAs($admin)
            ->from(route('admin.notes.show', ['noteId' => 'note-root-paid-http-001']))
            ->post('/admin/notes/revision-surplus-dispositions/surplus-disposition-paid-http-001/refund-paid', [
                'amount_rupiah' => 200000,
                'effective_date' => '2026-05-13',
                'reason' => 'Amount exceeds remaining refund due.',
                'idempotency_key' => 'refund-paid-http-idem-overpay',
            ]);

        $response->assertRedirect(route('admin.notes.show', ['noteId' => 'note-root-paid-http-001']));
        $response->assertSessionHasErrors(['refund_paid']);

        $this->assertDatabaseMissing('note_revision_surplus_refund_payments', [
            'note_revision_surplus_disposition_id' => 'surplus-disposition-paid-http-001',
        ]);
    }

    public function test_repeated_refund_paid_submit_with_same_idempotency_key_and_same_payload_reuses_existing_payment(): void
    {
        $admin = $this->seedActor('admin-refund-paid-idem-same@example.test', 'admin');
        $this->seedRefundDueDisposition();
        $this->bindDeterministicPorts();

        $payload = [
            'amount_rupiah' => 50000,
            'effective_date' => '2026-05-13',
            'reason' => 'Customer received surplus refund paid from stale form retry.',
            'idempotency_key' => 'refund-paid-surplus-disposition-paid-http-001-122000',
        ];

        $first = $this->actingAs($admin)
            ->from(route('admin.notes.show', ['noteId' => 'note-root-paid-http-001']))
            ->post('/admin/notes/revision-surplus-dispositions/surplus-disposition-paid-http-001/refund-paid', $payload);

        $first->assertRedirect(route('admin.notes.show', ['noteId' => 'note-root-paid-http-001']));
        $first->assertSessionHas('success');

        $second = $this->actingAs($admin)
            ->from(route('admin.notes.show', ['noteId' => 'note-root-paid-http-001']))
            ->post('/admin/notes/revision-surplus-dispositions/surplus-disposition-paid-http-001/refund-paid', $payload);

        $second->assertRedirect(route('admin.notes.show', ['noteId' => 'note-root-paid-http-001']));
        $second->assertSessionHas('success');

        self::assertSame(1, DB::table('note_revision_surplus_refund_payments')->count());
        self::assertSame(1, DB::table('audit_events')
            ->where('event_name', 'note_revision_surplus_refund_paid_recorded')
            ->count());

        $this->assertDatabaseHas('note_revision_surplus_refund_payments', [
            'note_revision_surplus_disposition_id' => 'surplus-disposition-paid-http-001',
            'amount_rupiah' => 50000,
            'status' => 'active',
            'idempotency_key' => 'refund-paid-surplus-disposition-paid-http-001-122000',
        ]);
    }

    public function test_repeated_refund_paid_submit_with_same_idempotency_key_and_different_payload_is_rejected(): void
    {
        $admin = $this->seedActor('admin-refund-paid-idem-different@example.test', 'admin');
        $this->seedRefundDueDisposition();
        $this->bindDeterministicPorts();

        $first = $this->actingAs($admin)
            ->from(route('admin.notes.show', ['noteId' => 'note-root-paid-http-001']))
            ->post('/admin/notes/revision-surplus-dispositions/surplus-disposition-paid-http-001/refund-paid', [
                'amount_rupiah' => 50000,
                'effective_date' => '2026-05-13',
                'reason' => 'Customer received first surplus refund paid.',
                'idempotency_key' => 'refund-paid-surplus-disposition-paid-http-001-122000',
            ]);

        $first->assertRedirect(route('admin.notes.show', ['noteId' => 'note-root-paid-http-001']));
        $first->assertSessionHas('success');

        $second = $this->actingAs($admin)
            ->from(route('admin.notes.show', ['noteId' => 'note-root-paid-http-001']))
            ->post('/admin/notes/revision-surplus-dispositions/surplus-disposition-paid-http-001/refund-paid', [
                'amount_rupiah' => 60000,
                'effective_date' => '2026-05-13',
                'reason' => 'Customer received changed surplus refund paid from stale form.',
                'idempotency_key' => 'refund-paid-surplus-disposition-paid-http-001-122000',
            ]);

        $second->assertRedirect(route('admin.notes.show', ['noteId' => 'note-root-paid-http-001']));
        $second->assertSessionHasErrors(['refund_paid']);

        self::assertSame(1, DB::table('note_revision_surplus_refund_payments')->count());
        self::assertSame(1, DB::table('audit_events')
            ->where('event_name', 'note_revision_surplus_refund_paid_recorded')
            ->count());

        $this->assertDatabaseHas('note_revision_surplus_refund_payments', [
            'note_revision_surplus_disposition_id' => 'surplus-disposition-paid-http-001',
            'amount_rupiah' => 50000,
            'status' => 'active',
            'idempotency_key' => 'refund-paid-surplus-disposition-paid-http-001-122000',
        ]);

        $this->assertDatabaseMissing('note_revision_surplus_refund_payments', [
            'note_revision_surplus_disposition_id' => 'surplus-disposition-paid-http-001',
            'amount_rupiah' => 60000,
            'status' => 'active',
        ]);
    }

    public function test_cashier_cannot_access_admin_refund_paid_route(): void
    {
        $cashier = $this->seedActor('cashier-refund-paid@example.test', 'kasir');
        $this->seedRefundDueDisposition();

        $response = $this->actingAs($cashier)
            ->post('/admin/notes/revision-surplus-dispositions/surplus-disposition-paid-http-001/refund-paid', [
                'amount_rupiah' => 50000,
                'effective_date' => '2026-05-13',
                'reason' => 'Cashier must not record surplus refund paid.',
                'idempotency_key' => 'refund-paid-http-idem-cashier',
            ]);

        $response->assertRedirect(route('cashier.dashboard'));

        $this->assertDatabaseMissing('note_revision_surplus_refund_payments', [
            'note_revision_surplus_disposition_id' => 'surplus-disposition-paid-http-001',
        ]);
    }

    private function seedActor(string $email, string $role): User
    {
        $user = User::query()->create([
            'name' => 'Refund Paid Actor',
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
        $this->app->instance(UuidPort::class, new SequentialHttpRefundPaidUuidPort([
            'surplus-refund-payment-http-001',
            'audit-event-refund-paid-http-001',
            'audit-snapshot-refund-paid-before-http-001',
            'audit-snapshot-refund-paid-after-http-001',
        ]));

        $this->app->instance(
            ClockPort::class,
            new FixedHttpRefundPaidClockPort(new DateTimeImmutable('2026-05-13 11:00:00')),
        );

        $this->app->forgetInstance(\App\Application\Note\UseCases\RecordNoteRevisionSurplusRefundPaymentHandler::class);
    }

    private function seedRefundDueDisposition(): void
    {
        DB::table('notes')->insert([
            'id' => 'note-root-paid-http-001',
            'customer_name' => 'Customer Refund Paid HTTP',
            'customer_phone' => '08123456789',
            'transaction_date' => '2026-05-13',
            'note_state' => 'closed',
            'closed_at' => '2026-05-13 09:00:00',
            'closed_by_actor_id' => 'admin-paid-http-001',
            'reopened_at' => null,
            'reopened_by_actor_id' => null,
            'total_rupiah' => 143000,
        ]);

        DB::table('note_revisions')->insert([
            'id' => 'note-revision-paid-http-001',
            'note_root_id' => 'note-root-paid-http-001',
            'revision_number' => 2,
            'parent_revision_id' => null,
            'created_by_actor_id' => 'admin-paid-http-001',
            'reason' => 'HTTP refund paid source revision.',
            'customer_name' => 'Customer Refund Paid HTTP',
            'customer_phone' => '08123456789',
            'transaction_date' => '2026-05-13',
            'grand_total_rupiah' => 143000,
            'line_count' => 1,
            'created_at' => '2026-05-13 09:30:00',
            'updated_at' => null,
        ]);

        DB::table('note_revision_settlements')->insert([
            'id' => 'settlement-paid-http-001',
            'note_revision_id' => 'note-revision-paid-http-001',
            'note_root_id' => 'note-root-paid-http-001',
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
            'id' => 'audit-event-disposition-paid-http-001',
            'bounded_context' => 'note',
            'aggregate_type' => 'note_revision_surplus_disposition',
            'aggregate_id' => 'surplus-disposition-paid-http-001',
            'event_name' => 'note_revision_surplus_refund_due_created',
            'actor_id' => 'admin-paid-http-001',
            'actor_role' => 'admin',
            'reason' => 'Customer requested refund due before cash out.',
            'source_channel' => 'web_admin',
            'request_id' => null,
            'correlation_id' => null,
            'occurred_at' => '2026-05-13 10:00:00',
            'metadata_json' => null,
        ]);

        DB::table('note_revision_surplus_dispositions')->insert([
            'id' => 'surplus-disposition-paid-http-001',
            'note_revision_settlement_id' => 'settlement-paid-http-001',
            'note_root_id' => 'note-root-paid-http-001',
            'note_revision_id' => 'note-revision-paid-http-001',
            'disposition_type' => 'refund_due',
            'amount_rupiah' => 122000,
            'before_pending_rupiah' => 122000,
            'after_pending_rupiah' => 0,
            'status' => 'active',
            'occurred_at' => '2026-05-13 10:00:00',
            'created_at' => '2026-05-13 10:00:00',
            'updated_at' => null,
            'audit_event_id' => 'audit-event-disposition-paid-http-001',
        ]);
    }
}

final class SequentialHttpRefundPaidUuidPort implements UuidPort
{
    /** @param list<string> $ids */
    public function __construct(private array $ids)
    {
    }

    public function generate(): string
    {
        return array_shift($this->ids) ?? 'generated-http-refund-paid-id';
    }
}

final class FixedHttpRefundPaidClockPort implements ClockPort
{
    public function __construct(private readonly DateTimeImmutable $now)
    {
    }

    public function now(): DateTimeImmutable
    {
        return $this->now;
    }
}
