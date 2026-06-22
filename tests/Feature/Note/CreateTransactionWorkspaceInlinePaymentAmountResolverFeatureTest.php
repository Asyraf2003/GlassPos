<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Application\Note\Services\CreateTransactionWorkspaceInlinePaymentAmountResolver;
use App\Core\Note\Note\Note;
use App\Core\Note\WorkItem\ServiceDetail;
use App\Core\Note\WorkItem\WorkItem;
use App\Core\Shared\Exceptions\DomainException;
use App\Core\Shared\ValueObjects\Money;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class CreateTransactionWorkspaceInlinePaymentAmountResolverFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_pay_full_uses_full_note_total_when_no_payment_exists(): void
    {
        $note = $this->seedNote('note-inline-amount-1', 100000);

        $amount = app(CreateTransactionWorkspaceInlinePaymentAmountResolver::class)->resolve($note, [
            'decision' => 'pay_full',
        ]);

        $this->assertSame(100000, $amount);
    }

    public function test_pay_full_uses_outstanding_after_existing_allocation(): void
    {
        $note = $this->seedNote('note-inline-amount-2', 100000);
        $this->seedPayment('payment-inline-amount-2', 40000);
        $this->seedLegacyAllocation('allocation-inline-amount-2', 'payment-inline-amount-2', 'note-inline-amount-2', 40000);

        $amount = app(CreateTransactionWorkspaceInlinePaymentAmountResolver::class)->resolve($note, [
            'decision' => 'pay_full',
        ]);

        $this->assertSame(60000, $amount);
    }

    public function test_pay_full_reopens_outstanding_after_refund(): void
    {
        $note = $this->seedNote('note-inline-amount-3', 100000);
        $this->seedPayment('payment-inline-amount-3', 100000);
        $this->seedComponentAllocation(
            'pca-inline-amount-3',
            'payment-inline-amount-3',
            'note-inline-amount-3',
            100000
        );
        $this->seedRefund('refund-inline-amount-3', 'payment-inline-amount-3', 'note-inline-amount-3', 40000);

        $amount = app(CreateTransactionWorkspaceInlinePaymentAmountResolver::class)->resolve($note, [
            'decision' => 'pay_full',
        ]);

        $this->assertSame(40000, $amount);
    }

    public function test_pay_full_uses_legacy_allocation_when_component_allocation_is_missing(): void
    {
        $note = $this->seedNote('note-inline-amount-4', 100000);
        $this->seedPayment('payment-inline-amount-4', 30000);
        $this->seedLegacyAllocation('allocation-inline-amount-4', 'payment-inline-amount-4', 'note-inline-amount-4', 30000);

        $amount = app(CreateTransactionWorkspaceInlinePaymentAmountResolver::class)->resolve($note, [
            'decision' => 'pay_full',
        ]);

        $this->assertSame(70000, $amount);
    }

    public function test_pay_partial_allows_amount_below_outstanding(): void
    {
        $note = $this->seedNote('note-inline-amount-5', 100000);
        $this->seedPayment('payment-inline-amount-5', 40000);
        $this->seedLegacyAllocation('allocation-inline-amount-5', 'payment-inline-amount-5', 'note-inline-amount-5', 40000);

        $amount = app(CreateTransactionWorkspaceInlinePaymentAmountResolver::class)->resolve($note, [
            'decision' => 'pay_partial',
            'amount_paid_rupiah' => 50000,
        ]);

        $this->assertSame(50000, $amount);
    }

    public function test_pay_partial_rejects_amount_equal_to_outstanding(): void
    {
        $note = $this->seedNote('note-inline-amount-6', 100000);
        $this->seedPayment('payment-inline-amount-6', 40000);
        $this->seedLegacyAllocation('allocation-inline-amount-6', 'payment-inline-amount-6', 'note-inline-amount-6', 40000);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Nominal pembayaran sebagian harus lebih kecil dari sisa tagihan.');

        app(CreateTransactionWorkspaceInlinePaymentAmountResolver::class)->resolve($note, [
            'decision' => 'pay_partial',
            'amount_paid_rupiah' => 60000,
        ]);
    }

    public function test_pay_partial_rejects_when_note_has_no_outstanding(): void
    {
        $note = $this->seedNote('note-inline-amount-7', 100000);
        $this->seedPayment('payment-inline-amount-7', 100000);
        $this->seedLegacyAllocation('allocation-inline-amount-7', 'payment-inline-amount-7', 'note-inline-amount-7', 100000);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Nota sudah tidak memiliki sisa tagihan.');

        app(CreateTransactionWorkspaceInlinePaymentAmountResolver::class)->resolve($note, [
            'decision' => 'pay_partial',
            'amount_paid_rupiah' => 10000,
        ]);
    }

    public function test_invalid_payment_decision_is_rejected(): void
    {
        $note = $this->seedNote('note-inline-amount-8', 100000);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Keputusan pembayaran workspace tidak valid.');

        app(CreateTransactionWorkspaceInlinePaymentAmountResolver::class)->resolve($note, [
            'decision' => 'pay_later_because_humans_keep_inventing_states',
        ]);
    }

    private function seedNote(string $id, int $total): Note
    {
        DB::table('notes')->insert([
            'id' => $id,
            'customer_name' => 'Budi Inline Amount',
            'customer_phone' => '0811111111',
            'transaction_date' => '2026-03-15',
            'total_rupiah' => $total,
            'note_state' => Note::STATE_OPEN,
        ]);

        return Note::rehydrate(
            $id,
            'Budi Inline Amount',
            '0811111111',
            new DateTimeImmutable('2026-03-15'),
            Money::fromInt($total),
            [],
            Note::STATE_OPEN,
        );
    }

    private function seedPayment(string $id, int $amount): void
    {
        DB::table('customer_payments')->insert([
            'id' => $id,
            'amount_rupiah' => $amount,
            'paid_at' => '2026-03-15',
            'payment_method' => 'transfer',
        ]);
    }

    private function seedLegacyAllocation(string $id, string $paymentId, string $noteId, int $amount): void
    {
        DB::table('payment_allocations')->insert([
            'id' => $id,
            'customer_payment_id' => $paymentId,
            'note_id' => $noteId,
            'amount_rupiah' => $amount,
        ]);
    }

    private function seedComponentAllocation(string $id, string $paymentId, string $noteId, int $amount): void
    {
        $workItemId = 'wi-' . $id;

        DB::table('work_items')->insert([
            'id' => $workItemId,
            'note_id' => $noteId,
            'line_no' => 1,
            'transaction_type' => WorkItem::TYPE_SERVICE_ONLY,
            'status' => WorkItem::STATUS_OPEN,
            'subtotal_rupiah' => $amount,
        ]);

        DB::table('work_item_service_details')->insert([
            'work_item_id' => $workItemId,
            'service_name' => 'Servis Inline Amount Component',
            'service_price_rupiah' => $amount,
            'part_source' => ServiceDetail::PART_SOURCE_NONE,
        ]);

        DB::table('payment_component_allocations')->insert([
            'id' => $id,
            'customer_payment_id' => $paymentId,
            'note_id' => $noteId,
            'work_item_id' => $workItemId,
            'component_type' => 'service_fee',
            'component_ref_id' => $workItemId,
            'component_amount_rupiah_snapshot' => $amount,
            'allocated_amount_rupiah' => $amount,
            'allocation_priority' => 1,
        ]);
    }

    private function seedRefund(string $id, string $paymentId, string $noteId, int $amount): void
    {
        DB::table('customer_refunds')->insert([
            'id' => $id,
            'customer_payment_id' => $paymentId,
            'note_id' => $noteId,
            'amount_rupiah' => $amount,
            'refunded_at' => '2026-03-15',
            'reason' => 'Refund resolver test',
        ]);

        DB::table('refund_component_allocations')->insert([
            'id' => 'rca-' . $id,
            'customer_refund_id' => $id,
            'customer_payment_id' => $paymentId,
            'note_id' => $noteId,
            'work_item_id' => 'wi-pca-inline-amount-3',
            'component_type' => 'service_fee',
            'component_ref_id' => 'wi-pca-inline-amount-3',
            'refunded_amount_rupiah' => $amount,
            'refund_priority' => 1,
        ]);
    }
}
