<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Application\Reporting\UseCases\GetTransactionSummaryPerNoteHandler;
use App\Application\Shared\DTO\Result;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class GetTransactionSummaryPerNoteFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_transaction_summary_per_note_handler_returns_note_level_summary_and_passes_reconciliation(): void
    {
        $this->seedNote('note-1', 'Budi', '2026-03-14', 100000);
        $this->seedNote('note-2', 'Siti', '2026-03-15', 50000);

        $this->seedCustomerPayment('payment-1', 80000, '2026-03-15');
        $this->seedCustomerPayment('payment-2', 50000, '2026-03-16');

        $this->seedPaymentAllocation('allocation-1', 'payment-1', 'note-1', 60000);
        $this->seedPaymentAllocation('allocation-2', 'payment-2', 'note-2', 50000);

        $this->seedCustomerRefund('refund-1', 'payment-1', 'note-1', 10000, '2026-03-16', 'Koreksi');

        $result = app(GetTransactionSummaryPerNoteHandler::class)
            ->handle('2026-03-14', '2026-03-15');

        $this->assertInstanceOf(Result::class, $result);
        $this->assertTrue($result->isSuccess());

        $data = $result->data();

        $this->assertIsArray($data);
        $this->assertArrayHasKey('rows', $data);

        $this->assertSame([
            [
                'note_id' => 'note-1',
                'transaction_date' => '2026-03-14',
                'customer_name' => 'Budi',
                'gross_transaction_rupiah' => 100000,
                'allocated_payment_rupiah' => 60000,
                'refunded_rupiah' => 10000,
                'refund_due_rupiah' => 0,
                'net_cash_collected_rupiah' => 50000,
                'outstanding_rupiah' => 50000,
                'payment_status_label' => 'Ada Refund',
            ],
            [
                'note_id' => 'note-2',
                'transaction_date' => '2026-03-15',
                'customer_name' => 'Siti',
                'gross_transaction_rupiah' => 50000,
                'allocated_payment_rupiah' => 50000,
                'refunded_rupiah' => 0,
                'refund_due_rupiah' => 0,
                'net_cash_collected_rupiah' => 50000,
                'outstanding_rupiah' => 0,
                'payment_status_label' => 'Lunas',
            ],
        ], $data['rows']);
    }

    private function seedNote(string $id, string $customerName, string $transactionDate, int $totalRupiah): void
    {
        DB::table('notes')->insert([
            'id' => $id,
            'customer_name' => $customerName,
            'transaction_date' => $transactionDate,
            'total_rupiah' => $totalRupiah,
        ]);
    }

    private function seedCustomerPayment(string $id, int $amountRupiah, string $paidAt): void
    {
        DB::table('customer_payments')->insert([
            'id' => $id,
            'amount_rupiah' => $amountRupiah,
            'paid_at' => $paidAt,
        ]);
    }

    private function seedPaymentAllocation(string $id, string $paymentId, string $noteId, int $amountRupiah): void
    {
        DB::table('payment_allocations')->insert([
            'id' => $id,
            'customer_payment_id' => $paymentId,
            'note_id' => $noteId,
            'amount_rupiah' => $amountRupiah,
        ]);
    }

    private function seedCustomerRefund(
        string $id,
        string $paymentId,
        string $noteId,
        int $amountRupiah,
        string $refundedAt,
        string $reason,
    ): void {
        DB::table('customer_refunds')->insert([
            'id' => $id,
            'customer_payment_id' => $paymentId,
            'note_id' => $noteId,
            'amount_rupiah' => $amountRupiah,
            'refunded_at' => $refundedAt,
            'reason' => $reason,
        ]);
    }
}
