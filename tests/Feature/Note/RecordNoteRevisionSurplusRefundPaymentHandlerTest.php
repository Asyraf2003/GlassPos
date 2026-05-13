<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Application\Note\UseCases\RecordNoteRevisionSurplusRefundPaymentCommand;
use App\Application\Note\UseCases\RecordNoteRevisionSurplusRefundPaymentHandler;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class RecordNoteRevisionSurplusRefundPaymentHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_records_surplus_refund_payment_with_canonical_audit_without_customer_refund_side_effects(): void
    {
        $this->seedRefundDueDisposition();

        $result = $this->handler()->handle($this->command(
            amountRupiah: 50000,
            idempotencyKey: 'refund-paid-idem-001',
        ));

        self::assertTrue($result->isSuccess());
        self::assertSame(72000, $result->data()['remaining_refund_due_rupiah']);

        $paymentId = (string) $result->data()['refund_payment_id'];

        $this->assertDatabaseHas('note_revision_surplus_refund_payments', [
            'id' => $paymentId,
            'note_revision_surplus_disposition_id' => 'surplus-disposition-test-001',
            'amount_rupiah' => 50000,
            'effective_date' => '2026-05-13',
            'status' => 'active',
            'idempotency_key' => 'refund-paid-idem-001',
        ]);

        $this->assertDatabaseHas('audit_events', [
            'id' => $result->data()['audit_event_id'],
            'bounded_context' => 'note',
            'aggregate_type' => 'note_revision_surplus_refund_payment',
            'aggregate_id' => $paymentId,
            'event_name' => 'note_revision_surplus_refund_paid_recorded',
            'actor_id' => 'admin-test-001',
            'actor_role' => 'admin',
            'reason' => 'Customer received surplus refund.',
        ]);

        self::assertSame(2, DB::table('audit_event_snapshots')
            ->where('audit_event_id', $result->data()['audit_event_id'])
            ->count());

        self::assertSame(0, DB::table('customer_refunds')->count());
        self::assertSame(0, DB::table('refund_component_allocations')->count());
    }

    public function test_rejects_amount_greater_than_remaining_refund_due(): void
    {
        $this->seedRefundDueDisposition();

        $result = $this->handler()->handle($this->command(
            amountRupiah: 200000,
            idempotencyKey: 'refund-paid-idem-002',
        ));

        self::assertTrue($result->isFailure());
        self::assertSame('Nominal surplus refund_paid melebihi sisa refund_due.', $result->message());
        self::assertSame(0, DB::table('note_revision_surplus_refund_payments')->count());
        self::assertSame(0, DB::table('audit_events')
            ->where('event_name', 'note_revision_surplus_refund_paid_recorded')
            ->count());
    }

    public function test_repeated_idempotency_key_with_same_payload_returns_existing_success(): void
    {
        $this->seedRefundDueDisposition();

        $handler = $this->handler();

        $first = $handler->handle($this->command(
            amountRupiah: 50000,
            idempotencyKey: 'refund-paid-idem-003',
        ));
        $second = $handler->handle($this->command(
            amountRupiah: 50000,
            idempotencyKey: 'refund-paid-idem-003',
        ));

        self::assertTrue($first->isSuccess());
        self::assertTrue($second->isSuccess());
        self::assertSame($first->data()['refund_payment_id'], $second->data()['refund_payment_id']);
        self::assertSame(72000, $second->data()['remaining_refund_due_rupiah']);
        self::assertSame(1, DB::table('note_revision_surplus_refund_payments')->count());
        self::assertSame(1, DB::table('audit_events')
            ->where('event_name', 'note_revision_surplus_refund_paid_recorded')
            ->count());
    }

    public function test_repeated_idempotency_key_with_different_payload_is_rejected(): void
    {
        $this->seedRefundDueDisposition();

        $handler = $this->handler();

        $first = $handler->handle($this->command(
            amountRupiah: 50000,
            idempotencyKey: 'refund-paid-idem-004',
        ));
        $second = $handler->handle($this->command(
            amountRupiah: 60000,
            idempotencyKey: 'refund-paid-idem-004',
        ));

        self::assertTrue($first->isSuccess());
        self::assertTrue($second->isFailure());
        self::assertSame(
            'Idempotency key surplus refund_paid sudah digunakan dengan payload berbeda.',
            $second->message(),
        );
        self::assertSame(1, DB::table('note_revision_surplus_refund_payments')->count());
    }

    public function test_rejects_non_admin_actor_and_blank_reason(): void
    {
        $this->seedRefundDueDisposition();

        $nonAdmin = $this->handler()->handle($this->command(
            amountRupiah: 50000,
            idempotencyKey: 'refund-paid-idem-005',
            actorRole: 'cashier',
        ));
        $blankReason = $this->handler()->handle($this->command(
            amountRupiah: 50000,
            idempotencyKey: 'refund-paid-idem-006',
            reason: ' ',
        ));

        self::assertTrue($nonAdmin->isFailure());
        self::assertTrue($blankReason->isFailure());
        self::assertSame(0, DB::table('note_revision_surplus_refund_payments')->count());
    }

    private function handler(): RecordNoteRevisionSurplusRefundPaymentHandler
    {
        return $this->app->make(RecordNoteRevisionSurplusRefundPaymentHandler::class);
    }

    private function command(
        int $amountRupiah,
        string $idempotencyKey,
        string $reason = 'Customer received surplus refund.',
        string $actorRole = 'admin',
    ): RecordNoteRevisionSurplusRefundPaymentCommand {
        return new RecordNoteRevisionSurplusRefundPaymentCommand(
            noteRevisionSurplusDispositionId: 'surplus-disposition-test-001',
            amountRupiah: $amountRupiah,
            effectiveDate: new DateTimeImmutable('2026-05-13'),
            reason: $reason,
            actorId: 'admin-test-001',
            actorRole: $actorRole,
            idempotencyKey: $idempotencyKey,
            occurredAt: new DateTimeImmutable('2026-05-13 11:00:00'),
            sourceChannel: 'web_admin',
            requestId: 'request-test-001',
            correlationId: 'correlation-test-001',
        );
    }

    private function seedRefundDueDisposition(): void
    {
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
            'id' => 'settlement-test-001',
            'note_revision_id' => 'note-revision-test-001',
            'note_root_id' => 'note-root-test-001',
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
            'id' => 'audit-event-disposition-test-001',
            'bounded_context' => 'note',
            'aggregate_type' => 'note_revision_surplus_disposition',
            'aggregate_id' => 'surplus-disposition-test-001',
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
            'id' => 'surplus-disposition-test-001',
            'note_revision_settlement_id' => 'settlement-test-001',
            'note_root_id' => 'note-root-test-001',
            'note_revision_id' => 'note-revision-test-001',
            'disposition_type' => 'refund_due',
            'amount_rupiah' => 122000,
            'before_pending_rupiah' => 122000,
            'after_pending_rupiah' => 0,
            'status' => 'active',
            'occurred_at' => '2026-05-13 10:00:00',
            'created_at' => '2026-05-13 10:00:00',
            'updated_at' => null,
            'audit_event_id' => 'audit-event-disposition-test-001',
        ]);
    }
}
