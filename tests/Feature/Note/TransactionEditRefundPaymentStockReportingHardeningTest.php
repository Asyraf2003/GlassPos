<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Adapters\Out\Reporting\Queries\TransactionCashLedgerReportingQuery;
use App\Application\Note\Services\NoteDetailPageDataBuilder;
use App\Application\Note\Services\NoteRevisionSurplusDispositionActionViewDataBuilder;
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
            array_replace($this->upwardStoreStockRevisionPayload(), ['base_revision_id' => $this->revisionBaseForTest($noteId)]),
            'admin-0062-a',
            false,
        );

        self::assertTrue($revision->isSuccess(), $revision->message());

        $newWorkItemId = (string) DB::table('work_items')
            ->where('note_id', $noteId)
            ->where('id', '<>', $oldWorkItemId)
            ->value('id');
        $newStoreStockLineId = (string) DB::table('work_item_store_stock_lines')
            ->where('work_item_id', $newWorkItemId)
            ->where('id', '<>', $oldStoreStockLineId)
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
            ->handleCurrent('2026-05-01', '2026-05-31');
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
        self::assertSame(-120000, $movementRow['net_cost_delta']);
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

    public function test_store_stock_transaction_keeps_historical_line_price_after_master_product_price_change(): void
    {
        $this->seedStoreStockProduct();

        \Illuminate\Support\Carbon::setTestNow('2026-05-20 09:00:00');
        $this->app->instance(
            \App\Ports\Out\ClockPort::class,
            new \Tests\Support\FixedHistoricalWorkspaceClock('2026-05-20 09:00:00'),
        );

        try {
            $create = app(CreateTransactionWorkspaceHandler::class)->handle($this->createPaidStoreStockPayload());
        } finally {
            \Illuminate\Support\Carbon::setTestNow();
            $this->app->forgetInstance(\App\Ports\Out\ClockPort::class);
        }

        self::assertTrue($create->isSuccess(), $create->message());

        $noteId = (string) ($create->data()['note']['id'] ?? '');
        self::assertNotSame('', $noteId);

        $oldWorkItemId = (string) DB::table('work_items')->where('note_id', $noteId)->value('id');
        $oldStoreStockLineId = (string) DB::table('work_item_store_stock_lines')
            ->where('work_item_id', $oldWorkItemId)
            ->value('id');

        self::assertNotSame('', $oldWorkItemId);
        self::assertNotSame('', $oldStoreStockLineId);

        DB::table('products')
            ->where('id', 'product-0062-a')
            ->update(['harga_jual' => 999999]);

        $this->assertDatabaseHas('notes', [
            'id' => $noteId,
            'total_rupiah' => 250000,
        ]);
        $this->assertDatabaseHas('work_item_store_stock_lines', [
            'id' => $oldStoreStockLineId,
            'product_id' => 'product-0062-a',
            'qty' => 2,
            'line_total_rupiah' => 200000,
        ]);
        $this->assertDatabaseMissing('work_item_store_stock_lines', [
            'id' => $oldStoreStockLineId,
            'line_total_rupiah' => 1999998,
        ]);

        $beforeRevisionTransaction = app(GetTransactionReportDatasetHandler::class)
            ->handle('2026-05-01', '2026-05-31');
        $beforeRevisionProfit = app(GetOperationalProfitSummaryHandler::class)
            ->handle('2026-05-01', '2026-05-31');

        self::assertTrue($beforeRevisionTransaction->isSuccess());
        self::assertTrue($beforeRevisionProfit->isSuccess());
        self::assertSame(250000, $beforeRevisionTransaction->data()['summary']['gross_transaction_rupiah']);
        self::assertSame(250000, $beforeRevisionTransaction->data()['summary']['allocated_payment_rupiah']);
        self::assertSame(80000, $beforeRevisionProfit->data()['row']['store_stock_cogs_rupiah']);

        $revision = app(CreateNoteRevisionHandler::class)->handle(
            $noteId,
            array_replace($this->masterPriceSnapshotRevisionPayload(), ['base_revision_id' => $this->revisionBaseForTest($noteId)]),
            'admin-0062-e',
            false,
        );

        self::assertFalse($revision->isSuccess());
        self::assertSame(
            'Harga jual pada store stock line tidak boleh di bawah harga jual minimum.',
            $revision->message(),
        );

        $this->assertDatabaseHas('notes', [
            'id' => $noteId,
            'total_rupiah' => 250000,
        ]);
        $this->assertDatabaseHas('work_item_store_stock_lines', [
            'id' => $oldStoreStockLineId,
            'product_id' => 'product-0062-a',
            'qty' => 2,
            'line_total_rupiah' => 200000,
        ]);
        self::assertSame(1, DB::table('note_revisions')->where('note_root_id', $noteId)->count());
        self::assertSame(1, DB::table('inventory_movements')
            ->where('product_id', 'product-0062-a')
            ->where('movement_type', 'stock_out')
            ->where('source_type', 'work_item_store_stock_line')
            ->count());
        self::assertSame(0, DB::table('inventory_movements')
            ->where('source_type', 'transaction_workspace_updated')
            ->count());

        $transaction = app(GetTransactionReportDatasetHandler::class)
            ->handle('2026-05-01', '2026-05-31');
        $cashLedger = app(TransactionCashLedgerReportingQuery::class)
            ->reconciliation('2026-05-01', '2026-05-31');
        $profit = app(GetOperationalProfitSummaryHandler::class)
            ->handle('2026-05-01', '2026-05-31');

        self::assertTrue($transaction->isSuccess());
        self::assertTrue($profit->isSuccess());

        self::assertSame(250000, $transaction->data()['summary']['gross_transaction_rupiah']);
        self::assertSame(250000, $transaction->data()['summary']['allocated_payment_rupiah']);
        self::assertSame(0, $transaction->data()['summary']['outstanding_rupiah']);
        self::assertSame(250000, $cashLedger['total_in_rupiah']);
        self::assertSame(250000, $profit->data()['row']['cash_in_rupiah']);
        self::assertSame(80000, $profit->data()['row']['store_stock_cogs_rupiah']);
        self::assertSame(170000, $profit->data()['row']['cash_operational_profit_rupiah']);
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
    private function masterPriceSnapshotRevisionPayload(): array
    {
        return [
            'reason' => '0062-E master product price changed after transaction.',
            'note' => [
                'customer_name' => 'Budi 0062 E Revised',
                'customer_phone' => '08123456789',
                'transaction_date' => '2026-05-23',
            ],
            'items' => [[
                'entry_mode' => 'service',
                'description' => null,
                'part_source' => 'store_stock',
                'service' => [
                    'name' => 'Servis 0062 E Revised',
                    'price_rupiah' => 50000,
                    'notes' => null,
                ],
                'product_lines' => [[
                    'product_id' => 'product-0062-a',
                    'qty' => 1,
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
