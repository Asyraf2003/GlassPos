<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Adapters\Out\Reporting\Queries\TransactionCashLedgerReportingQuery;
use App\Application\Reporting\UseCases\GetInventoryStockValueReportDatasetHandler;
use App\Application\Reporting\UseCases\GetOperationalProfitSummaryHandler;
use App\Application\Reporting\UseCases\GetTransactionReportDatasetHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class TransactionReportingReconciliationFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_golden_master_reconciles_transaction_cash_inventory_and_profit_reports(): void
    {
        $this->seedGoldenMasterFixture();

        $transaction = app(GetTransactionReportDatasetHandler::class)
            ->handle('2026-05-01', '2026-05-31');
        $inventory = app(GetInventoryStockValueReportDatasetHandler::class)
            ->handle('2026-05-01', '2026-05-31');
        $profit = app(GetOperationalProfitSummaryHandler::class)
            ->handle('2026-05-01', '2026-05-31');
        $cashLedger = app(TransactionCashLedgerReportingQuery::class)
            ->reconciliation('2026-05-01', '2026-05-31');

        $this->assertTrue($transaction->isSuccess());
        $this->assertTrue($inventory->isSuccess());
        $this->assertTrue($profit->isSuccess());

        $transactionData = $transaction->data();
        $inventoryData = $inventory->data();
        $profitData = $profit->data();

        $this->assertIsArray($transactionData);
        $this->assertIsArray($inventoryData);
        $this->assertIsArray($profitData);

        $transactionSummary = $transactionData['summary'];
        $inventorySummary = $inventoryData['summary'];
        $profitRow = $profitData['row'];

        $this->assertSame([
            'total_rows' => 2,
            'gross_transaction_rupiah' => 280000,
            'allocated_payment_rupiah' => 320000,
            'refunded_rupiah' => 40000,
            'refund_due_rupiah' => 30000,
            'surplus_refund_paid_rupiah' => 10000,
            'remaining_refund_due_rupiah' => 20000,
            'net_cash_collected_rupiah' => 270000,
            'outstanding_rupiah' => 0,
            'settled_rows' => 2,
            'outstanding_rows' => 0,
        ], $transactionSummary);

        $this->assertSame([
            'total_in_rupiah' => 320000,
            'cash_in_rupiah' => 220000,
            'transfer_in_rupiah' => 100000,
            'total_out_rupiah' => 50000,
        ], $cashLedger);

        $this->assertSame(1, $inventorySummary['snapshot_product_rows']);
        $this->assertSame(1, $inventorySummary['movement_product_rows']);
        $this->assertSame(4, $inventorySummary['total_qty_on_hand']);
        $this->assertSame(160000, $inventorySummary['total_inventory_value_rupiah']);
        $this->assertSame(5, $inventorySummary['period_supply_in_qty']);
        $this->assertSame(2, $inventorySummary['period_sale_out_qty']);
        $this->assertSame(1, $inventorySummary['period_refund_reversal_qty']);
        $this->assertSame(80000, $inventorySummary['period_total_out_cost_rupiah']);
        $this->assertSame(160000, $inventorySummary['period_net_cost_delta_rupiah']);

        $this->assertSame([
            'from_date' => '2026-05-01',
            'to_date' => '2026-05-31',
            'cash_in_rupiah' => 350000,
            'refunded_rupiah' => 50000,
            'external_purchase_cost_rupiah' => 10000,
            'store_stock_cogs_rupiah' => 40000,
            'product_purchase_cost_rupiah' => 50000,
            'operational_expense_rupiah' => 15000,
            'payroll_disbursement_rupiah' => 25000,
            'employee_debt_cash_out_rupiah' => 5000,
            'cash_operational_profit_rupiah' => 205000,
        ], $profitRow);

        $this->assertSame(
            $transactionSummary['net_cash_collected_rupiah'],
            $cashLedger['total_in_rupiah'] - $cashLedger['total_out_rupiah'],
        );
        $this->assertSame(
            $cashLedger['total_out_rupiah'],
            $transactionSummary['refunded_rupiah'] + $transactionSummary['surplus_refund_paid_rupiah'],
        );
        $this->assertSame($cashLedger['total_out_rupiah'], $profitRow['refunded_rupiah']);
        $this->assertSame(
            $transactionSummary['refund_due_rupiah'],
            $profitRow['cash_in_rupiah'] - $transactionSummary['allocated_payment_rupiah'],
        );
    }

    private function seedGoldenMasterFixture(): void
    {
        $this->seedProduct();
        $this->seedTransactionNotes();
        $this->seedCustomerMoney();
        $this->seedInventoryMovements();
        $this->seedOperatingCosts();
        $this->seedSurplusRefund();
    }

    private function seedProduct(): void
    {
        DB::table('products')->insert([
            'id' => 'product-golden-master-1',
            'kode_barang' => 'GM-001',
            'nama_barang' => 'Oli Golden Master',
            'merek' => 'Federal',
            'ukuran' => 100,
            'harga_jual' => 60000,
        ]);

        DB::table('product_inventory')->insert([
            'product_id' => 'product-golden-master-1',
            'qty_on_hand' => 4,
        ]);

        DB::table('product_inventory_costing')->insert([
            'product_id' => 'product-golden-master-1',
            'avg_cost_rupiah' => 40000,
            'inventory_value_rupiah' => 160000,
        ]);
    }

    private function seedTransactionNotes(): void
    {
        DB::table('notes')->insert([
            [
                'id' => 'note-golden-master-service',
                'customer_name' => 'Budi Golden Master',
                'transaction_date' => '2026-05-10',
                'total_rupiah' => 160000,
            ],
            [
                'id' => 'note-golden-master-surplus',
                'customer_name' => 'Siti Golden Master',
                'transaction_date' => '2026-05-11',
                'total_rupiah' => 120000,
            ],
        ]);

        DB::table('work_items')->insert([
            [
                'id' => 'wi-golden-master-stock',
                'note_id' => 'note-golden-master-service',
                'line_no' => 1,
                'transaction_type' => 'service_with_store_stock_part',
                'status' => 'open',
                'subtotal_rupiah' => 150000,
            ],
            [
                'id' => 'wi-golden-master-external',
                'note_id' => 'note-golden-master-service',
                'line_no' => 2,
                'transaction_type' => 'service_with_external_purchase',
                'status' => 'open',
                'subtotal_rupiah' => 50000,
            ],
            [
                'id' => 'wi-golden-master-surplus',
                'note_id' => 'note-golden-master-surplus',
                'line_no' => 1,
                'transaction_type' => 'service_only',
                'status' => 'open',
                'subtotal_rupiah' => 120000,
            ],
        ]);

        DB::table('work_item_store_stock_lines')->insert([
            'id' => 'ssl-golden-master-stock',
            'work_item_id' => 'wi-golden-master-stock',
            'product_id' => 'product-golden-master-1',
            'qty' => 2,
            'line_total_rupiah' => 120000,
        ]);

        DB::table('work_item_external_purchase_lines')->insert([
            'id' => 'ext-golden-master-1',
            'work_item_id' => 'wi-golden-master-external',
            'cost_description' => 'Part luar golden master',
            'unit_cost_rupiah' => 20000,
            'qty' => 1,
            'line_total_rupiah' => 20000,
        ]);
    }

    private function seedCustomerMoney(): void
    {
        DB::table('customer_payments')->insert([
            [
                'id' => 'payment-golden-master-cash',
                'amount_rupiah' => 100000,
                'paid_at' => '2026-05-10',
                'payment_method' => 'cash',
            ],
            [
                'id' => 'payment-golden-master-transfer',
                'amount_rupiah' => 100000,
                'paid_at' => '2026-05-10',
                'payment_method' => 'transfer',
            ],
            [
                'id' => 'payment-golden-master-overpaid',
                'amount_rupiah' => 150000,
                'paid_at' => '2026-05-11',
                'payment_method' => 'cash',
            ],
        ]);

        DB::table('payment_allocations')->insert([
            ['id' => 'pa-gm-cash', 'customer_payment_id' => 'payment-golden-master-cash', 'note_id' => 'note-golden-master-service', 'amount_rupiah' => 100000],
            ['id' => 'pa-gm-transfer', 'customer_payment_id' => 'payment-golden-master-transfer', 'note_id' => 'note-golden-master-service', 'amount_rupiah' => 100000],
            ['id' => 'pa-gm-overpaid', 'customer_payment_id' => 'payment-golden-master-overpaid', 'note_id' => 'note-golden-master-surplus', 'amount_rupiah' => 120000],
        ]);

        DB::table('payment_component_allocations')->insert([
            ['id' => 'pca-gm-stock', 'customer_payment_id' => 'payment-golden-master-cash', 'note_id' => 'note-golden-master-service', 'work_item_id' => 'wi-golden-master-stock', 'component_type' => 'service_store_stock_part', 'component_ref_id' => 'ssl-golden-master-stock', 'component_amount_rupiah_snapshot' => 120000, 'allocated_amount_rupiah' => 120000, 'allocation_priority' => 1],
            ['id' => 'pca-gm-external', 'customer_payment_id' => 'payment-golden-master-transfer', 'note_id' => 'note-golden-master-service', 'work_item_id' => 'wi-golden-master-external', 'component_type' => 'service_external_purchase_part', 'component_ref_id' => 'ext-golden-master-1', 'component_amount_rupiah_snapshot' => 20000, 'allocated_amount_rupiah' => 20000, 'allocation_priority' => 2],
            ['id' => 'pca-gm-service', 'customer_payment_id' => 'payment-golden-master-transfer', 'note_id' => 'note-golden-master-service', 'work_item_id' => 'wi-golden-master-stock', 'component_type' => 'service_fee', 'component_ref_id' => 'wi-golden-master-stock', 'component_amount_rupiah_snapshot' => 80000, 'allocated_amount_rupiah' => 80000, 'allocation_priority' => 3],
        ]);

        DB::table('customer_refunds')->insert([
            'id' => 'refund-golden-master-1',
            'customer_payment_id' => 'payment-golden-master-cash',
            'note_id' => 'note-golden-master-service',
            'amount_rupiah' => 40000,
            'refunded_at' => '2026-05-12 10:00:00',
            'reason' => 'Golden master partial refund.',
        ]);

        DB::table('refund_component_allocations')->insert([
            ['id' => 'rca-gm-stock', 'customer_refund_id' => 'refund-golden-master-1', 'customer_payment_id' => 'payment-golden-master-cash', 'note_id' => 'note-golden-master-service', 'work_item_id' => 'wi-golden-master-stock', 'component_type' => 'service_store_stock_part', 'component_ref_id' => 'ssl-golden-master-stock', 'refunded_amount_rupiah' => 30000, 'refund_priority' => 1],
            ['id' => 'rca-gm-external', 'customer_refund_id' => 'refund-golden-master-1', 'customer_payment_id' => 'payment-golden-master-cash', 'note_id' => 'note-golden-master-service', 'work_item_id' => 'wi-golden-master-external', 'component_type' => 'service_external_purchase_part', 'component_ref_id' => 'ext-golden-master-1', 'refunded_amount_rupiah' => 10000, 'refund_priority' => 2],
        ]);
    }

    private function seedInventoryMovements(): void
    {
        DB::table('inventory_movements')->insert([
            ['id' => 'movement-gm-supply', 'product_id' => 'product-golden-master-1', 'movement_type' => 'stock_in', 'source_type' => 'supplier_receipt_line', 'source_id' => 'receipt-gm-1', 'tanggal_mutasi' => '2026-05-09', 'qty_delta' => 5, 'unit_cost_rupiah' => 40000, 'total_cost_rupiah' => 200000],
            ['id' => 'movement-gm-sale', 'product_id' => 'product-golden-master-1', 'movement_type' => 'stock_out', 'source_type' => 'work_item_store_stock_line', 'source_id' => 'ssl-golden-master-stock', 'tanggal_mutasi' => '2026-05-10', 'qty_delta' => -2, 'unit_cost_rupiah' => 40000, 'total_cost_rupiah' => -80000],
            ['id' => 'movement-gm-refund', 'product_id' => 'product-golden-master-1', 'movement_type' => 'stock_in', 'source_type' => 'work_item_store_stock_line_reversal', 'source_id' => 'ssl-golden-master-stock', 'tanggal_mutasi' => '2026-05-12', 'qty_delta' => 1, 'unit_cost_rupiah' => 40000, 'total_cost_rupiah' => 40000],
        ]);
    }

    private function seedOperatingCosts(): void
    {
        DB::table('employees')->insert([
            'id' => '11111111-1111-1111-1111-111111111111',
            'employee_name' => 'Montir Golden Master',
            'phone' => null,
            'salary_basis_type' => 'weekly',
            'default_salary_amount' => 3000000,
            'employment_status' => 'active',
            'started_at' => null,
            'ended_at' => null,
            'created_at' => '2026-05-01 00:00:00',
            'updated_at' => '2026-05-01 00:00:00',
        ]);

        DB::table('expense_categories')->insert([
            'id' => 'expense-category-gm',
            'code' => 'GM',
            'name' => 'Golden Master',
            'description' => null,
            'is_active' => true,
            'created_at' => '2026-05-01 00:00:00',
            'updated_at' => '2026-05-01 00:00:00',
        ]);

        DB::table('operational_expenses')->insert([
            'id' => 'expense-gm-1',
            'category_id' => 'expense-category-gm',
            'amount_rupiah' => 15000,
            'expense_date' => '2026-05-13',
            'description' => 'Biaya operasional golden master',
            'payment_method' => 'cash',
            'reference_no' => null,
            'created_at' => '2026-05-13 08:00:00',
            'updated_at' => '2026-05-13 08:00:00',
            'deleted_at' => null,
        ]);

        DB::table('payroll_disbursements')->insert([
            'id' => '22222222-2222-2222-2222-222222222222',
            'employee_id' => '11111111-1111-1111-1111-111111111111',
            'amount' => 25000,
            'disbursement_date' => '2026-05-14 12:00:00',
            'mode' => 'weekly',
            'notes' => null,
            'created_at' => '2026-05-14 12:00:00',
            'updated_at' => '2026-05-14 12:00:00',
        ]);

        DB::table('employee_debts')->insert([
            'id' => '33333333-3333-3333-3333-333333333333',
            'employee_id' => '11111111-1111-1111-1111-111111111111',
            'total_debt' => 5000,
            'remaining_balance' => 5000,
            'status' => 'unpaid',
            'notes' => 'Kasbon golden master',
            'created_at' => '2026-05-15 08:00:00',
            'updated_at' => '2026-05-15 08:00:00',
        ]);
    }

    private function seedSurplusRefund(): void
    {
        DB::table('note_revisions')->insert([
            'id' => 'revision-golden-master-surplus',
            'note_root_id' => 'note-golden-master-surplus',
            'revision_number' => 1,
            'parent_revision_id' => null,
            'created_by_actor_id' => null,
            'reason' => 'Golden master surplus fixture',
            'customer_name' => 'Siti Golden Master',
            'customer_phone' => null,
            'transaction_date' => '2026-05-11',
            'grand_total_rupiah' => 120000,
            'line_count' => 1,
            'created_at' => '2026-05-11 09:00:00',
            'updated_at' => null,
        ]);

        DB::table('note_revision_settlements')->insert([
            'id' => 'settlement-golden-master-surplus',
            'note_revision_id' => 'revision-golden-master-surplus',
            'note_root_id' => 'note-golden-master-surplus',
            'gross_total_rupiah' => 120000,
            'carry_forward_paid_rupiah' => 150000,
            'carry_forward_refunded_rupiah' => 0,
            'net_paid_rupiah' => 150000,
            'outstanding_rupiah' => 0,
            'surplus_rupiah' => 30000,
            'settlement_status' => 'overpaid_pending',
            'created_at' => '2026-05-11 09:00:00',
            'updated_at' => null,
        ]);

        DB::table('audit_events')->insert([
            [
                'id' => 'audit-disp-golden-master-surplus',
                'bounded_context' => 'note',
                'aggregate_type' => 'note_revision_surplus_disposition',
                'aggregate_id' => 'disp-golden-master-surplus',
                'event_name' => 'note_revision_surplus_refund_due_created',
                'actor_id' => 'admin-1',
                'actor_role' => 'admin',
                'reason' => 'Golden master surplus due fixture',
                'source_channel' => 'test',
                'request_id' => null,
                'correlation_id' => null,
                'occurred_at' => '2026-05-11 09:30:00',
                'metadata_json' => null,
            ],
            [
                'id' => 'audit-pay-golden-master-surplus',
                'bounded_context' => 'note',
                'aggregate_type' => 'note_revision_surplus_refund_payment',
                'aggregate_id' => 'surplus-payment-golden-master',
                'event_name' => 'note_revision_surplus_refund_paid_recorded',
                'actor_id' => 'admin-1',
                'actor_role' => 'admin',
                'reason' => 'Golden master surplus paid fixture',
                'source_channel' => 'test',
                'request_id' => null,
                'correlation_id' => null,
                'occurred_at' => '2026-05-13 10:00:00',
                'metadata_json' => null,
            ],
        ]);

        DB::table('note_revision_surplus_dispositions')->insert([
            'id' => 'disp-golden-master-surplus',
            'note_revision_settlement_id' => 'settlement-golden-master-surplus',
            'note_root_id' => 'note-golden-master-surplus',
            'note_revision_id' => 'revision-golden-master-surplus',
            'disposition_type' => 'refund_due',
            'amount_rupiah' => 30000,
            'before_pending_rupiah' => 30000,
            'after_pending_rupiah' => 0,
            'status' => 'active',
            'occurred_at' => '2026-05-11 09:30:00',
            'created_at' => '2026-05-11 09:30:00',
            'updated_at' => null,
            'audit_event_id' => 'audit-disp-golden-master-surplus',
        ]);

        DB::table('note_revision_surplus_refund_payments')->insert([
            'id' => 'surplus-payment-golden-master',
            'note_revision_surplus_disposition_id' => 'disp-golden-master-surplus',
            'note_revision_settlement_id' => 'settlement-golden-master-surplus',
            'note_root_id' => 'note-golden-master-surplus',
            'note_revision_id' => 'revision-golden-master-surplus',
            'amount_rupiah' => 10000,
            'effective_date' => '2026-05-13',
            'occurred_at' => '2026-05-13 10:00:00',
            'status' => 'active',
            'idempotency_key' => 'idem-surplus-payment-golden-master',
            'audit_event_id' => 'audit-pay-golden-master-surplus',
            'created_at' => '2026-05-13 10:00:00',
            'updated_at' => null,
        ]);
    }
}
