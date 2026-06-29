<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Adapters\Out\Reporting\Queries\TransactionCashLedgerReportingQuery;
use App\Application\Note\UseCases\CreateNoteRevisionHandler;
use App\Application\Note\UseCases\CreateTransactionWorkspaceHandler;
use App\Application\Payment\UseCases\RecordAndAllocateNotePaymentHandler;
use App\Application\Reporting\UseCases\GetDashboardOperationalPerformanceDatasetHandler;
use App\Application\Reporting\UseCases\GetInventoryMovementSummaryHandler;
use App\Application\Reporting\UseCases\GetOperationalProfitSummaryHandler;
use App\Application\Reporting\UseCases\GetTransactionReportDatasetHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class TransactionEditRefundPaymentStockReportingHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_paid_store_stock_revision_upward_preserves_payment_creates_outstanding_delta_and_reconciles_reports(): void
    {
        $this->seedStoreStockProduct();

        $create = app(CreateTransactionWorkspaceHandler::class)->handle($this->createPaidStoreStockPayload());

        self::assertTrue($create->isSuccess(), $create->message());

        $noteId = (string) ($create->data()['note']['id'] ?? '');
        self::assertNotSame('', $noteId);

        $oldWorkItemId = (string) DB::table('work_items')->where('note_id', $noteId)->value('id');
        $oldStoreStockLineId = (string) DB::table('work_item_store_stock_lines')
            ->where('work_item_id', $oldWorkItemId)
            ->value('id');
        $oldPaymentId = (string) DB::table('customer_payments')->value('id');

        self::assertNotSame('', $oldWorkItemId);
        self::assertNotSame('', $oldStoreStockLineId);
        self::assertNotSame('', $oldPaymentId);

        $this->assertDatabaseHas('note_history_projection', [
            'note_id' => $noteId,
            'total_rupiah' => 250000,
            'allocated_rupiah' => 250000,
            'refunded_rupiah' => 0,
            'net_paid_rupiah' => 250000,
            'outstanding_rupiah' => 0,
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => 'product-0062-a',
            'movement_type' => 'stock_out',
            'source_type' => 'work_item_store_stock_line',
            'source_id' => $oldStoreStockLineId,
            'tanggal_mutasi' => '2026-05-20',
            'qty_delta' => -2,
            'unit_cost_rupiah' => 40000,
            'total_cost_rupiah' => -80000,
        ]);
        self::assertSame(1, DB::table('customer_payments')->count());
        self::assertSame(250000, (int) DB::table('customer_payments')->sum('amount_rupiah'));

        $revision = app(CreateNoteRevisionHandler::class)->handle(
            $noteId,
            $this->upwardStoreStockRevisionPayload(),
            'admin-0062-a',
            false,
        );

        self::assertTrue($revision->isSuccess(), $revision->message());

        $newWorkItemId = (string) DB::table('work_items')->where('note_id', $noteId)->value('id');
        $newStoreStockLineId = (string) DB::table('work_item_store_stock_lines')
            ->where('work_item_id', $newWorkItemId)
            ->value('id');

        self::assertNotSame('', $newWorkItemId);
        self::assertNotSame('', $newStoreStockLineId);
        self::assertNotSame($oldWorkItemId, $newWorkItemId);
        self::assertNotSame($oldStoreStockLineId, $newStoreStockLineId);

        $this->assertDatabaseHas('customer_payments', [
            'id' => $oldPaymentId,
            'amount_rupiah' => 250000,
            'paid_at' => '2026-05-20',
            'payment_method' => 'cash',
        ]);
        $this->assertDatabaseHas('note_history_projection', [
            'note_id' => $noteId,
            'total_rupiah' => 350000,
            'allocated_rupiah' => 250000,
            'refunded_rupiah' => 0,
            'net_paid_rupiah' => 250000,
            'outstanding_rupiah' => 100000,
        ]);
        self::assertSame(1, DB::table('customer_payments')->count());
        self::assertSame(250000, (int) DB::table('customer_payments')->sum('amount_rupiah'));
        self::assertSame(250000, app(TransactionCashLedgerReportingQuery::class)
            ->reconciliation('2026-05-01', '2026-05-31')['total_in_rupiah']);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => 'product-0062-a',
            'movement_type' => 'stock_in',
            'source_type' => 'transaction_workspace_updated',
            'source_id' => $oldStoreStockLineId,
            'tanggal_mutasi' => '2026-05-21',
            'qty_delta' => 2,
            'unit_cost_rupiah' => 40000,
            'total_cost_rupiah' => 80000,
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => 'product-0062-a',
            'movement_type' => 'stock_out',
            'source_type' => 'work_item_store_stock_line',
            'source_id' => $newStoreStockLineId,
            'tanggal_mutasi' => '2026-05-21',
            'qty_delta' => -3,
            'unit_cost_rupiah' => 40000,
            'total_cost_rupiah' => -120000,
        ]);
        self::assertSame(1, DB::table('inventory_movements')
            ->where('source_type', 'transaction_workspace_updated')
            ->where('source_id', $oldStoreStockLineId)
            ->count());
        self::assertSame(0, DB::table('inventory_movements')
            ->where('source_type', 'work_item_store_stock_line_reversal')
            ->count());
        $this->assertDatabaseHas('product_inventory', [
            'product_id' => 'product-0062-a',
            'qty_on_hand' => 7,
        ]);
        $this->assertDatabaseHas('product_inventory_costing', [
            'product_id' => 'product-0062-a',
            'avg_cost_rupiah' => 40000,
            'inventory_value_rupiah' => 280000,
        ]);

        $payment = app(RecordAndAllocateNotePaymentHandler::class)->handle(
            $noteId,
            100000,
            '2026-05-22',
            [],
            'cash',
            100000,
        );

        self::assertTrue($payment->isSuccess(), $payment->message());

        $this->assertDatabaseHas('note_history_projection', [
            'note_id' => $noteId,
            'total_rupiah' => 350000,
            'allocated_rupiah' => 350000,
            'refunded_rupiah' => 0,
            'net_paid_rupiah' => 350000,
            'outstanding_rupiah' => 0,
        ]);
        self::assertSame(2, DB::table('customer_payments')->count());
        self::assertSame(350000, (int) DB::table('customer_payments')->sum('amount_rupiah'));

        $transaction = app(GetTransactionReportDatasetHandler::class)
            ->handle('2026-05-01', '2026-05-31');
        $cashLedger = app(TransactionCashLedgerReportingQuery::class)
            ->reconciliation('2026-05-01', '2026-05-31');
        $inventoryMovement = app(GetInventoryMovementSummaryHandler::class)
            ->handle('2026-05-01', '2026-05-31');
        $profit = app(GetOperationalProfitSummaryHandler::class)
            ->handle('2026-05-01', '2026-05-31');

        self::assertTrue($transaction->isSuccess());
        self::assertTrue($inventoryMovement->isSuccess());
        self::assertTrue($profit->isSuccess());

        $transactionSummary = $transaction->data()['summary'];
        self::assertSame(1, $transactionSummary['total_rows']);
        self::assertSame(350000, $transactionSummary['gross_transaction_rupiah']);
        self::assertSame(350000, $transactionSummary['allocated_payment_rupiah']);
        self::assertSame(0, $transactionSummary['refunded_rupiah']);
        self::assertSame(350000, $transactionSummary['net_cash_collected_rupiah']);
        self::assertSame(0, $transactionSummary['outstanding_rupiah']);
        self::assertSame(1, $transactionSummary['settled_rows']);
        self::assertSame(0, $transactionSummary['outstanding_rows']);

        self::assertSame([
            'total_in_rupiah' => 350000,
            'cash_in_rupiah' => 350000,
            'transfer_in_rupiah' => 0,
            'total_out_rupiah' => 0,
        ], $cashLedger);

        $movementRow = $inventoryMovement->data()['rows'][0];
        self::assertSame('product-0062-a', $movementRow['product_id']);
        self::assertSame(5, $movementRow['sale_out_qty']);
        self::assertSame(0, $movementRow['refund_reversal_qty']);
        self::assertSame(2, $movementRow['revision_correction_qty']);
        self::assertSame(-3, $movementRow['net_qty_delta']);
        self::assertSame(80000, $movementRow['total_in_cost_rupiah']);
        self::assertSame(200000, $movementRow['total_out_cost_rupiah']);
        self::assertSame(-120000, $movementRow['net_cost_delta_rupiah']);
        self::assertSame(7, $movementRow['current_qty_on_hand']);
        self::assertSame(280000, $movementRow['current_inventory_value_rupiah']);

        $profitRow = $profit->data()['row'];
        self::assertSame(350000, $profitRow['cash_in_rupiah']);
        self::assertSame(0, $profitRow['refunded_rupiah']);
        self::assertSame(120000, $profitRow['store_stock_cogs_rupiah']);
        self::assertSame(120000, $profitRow['product_purchase_cost_rupiah']);
        self::assertSame(230000, $profitRow['cash_operational_profit_rupiah']);

        $dashboard = app(GetDashboardOperationalPerformanceDatasetHandler::class)
            ->handle('2026-05-01', '2026-05-31');

        self::assertSame(230000, $dashboard['summary']['total_operational_profit_rupiah']);
    }

    public function test_paid_store_stock_revision_downward_preserves_payment_creates_surplus_policy_and_reconciles_reports(): void
    {
        $this->seedStoreStockProduct();

        $create = app(CreateTransactionWorkspaceHandler::class)->handle($this->createOverpaidStoreStockPayload());

        self::assertTrue($create->isSuccess(), $create->message());

        $noteId = (string) ($create->data()['note']['id'] ?? '');
        self::assertNotSame('', $noteId);

        $oldWorkItemId = (string) DB::table('work_items')->where('note_id', $noteId)->value('id');
        $oldStoreStockLineId = (string) DB::table('work_item_store_stock_lines')
            ->where('work_item_id', $oldWorkItemId)
            ->value('id');
        $oldPaymentId = (string) DB::table('customer_payments')->value('id');

        self::assertNotSame('', $oldWorkItemId);
        self::assertNotSame('', $oldStoreStockLineId);
        self::assertNotSame('', $oldPaymentId);

        $this->assertDatabaseHas('note_history_projection', [
            'note_id' => $noteId,
            'total_rupiah' => 350000,
            'allocated_rupiah' => 350000,
            'refunded_rupiah' => 0,
            'net_paid_rupiah' => 350000,
            'outstanding_rupiah' => 0,
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => 'product-0062-a',
            'movement_type' => 'stock_out',
            'source_type' => 'work_item_store_stock_line',
            'source_id' => $oldStoreStockLineId,
            'tanggal_mutasi' => '2026-05-20',
            'qty_delta' => -3,
            'unit_cost_rupiah' => 40000,
            'total_cost_rupiah' => -120000,
        ]);

        $revision = app(CreateNoteRevisionHandler::class)->handle(
            $noteId,
            $this->downwardStoreStockRevisionPayload(),
            'admin-0062-b',
            false,
        );

        self::assertTrue($revision->isSuccess(), $revision->message());

        $newWorkItemId = (string) DB::table('work_items')->where('note_id', $noteId)->value('id');
        $newStoreStockLineId = (string) DB::table('work_item_store_stock_lines')
            ->where('work_item_id', $newWorkItemId)
            ->value('id');

        self::assertNotSame('', $newWorkItemId);
        self::assertNotSame('', $newStoreStockLineId);
        self::assertNotSame($oldWorkItemId, $newWorkItemId);
        self::assertNotSame($oldStoreStockLineId, $newStoreStockLineId);

        $this->assertDatabaseHas('customer_payments', [
            'id' => $oldPaymentId,
            'amount_rupiah' => 350000,
            'paid_at' => '2026-05-20',
            'payment_method' => 'cash',
        ]);
        self::assertSame(1, DB::table('customer_payments')->count());
        self::assertSame(350000, (int) DB::table('customer_payments')->sum('amount_rupiah'));
        self::assertSame(250000, (int) DB::table('payment_component_allocations')
            ->where('note_id', $noteId)
            ->sum('allocated_amount_rupiah'));

        $this->assertDatabaseHas('note_revision_settlements', [
            'note_revision_id' => $noteId . '-r002',
            'note_root_id' => $noteId,
            'gross_total_rupiah' => 250000,
            'carry_forward_paid_rupiah' => 350000,
            'carry_forward_refunded_rupiah' => 0,
            'net_paid_rupiah' => 350000,
            'outstanding_rupiah' => 0,
            'surplus_rupiah' => 100000,
            'settlement_status' => 'overpaid_pending',
        ]);
        $this->assertDatabaseHas('note_history_projection', [
            'note_id' => $noteId,
            'total_rupiah' => 250000,
            'allocated_rupiah' => 250000,
            'refunded_rupiah' => 0,
            'net_paid_rupiah' => 350000,
            'outstanding_rupiah' => 0,
        ]);
        self::assertSame(0, DB::table('customer_refunds')->count());
        self::assertSame(0, DB::table('note_revision_surplus_dispositions')->count());
        self::assertSame(0, DB::table('note_revision_surplus_refund_payments')->count());

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => 'product-0062-a',
            'movement_type' => 'stock_in',
            'source_type' => 'transaction_workspace_updated',
            'source_id' => $oldStoreStockLineId,
            'tanggal_mutasi' => '2026-05-21',
            'qty_delta' => 3,
            'unit_cost_rupiah' => 40000,
            'total_cost_rupiah' => 120000,
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => 'product-0062-a',
            'movement_type' => 'stock_out',
            'source_type' => 'work_item_store_stock_line',
            'source_id' => $newStoreStockLineId,
            'tanggal_mutasi' => '2026-05-21',
            'qty_delta' => -2,
            'unit_cost_rupiah' => 40000,
            'total_cost_rupiah' => -80000,
        ]);
        self::assertSame(1, DB::table('inventory_movements')
            ->where('source_type', 'transaction_workspace_updated')
            ->where('source_id', $oldStoreStockLineId)
            ->count());
        self::assertSame(0, DB::table('inventory_movements')
            ->where('source_type', 'work_item_store_stock_line_reversal')
            ->count());
        $this->assertDatabaseHas('product_inventory', [
            'product_id' => 'product-0062-a',
            'qty_on_hand' => 8,
        ]);
        $this->assertDatabaseHas('product_inventory_costing', [
            'product_id' => 'product-0062-a',
            'avg_cost_rupiah' => 40000,
            'inventory_value_rupiah' => 320000,
        ]);

        $transaction = app(GetTransactionReportDatasetHandler::class)
            ->handle('2026-05-01', '2026-05-31');
        $cashLedger = app(TransactionCashLedgerReportingQuery::class)
            ->reconciliation('2026-05-01', '2026-05-31');
        $inventoryMovement = app(GetInventoryMovementSummaryHandler::class)
            ->handle('2026-05-01', '2026-05-31');
        $profit = app(GetOperationalProfitSummaryHandler::class)
            ->handle('2026-05-01', '2026-05-31');

        self::assertTrue($transaction->isSuccess());
        self::assertTrue($inventoryMovement->isSuccess());
        self::assertTrue($profit->isSuccess());

        $transactionSummary = $transaction->data()['summary'];
        self::assertSame(1, $transactionSummary['total_rows']);
        self::assertSame(250000, $transactionSummary['gross_transaction_rupiah']);
        self::assertSame(250000, $transactionSummary['allocated_payment_rupiah']);
        self::assertSame(0, $transactionSummary['refunded_rupiah']);
        self::assertSame(0, $transactionSummary['refund_due_rupiah']);
        self::assertSame(250000, $transactionSummary['net_cash_collected_rupiah']);
        self::assertSame(0, $transactionSummary['outstanding_rupiah']);
        self::assertSame(1, $transactionSummary['settled_rows']);

        self::assertSame([
            'total_in_rupiah' => 250000,
            'cash_in_rupiah' => 250000,
            'transfer_in_rupiah' => 0,
            'total_out_rupiah' => 0,
        ], $cashLedger);

        $movementRow = $inventoryMovement->data()['rows'][0];
        self::assertSame('product-0062-a', $movementRow['product_id']);
        self::assertSame(5, $movementRow['sale_out_qty']);
        self::assertSame(0, $movementRow['refund_reversal_qty']);
        self::assertSame(3, $movementRow['revision_correction_qty']);
        self::assertSame(-2, $movementRow['net_qty_delta']);
        self::assertSame(120000, $movementRow['total_in_cost_rupiah']);
        self::assertSame(200000, $movementRow['total_out_cost_rupiah']);
        self::assertSame(-80000, $movementRow['net_cost_delta_rupiah']);
        self::assertSame(8, $movementRow['current_qty_on_hand']);
        self::assertSame(320000, $movementRow['current_inventory_value_rupiah']);

        $profitRow = $profit->data()['row'];
        self::assertSame(350000, $profitRow['cash_in_rupiah']);
        self::assertSame(0, $profitRow['refunded_rupiah']);
        self::assertSame(80000, $profitRow['store_stock_cogs_rupiah']);
        self::assertSame(80000, $profitRow['product_purchase_cost_rupiah']);
        self::assertSame(270000, $profitRow['cash_operational_profit_rupiah']);

        $dashboard = app(GetDashboardOperationalPerformanceDatasetHandler::class)
            ->handle('2026-05-01', '2026-05-31');

        self::assertSame(270000, $dashboard['summary']['total_operational_profit_rupiah']);
    }

    private function seedStoreStockProduct(): void
    {
        DB::table('products')->insert([
            'id' => 'product-0062-a',
            'kode_barang' => '0062-A',
            'nama_barang' => 'Oli Hardening 0062 A',
            'merek' => 'Federal',
            'ukuran' => 100,
            'harga_jual' => 100000,
        ]);

        DB::table('product_inventory')->insert([
            'product_id' => 'product-0062-a',
            'qty_on_hand' => 10,
        ]);

        DB::table('product_inventory_costing')->insert([
            'product_id' => 'product-0062-a',
            'avg_cost_rupiah' => 40000,
            'inventory_value_rupiah' => 400000,
        ]);
    }

    /** @return array<string, mixed> */
    private function createPaidStoreStockPayload(): array
    {
        return [
            'idempotency_key' => '0062-a-create-paid-store-stock',
            'note' => [
                'customer_name' => 'Budi 0062 A',
                'customer_phone' => '08123456789',
                'transaction_date' => '2026-05-20',
            ],
            'items' => [[
                'entry_mode' => 'service',
                'description' => null,
                'part_source' => 'store_stock',
                'service' => [
                    'name' => 'Servis 0062 A',
                    'price_rupiah' => 50000,
                    'notes' => null,
                ],
                'product_lines' => [[
                    'product_id' => 'product-0062-a',
                    'qty' => 2,
                    'unit_price_rupiah' => 100000,
                    'price_basis' => 'current_catalog',
                ]],
                'external_purchase_lines' => [],
            ]],
            'inline_payment' => [
                'decision' => 'pay_full',
                'payment_method' => 'cash',
                'paid_at' => '2026-05-20',
                'amount_paid_rupiah' => 250000,
                'amount_received_rupiah' => 250000,
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function createOverpaidStoreStockPayload(): array
    {
        return [
            'idempotency_key' => '0062-b-create-paid-store-stock',
            'note' => [
                'customer_name' => 'Budi 0062 B',
                'customer_phone' => '08123456789',
                'transaction_date' => '2026-05-20',
            ],
            'items' => [[
                'entry_mode' => 'service',
                'description' => null,
                'part_source' => 'store_stock',
                'service' => [
                    'name' => 'Servis 0062 B',
                    'price_rupiah' => 50000,
                    'notes' => null,
                ],
                'product_lines' => [[
                    'product_id' => 'product-0062-a',
                    'qty' => 3,
                    'unit_price_rupiah' => 100000,
                    'price_basis' => 'current_catalog',
                ]],
                'external_purchase_lines' => [],
            ]],
            'inline_payment' => [
                'decision' => 'pay_full',
                'payment_method' => 'cash',
                'paid_at' => '2026-05-20',
                'amount_paid_rupiah' => 350000,
                'amount_received_rupiah' => 350000,
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function upwardStoreStockRevisionPayload(): array
    {
        return [
            'reason' => '0062-A paid store-stock upward revision hardening.',
            'note' => [
                'customer_name' => 'Budi 0062 A Revised',
                'customer_phone' => '08123456789',
                'transaction_date' => '2026-05-21',
            ],
            'items' => [[
                'entry_mode' => 'service',
                'description' => null,
                'part_source' => 'store_stock',
                'service' => [
                    'name' => 'Servis 0062 A Revised',
                    'price_rupiah' => 50000,
                    'notes' => null,
                ],
                'product_lines' => [[
                    'product_id' => 'product-0062-a',
                    'qty' => 3,
                    'unit_price_rupiah' => 100000,
                    'price_basis' => 'revision_snapshot',
                ]],
                'external_purchase_lines' => [],
            ]],
            'inline_payment' => [
                'decision' => 'skip',
                'payment_method' => null,
                'paid_at' => null,
                'amount_paid_rupiah' => null,
                'amount_received_rupiah' => null,
            ],
        ];
    }

    /** @return array<string, mixed> */
    private function downwardStoreStockRevisionPayload(): array
    {
        return [
            'reason' => '0062-B paid store-stock downward revision hardening.',
            'note' => [
                'customer_name' => 'Budi 0062 B Revised',
                'customer_phone' => '08123456789',
                'transaction_date' => '2026-05-21',
            ],
            'items' => [[
                'entry_mode' => 'service',
                'description' => null,
                'part_source' => 'store_stock',
                'service' => [
                    'name' => 'Servis 0062 B Revised',
                    'price_rupiah' => 50000,
                    'notes' => null,
                ],
                'product_lines' => [[
                    'product_id' => 'product-0062-a',
                    'qty' => 2,
                    'unit_price_rupiah' => 100000,
                    'price_basis' => 'revision_snapshot',
                ]],
                'external_purchase_lines' => [],
            ]],
            'inline_payment' => [
                'decision' => 'skip',
                'payment_method' => null,
                'paid_at' => null,
                'amount_paid_rupiah' => null,
                'amount_received_rupiah' => null,
            ],
        ];
    }
}
