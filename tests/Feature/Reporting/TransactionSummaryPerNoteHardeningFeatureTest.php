<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Application\Reporting\UseCases\GetTransactionSummaryPerNoteHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class TransactionSummaryPerNoteHardeningFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_transaction_summary_period_parity_matches_expected_totals_and_component_rows_take_precedence_over_legacy_rows(): void
    {
        $this->seedNote('note-1', 'Budi', '2030-01-07', 100000);
        $this->seedNote('note-2', 'Siti', '2030-01-09', 50000);
        $this->seedNote('note-3', 'Outside', '2030-02-01', 30000);

        $this->seedWorkItem('wi-1', 'note-1', 1, 100000);
        $this->seedWorkItem('wi-2', 'note-2', 1, 50000);
        $this->seedWorkItem('wi-3', 'note-3', 1, 30000);

        $this->seedCustomerPayment('payment-1', 70000, '2030-01-07');
        $this->seedCustomerPayment('payment-2', 50000, '2030-01-09');
        $this->seedCustomerPayment('payment-3', 30000, '2030-02-01');

        $this->seedPaymentAllocation('allocation-1', 'payment-1', 'note-1', 99999);
        $this->seedPaymentAllocation('allocation-2', 'payment-2', 'note-2', 50000);
        $this->seedPaymentAllocation('allocation-3', 'payment-3', 'note-3', 30000);

        DB::table('payment_component_allocations')->insert([
            [
                'id' => 'pca-1',
                'customer_payment_id' => 'payment-1',
                'note_id' => 'note-1',
                'work_item_id' => 'wi-1',
                'component_type' => 'service_fee',
                'component_ref_id' => 'wi-1',
                'component_amount_rupiah_snapshot' => 100000,
                'allocated_amount_rupiah' => 70000,
                'allocation_priority' => 1,
            ],
        ]);

        $this->seedCustomerRefund('refund-1', 'payment-1', 'note-1', 9000, '2030-01-08', 'Koreksi');
        $this->seedCustomerRefund('refund-2', 'payment-3', 'note-3', 3000, '2030-02-01', 'Outside');

        DB::table('refund_component_allocations')->insert([
            [
                'id' => 'rca-1',
                'customer_refund_id' => 'refund-1',
                'customer_payment_id' => 'payment-1',
                'note_id' => 'note-1',
                'work_item_id' => 'wi-1',
                'component_type' => 'service_fee',
                'component_ref_id' => 'wi-1',
                'refunded_amount_rupiah' => 5000,
                'refund_priority' => 1,
            ],
        ]);

        $daily = $this->summaryTotals('2030-01-07', '2030-01-07');
        $weekly = $this->summaryTotals('2030-01-07', '2030-01-13');
        $monthly = $this->summaryTotals('2030-01-01', '2030-01-31');
        $custom = $this->summaryTotals('2030-01-01', '2030-01-31');

        $this->assertSame([
            'total_rows' => 1,
            'gross_transaction_rupiah' => 100000,
            'allocated_payment_rupiah' => 99999,
            'refunded_rupiah' => 9000,
            'refund_due_rupiah' => 0,
            'net_cash_collected_rupiah' => 90999,
            'outstanding_rupiah' => 9001,
        ], $daily);

        $this->assertSame([
            'total_rows' => 2,
            'gross_transaction_rupiah' => 150000,
            'allocated_payment_rupiah' => 149999,
            'refunded_rupiah' => 9000,
            'net_cash_collected_rupiah' => 140999,
            'outstanding_rupiah' => 9001,
        ], $weekly);

        $this->assertSame([
            'total_rows' => 2,
            'gross_transaction_rupiah' => 150000,
            'allocated_payment_rupiah' => 149999,
            'refunded_rupiah' => 9000,
            'net_cash_collected_rupiah' => 140999,
            'outstanding_rupiah' => 9001,
        ], $monthly);

        $this->assertSame($monthly, $custom);

        $result = app(GetTransactionSummaryPerNoteHandler::class)
            ->handle('2030-01-01', '2030-01-31');

        $this->assertTrue($result->isSuccess());

        $rows = $result->data()['rows'] ?? [];
        $this->assertIsArray($rows);

        $this->assertSame([
            'note_id' => 'note-1',
            'transaction_date' => '2030-01-07',
            'customer_name' => 'Budi',
            'gross_transaction_rupiah' => 100000,
            'allocated_payment_rupiah' => 99999,
            'refunded_rupiah' => 9000,
            'net_cash_collected_rupiah' => 90999,
            'outstanding_rupiah' => 9001,
            'payment_status_label' => 'Ada Refund',
        ], $rows[0]);
    }

    private function summaryTotals(string $from, string $to): array
    {
        $result = app(GetTransactionSummaryPerNoteHandler::class)->handle($from, $to);

        $this->assertTrue($result->isSuccess());

        $data = $result->data();
        $this->assertIsArray($data);

        $rows = $data['rows'] ?? [];
        $this->assertIsArray($rows);

        return [
            'total_rows' => count($rows),
            'gross_transaction_rupiah' => array_sum(array_column($rows, 'gross_transaction_rupiah')),
            'allocated_payment_rupiah' => array_sum(array_column($rows, 'allocated_payment_rupiah')),
            'refunded_rupiah' => array_sum(array_column($rows, 'refunded_rupiah')),
            'net_cash_collected_rupiah' => array_sum(array_column($rows, 'net_cash_collected_rupiah')),
            'outstanding_rupiah' => array_sum(array_column($rows, 'outstanding_rupiah')),
        ];
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

    private function seedWorkItem(string $id, string $noteId, int $lineNo, int $subtotalRupiah): void
    {
        DB::table('work_items')->insert([
            'id' => $id,
            'note_id' => $noteId,
            'line_no' => $lineNo,
            'transaction_type' => 'service_only',
            'status' => 'open',
            'subtotal_rupiah' => $subtotalRupiah,
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
