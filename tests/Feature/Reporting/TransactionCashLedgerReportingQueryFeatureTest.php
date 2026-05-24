<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Adapters\Out\Reporting\Queries\TransactionCashLedgerReportingQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class TransactionCashLedgerReportingQueryFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_groups_cash_events_from_cash_records(): void
    {
        $this->seedNote('note-1', 'Budi', '2026-04-02', 26000);
        $this->seedNote('note-2', 'Sari', '2026-04-03', 10000);

        $this->seedWorkItem('wi-1', 'note-1', 1, 5000);
        $this->seedWorkItem('wi-2', 'note-1', 2, 3000);
        $this->seedWorkItem('wi-3', 'note-2', 1, 10000);

        $this->seedCustomerPayment('pay-1', 8000, '2026-04-02');
        $this->seedCustomerPayment('pay-2', 4000, '2026-04-03');

        DB::table('payment_allocations')->insert([
            [
                'id' => 'pa-1',
                'customer_payment_id' => 'pay-1',
                'note_id' => 'note-1',
                'amount_rupiah' => 8000,
            ],
            [
                'id' => 'pa-2',
                'customer_payment_id' => 'pay-2',
                'note_id' => 'note-2',
                'amount_rupiah' => 4000,
            ],
        ]);

        $this->seedCustomerRefund('ref-1', 'pay-1', 'note-1', 1000, '2026-04-04', 'Refund');

        DB::table('payment_component_allocations')->insert([
            [
                'id' => 'p1',
                'customer_payment_id' => 'pay-1',
                'note_id' => 'note-1',
                'work_item_id' => 'wi-1',
                'component_type' => 'product_only_work_item',
                'component_ref_id' => 'wi-1',
                'component_amount_rupiah_snapshot' => 5000,
                'allocated_amount_rupiah' => 5000,
                'allocation_priority' => 1,
            ],
            [
                'id' => 'p2',
                'customer_payment_id' => 'pay-1',
                'note_id' => 'note-1',
                'work_item_id' => 'wi-2',
                'component_type' => 'service_store_stock_part',
                'component_ref_id' => 'sto-2',
                'component_amount_rupiah_snapshot' => 3000,
                'allocated_amount_rupiah' => 3000,
                'allocation_priority' => 2,
            ],
            [
                'id' => 'p3',
                'customer_payment_id' => 'pay-2',
                'note_id' => 'note-2',
                'work_item_id' => 'wi-3',
                'component_type' => 'service_fee',
                'component_ref_id' => 'wi-3',
                'component_amount_rupiah_snapshot' => 10000,
                'allocated_amount_rupiah' => 4000,
                'allocation_priority' => 1,
            ],
        ]);

        DB::table('refund_component_allocations')->insert([
            [
                'id' => 'r1',
                'customer_refund_id' => 'ref-1',
                'customer_payment_id' => 'pay-1',
                'note_id' => 'note-1',
                'work_item_id' => 'wi-2',
                'component_type' => 'service_fee',
                'component_ref_id' => 'wi-2',
                'refunded_amount_rupiah' => 1000,
                'refund_priority' => 1,
            ],
        ]);

        $query = app(TransactionCashLedgerReportingQuery::class);
        $rows = $query->rows('2026-04-01', '2026-04-30');
        $recon = $query->reconciliation('2026-04-01', '2026-04-30');

        $this->assertCount(3, $rows);
        $this->assertSame('payment_allocation', $rows[0]['event_type']);
        $this->assertSame(8000, $rows[0]['event_amount_rupiah']);
        $this->assertSame('payment_allocation', $rows[1]['event_type']);
        $this->assertSame(4000, $rows[1]['event_amount_rupiah']);
        $this->assertSame('refund', $rows[2]['event_type']);
        $this->assertSame(1000, $rows[2]['event_amount_rupiah']);
        $this->assertSame(12000, $recon['total_in_rupiah']);
        $this->assertSame(1000, $recon['total_out_rupiah']);
    }

    public function test_cash_ledger_uses_cash_records_when_component_allocations_are_rebuilt_after_refund_revision(): void
    {
        $this->seedNote('note-revision-cash', 'Budi Revision', '2026-04-30', 0);
        $this->seedWorkItem('wi-old-refunded', 'note-revision-cash', 1, 122000);
        $this->seedWorkItem('wi-current', 'note-revision-cash', 2, 143000);

        $this->seedCustomerPayment('pay-revision', 265000, '2026-04-30');

        DB::table('payment_allocations')->insert([
            'id' => 'pa-revision',
            'customer_payment_id' => 'pay-revision',
            'note_id' => 'note-revision-cash',
            'amount_rupiah' => 265000,
        ]);

        DB::table('payment_component_allocations')->insert([
            'id' => 'pca-current-after-rebuild',
            'customer_payment_id' => 'pay-revision',
            'note_id' => 'note-revision-cash',
            'work_item_id' => 'wi-current',
            'component_type' => 'product_only_work_item',
            'component_ref_id' => 'wi-current',
            'component_amount_rupiah_snapshot' => 143000,
            'allocated_amount_rupiah' => 143000,
            'allocation_priority' => 1,
        ]);

        $this->seedCustomerRefund('refund-old-line', 'pay-revision', 'note-revision-cash', 122000, '2026-04-30', 'Refund old component');
        $this->seedCustomerRefund('refund-current-line', 'pay-revision', 'note-revision-cash', 143000, '2026-04-30', 'Refund current component');

        DB::table('refund_component_allocations')->insert([
            [
                'id' => 'rca-old-line',
                'customer_refund_id' => 'refund-old-line',
                'customer_payment_id' => 'pay-revision',
                'note_id' => 'note-revision-cash',
                'work_item_id' => 'wi-old-refunded',
                'component_type' => 'product_only_work_item',
                'component_ref_id' => 'wi-old-refunded',
                'refunded_amount_rupiah' => 122000,
                'refund_priority' => 1,
            ],
            [
                'id' => 'rca-current-line',
                'customer_refund_id' => 'refund-current-line',
                'customer_payment_id' => 'pay-revision',
                'note_id' => 'note-revision-cash',
                'work_item_id' => 'wi-current',
                'component_type' => 'product_only_work_item',
                'component_ref_id' => 'wi-current',
                'refunded_amount_rupiah' => 143000,
                'refund_priority' => 1,
            ],
        ]);

        $query = app(TransactionCashLedgerReportingQuery::class);
        $recon = $query->reconciliation('2026-04-01', '2026-04-30');

        $this->assertSame(265000, $recon['total_in_rupiah']);
        $this->assertSame(265000, $recon['total_out_rupiah']);
    }


    public function test_cash_ledger_includes_surplus_refund_paid_as_separate_outflow(): void
    {
        $this->seedNote('note-surplus-paid-ledger', 'Budi Surplus Ledger', '2026-04-04', 100000);
        $this->seedRefundDueDisposition('disp-surplus-paid-ledger', 'note-surplus-paid-ledger', 'rev-surplus-paid-ledger', 'settlement-surplus-paid-ledger', 7000);
        $this->seedSurplusRefundPayment('surplus-payment-ledger', 'disp-surplus-paid-ledger', 'note-surplus-paid-ledger', 'rev-surplus-paid-ledger', 'settlement-surplus-paid-ledger', 3000, '2026-04-05');

        $query = app(TransactionCashLedgerReportingQuery::class);
        $rows = $query->rows('2026-04-01', '2026-04-30');
        $recon = $query->reconciliation('2026-04-01', '2026-04-30');

        $this->assertCount(1, $rows);
        $this->assertSame('note-surplus-paid-ledger', $rows[0]['note_id']);
        $this->assertSame('2026-04-05', $rows[0]['event_date']);
        $this->assertSame('surplus_refund_paid', $rows[0]['event_type']);
        $this->assertSame('out', $rows[0]['direction']);
        $this->assertSame(3000, $rows[0]['event_amount_rupiah']);
        $this->assertNull($rows[0]['customer_payment_id']);
        $this->assertNull($rows[0]['refund_id']);
        $this->assertSame('note_revision_surplus_refund_payments', $rows[0]['source_table']);
        $this->assertSame('surplus-payment-ledger', $rows[0]['source_id']);
        $this->assertSame('disp-surplus-paid-ledger', $rows[0]['source_disposition_id']);

        $this->assertSame(0, $recon['total_in_rupiah']);
        $this->assertSame(3000, $recon['total_out_rupiah']);
    }


    public function test_cash_ledger_reads_component_allocation_money_in_and_splits_cash_vs_transfer(): void
    {
        $this->seedNote('note-cash-ledger-create-cash', 'Cash Create Ledger', '2026-05-24', 85000);
        $this->seedNote('note-cash-ledger-create-transfer', 'Transfer Create Ledger', '2026-05-24', 85000);

        $this->seedWorkItem('wi-cash-ledger-create-cash', 'note-cash-ledger-create-cash', 1, 85000);
        $this->seedWorkItem('wi-cash-ledger-create-transfer', 'note-cash-ledger-create-transfer', 1, 85000);

        $this->seedCustomerPaymentWithMethod('pay-cash-ledger-create-cash', 85000, '2026-05-24', 'cash');
        $this->seedCustomerPaymentWithMethod('pay-cash-ledger-create-transfer', 30000, '2026-05-24', 'transfer');

        DB::table('payment_component_allocations')->insert([
            [
                'id' => 'pca-cash-ledger-create-cash',
                'customer_payment_id' => 'pay-cash-ledger-create-cash',
                'note_id' => 'note-cash-ledger-create-cash',
                'work_item_id' => 'wi-cash-ledger-create-cash',
                'component_type' => 'service_fee',
                'component_ref_id' => 'wi-cash-ledger-create-cash',
                'component_amount_rupiah_snapshot' => 85000,
                'allocated_amount_rupiah' => 85000,
                'allocation_priority' => 1,
            ],
            [
                'id' => 'pca-cash-ledger-create-transfer',
                'customer_payment_id' => 'pay-cash-ledger-create-transfer',
                'note_id' => 'note-cash-ledger-create-transfer',
                'work_item_id' => 'wi-cash-ledger-create-transfer',
                'component_type' => 'service_fee',
                'component_ref_id' => 'wi-cash-ledger-create-transfer',
                'component_amount_rupiah_snapshot' => 85000,
                'allocated_amount_rupiah' => 30000,
                'allocation_priority' => 1,
            ],
        ]);

        $query = app(TransactionCashLedgerReportingQuery::class);
        $rows = $query->rows('2026-05-01', '2026-05-31');
        $recon = $query->reconciliation('2026-05-01', '2026-05-31');

        $this->assertCount(2, $rows);

        $cashRow = collect($rows)->firstWhere('customer_payment_id', 'pay-cash-ledger-create-cash');
        $transferRow = collect($rows)->firstWhere('customer_payment_id', 'pay-cash-ledger-create-transfer');

        $this->assertNotNull($cashRow);
        $this->assertNotNull($transferRow);

        $this->assertSame('payment_allocation', $cashRow['event_type']);
        $this->assertSame('in', $cashRow['direction']);
        $this->assertSame('cash', $cashRow['payment_method']);
        $this->assertSame(85000, $cashRow['event_amount_rupiah']);
        $this->assertSame('payment_component_allocations', $cashRow['source_table']);

        $this->assertSame('payment_allocation', $transferRow['event_type']);
        $this->assertSame('in', $transferRow['direction']);
        $this->assertSame('transfer', $transferRow['payment_method']);
        $this->assertSame(30000, $transferRow['event_amount_rupiah']);
        $this->assertSame('payment_component_allocations', $transferRow['source_table']);

        $this->assertSame(115000, $recon['total_in_rupiah']);
        $this->assertSame(85000, $recon['cash_in_rupiah']);
        $this->assertSame(30000, $recon['transfer_in_rupiah']);
        $this->assertSame(0, $recon['total_out_rupiah']);
    }


    private function seedCustomerPaymentWithMethod(string $id, int $amountRupiah, string $paidAt, string $paymentMethod): void
    {
        DB::table('customer_payments')->insert([
            'id' => $id,
            'amount_rupiah' => $amountRupiah,
            'paid_at' => $paidAt,
            'payment_method' => $paymentMethod,
        ]);
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

    private function seedCustomerRefund(
        string $id,
        string $paymentId,
        string $noteId,
        int $amountRupiah,
        string $refundedAt,
        string $reason
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
            'reason' => 'Cash ledger refund due fixture',
            'customer_name' => 'Cash Ledger Customer',
            'customer_phone' => null,
            'transaction_date' => '2026-04-04',
            'grand_total_rupiah' => 100000,
            'line_count' => 0,
            'created_at' => '2026-04-04 09:00:00',
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
            'created_at' => '2026-04-04 09:00:00',
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
            'reason' => 'Cash ledger refund due fixture',
            'source_channel' => 'test',
            'request_id' => null,
            'correlation_id' => null,
            'occurred_at' => '2026-04-04 09:30:00',
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
            'occurred_at' => '2026-04-04 09:30:00',
            'created_at' => '2026-04-04 09:30:00',
            'updated_at' => null,
            'audit_event_id' => 'audit-' . $id,
        ]);
    }

    private function seedSurplusRefundPayment(
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
            'reason' => 'Cash ledger surplus refund paid fixture',
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

}
