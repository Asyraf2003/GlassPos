<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Application\Reporting\UseCases\GetTransactionReportDatasetHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class GetTransactionReportDatasetFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_transaction_report_dataset_returns_single_exact_dataset_from_summary_rows(): void
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

        $this->seedRefundDueDisposition('disp-1', 'note-1', 'rev-1', 'settlement-1', 7000);
        $this->seedRefundDueDisposition('disp-3', 'note-3', 'rev-3', 'settlement-3', 3000);

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

        $result = app(GetTransactionReportDatasetHandler::class)
            ->handle('2030-01-01', '2030-01-31');

        $this->assertTrue($result->isSuccess());

        $data = $result->data();
        $this->assertIsArray($data);

        $rows = $data['rows'] ?? null;
        $summary = $data['summary'] ?? null;
        $periodRows = $data['period_rows'] ?? null;
        $customerRows = $data['customer_rows'] ?? null;

        $this->assertIsArray($rows);
        $this->assertIsArray($summary);
        $this->assertIsArray($periodRows);
        $this->assertIsArray($customerRows);

        $this->assertCount(2, $rows);

        $this->assertSame([
            'total_rows' => 2,
            'gross_transaction_rupiah' => 150000,
            'allocated_payment_rupiah' => 149999,
            'refunded_rupiah' => 9000,
            'refund_due_rupiah' => 7000,
            'net_cash_collected_rupiah' => 140999,
            'outstanding_rupiah' => 9001,
            'settled_rows' => 1,
            'outstanding_rows' => 1,
        ], $summary);

        $this->assertSame([
            [
                'period_label' => '2030-01-07',
                'total_rows' => 1,
                'gross_transaction_rupiah' => 100000,
                'allocated_payment_rupiah' => 99999,
                'refunded_rupiah' => 9000,
                'refund_due_rupiah' => 7000,
                'net_cash_collected_rupiah' => 90999,
                'outstanding_rupiah' => 9001,
            ],
            [
                'period_label' => '2030-01-09',
                'total_rows' => 1,
                'gross_transaction_rupiah' => 50000,
                'allocated_payment_rupiah' => 50000,
                'refunded_rupiah' => 0,
                'refund_due_rupiah' => 0,
                'net_cash_collected_rupiah' => 50000,
                'outstanding_rupiah' => 0,
            ],
        ], $periodRows);

        $this->assertSame([
            [
                'customer_name' => 'Budi',
                'total_rows' => 1,
                'gross_transaction_rupiah' => 100000,
                'allocated_payment_rupiah' => 99999,
                'refunded_rupiah' => 9000,
                'refund_due_rupiah' => 7000,
                'net_cash_collected_rupiah' => 90999,
                'outstanding_rupiah' => 9001,
            ],
            [
                'customer_name' => 'Siti',
                'total_rows' => 1,
                'gross_transaction_rupiah' => 50000,
                'allocated_payment_rupiah' => 50000,
                'refunded_rupiah' => 0,
                'refund_due_rupiah' => 0,
                'net_cash_collected_rupiah' => 50000,
                'outstanding_rupiah' => 0,
            ],
        ], $customerRows);

        $this->assertSame(
            $summary['gross_transaction_rupiah'],
            array_sum(array_column($rows, 'gross_transaction_rupiah'))
        );

        $this->assertSame(
            $summary['allocated_payment_rupiah'],
            array_sum(array_column($rows, 'allocated_payment_rupiah'))
        );

        $this->assertSame(
            $summary['refunded_rupiah'],
            array_sum(array_column($rows, 'refunded_rupiah'))
        );

        $this->assertSame(
            $summary['refund_due_rupiah'],
            array_sum(array_column($rows, 'refund_due_rupiah'))
        );

        $this->assertSame(
            $summary['net_cash_collected_rupiah'],
            array_sum(array_column($rows, 'net_cash_collected_rupiah'))
        );

        $this->assertSame(
            $summary['outstanding_rupiah'],
            array_sum(array_column($rows, 'outstanding_rupiah'))
        );
    }

    public function test_transaction_report_dataset_distinguishes_refund_due_surplus_refund_paid_and_remaining_refund_due(): void
    {
        $this->seedNote('note-surplus-paid-report', 'Budi Surplus Paid', '2030-01-10', 100000);
        $this->seedWorkItem('wi-surplus-paid-report', 'note-surplus-paid-report', 1, 100000);

        $this->seedCustomerPayment('payment-surplus-paid-report', 100000, '2030-01-10');
        $this->seedPaymentAllocation('allocation-surplus-paid-report', 'payment-surplus-paid-report', 'note-surplus-paid-report', 100000);
        $this->seedCustomerRefund('refund-customer-report', 'payment-surplus-paid-report', 'note-surplus-paid-report', 9000, '2030-01-10', 'Customer refund');

        $this->seedRefundDueDisposition('disp-surplus-paid-report', 'note-surplus-paid-report', 'rev-surplus-paid-report', 'settlement-surplus-paid-report', 7000);
        $this->seedSurplusRefundPayment('surplus-payment-report', 'disp-surplus-paid-report', 'note-surplus-paid-report', 'rev-surplus-paid-report', 'settlement-surplus-paid-report', 3000, '2030-01-10');

        $result = app(GetTransactionReportDatasetHandler::class)
            ->handle('2030-01-01', '2030-01-31');

        $this->assertTrue($result->isSuccess());

        $data = $result->data();
        $summary = $data['summary'];

        $this->assertSame(9000, $summary['refunded_rupiah']);
        $this->assertSame(7000, $summary['refund_due_rupiah']);
        $this->assertSame(3000, $summary['surplus_refund_paid_rupiah']);
        $this->assertSame(4000, $summary['remaining_refund_due_rupiah']);

        $row = $data['rows'][0];

        $this->assertSame(9000, $row['refunded_rupiah']);
        $this->assertSame(7000, $row['refund_due_rupiah']);
        $this->assertSame(3000, $row['surplus_refund_paid_rupiah']);
        $this->assertSame(4000, $row['remaining_refund_due_rupiah']);
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
    private function seedRefundDueDisposition(
        string $id,
        string $noteId,
        string $revisionId,
        string $settlementId,
        int $amountRupiah,
    ): void {
        DB::table('note_revisions')->insert([
            'id' => $revisionId,
            'note_root_id' => $noteId,
            'revision_number' => 1,
            'parent_revision_id' => null,
            'created_by_actor_id' => null,
            'reason' => 'Report refund due fixture',
            'customer_name' => 'Reporting Customer',
            'customer_phone' => null,
            'transaction_date' => '2030-01-07',
            'grand_total_rupiah' => 100000,
            'line_count' => 0,
            'created_at' => '2030-01-07 09:00:00',
            'updated_at' => null,
        ]);

        DB::table('note_revision_settlements')->insert([
            'id' => $settlementId,
            'note_revision_id' => $revisionId,
            'note_root_id' => $noteId,
            'gross_total_rupiah' => 100000,
            'carry_forward_paid_rupiah' => 107000,
            'carry_forward_refunded_rupiah' => 0,
            'net_paid_rupiah' => 107000,
            'outstanding_rupiah' => 0,
            'surplus_rupiah' => $amountRupiah,
            'settlement_status' => 'overpaid_pending',
            'created_at' => '2030-01-07 09:00:00',
            'updated_at' => null,
        ]);

        DB::table('audit_events')->insert([
            'id' => 'audit-' . $id,
            'bounded_context' => 'note',
            'aggregate_type' => 'note_revision_surplus_disposition',
            'aggregate_id' => $id,
            'event_name' => 'note_revision_surplus_refund_due_created',
            'actor_id' => 'admin-1',
            'actor_role' => 'admin',
            'reason' => 'Report refund due fixture',
            'source_channel' => 'test',
            'request_id' => null,
            'correlation_id' => null,
            'occurred_at' => '2030-01-07 09:30:00',
            'metadata_json' => null,
        ]);

        DB::table('note_revision_surplus_dispositions')->insert([
            'id' => $id,
            'note_revision_settlement_id' => $settlementId,
            'note_root_id' => $noteId,
            'note_revision_id' => $revisionId,
            'disposition_type' => 'refund_due',
            'amount_rupiah' => $amountRupiah,
            'before_pending_rupiah' => $amountRupiah,
            'after_pending_rupiah' => 0,
            'status' => 'active',
            'occurred_at' => '2030-01-07 09:30:00',
            'created_at' => '2030-01-07 09:30:00',
            'updated_at' => null,
            'audit_event_id' => 'audit-' . $id,
        ]);
    }
}
\n    private function seedSurplusRefundPayment(
        string $id,
        string $dispositionId,
        string $noteId,
        string $revisionId,
        string $settlementId,
        int $amountRupiah,
        string $effectiveDate,
    ): void {
        DB::table('audit_events')->insert([
            'id' => 'audit-' . $id,
            'bounded_context' => 'note',
            'aggregate_type' => 'note_revision_surplus_refund_payment',
            'aggregate_id' => $id,
            'event_name' => 'note_revision_surplus_refund_paid_recorded',
            'actor_id' => 'admin-1',
            'actor_role' => 'admin',
            'reason' => 'Report surplus refund paid fixture',
            'source_channel' => 'test',
            'request_id' => null,
            'correlation_id' => null,
            'occurred_at' => $effectiveDate . ' 10:00:00',
            'metadata_json' => null,
        ]);

        DB::table('note_revision_surplus_refund_payments')->insert([
            'id' => $id,
            'note_revision_surplus_disposition_id' => $dispositionId,
            'note_revision_settlement_id' => $settlementId,
            'note_root_id' => $noteId,
            'note_revision_id' => $revisionId,
            'amount_rupiah' => $amountRupiah,
            'effective_date' => $effectiveDate,
            'occurred_at' => $effectiveDate . ' 10:00:00',
            'status' => 'active',
            'idempotency_key' => 'idem-' . $id,
            'audit_event_id' => 'audit-' . $id,
            'created_at' => $effectiveDate . ' 10:00:00',
            'updated_at' => null,
        ]);
    }

}\n