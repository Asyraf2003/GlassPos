<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Adapters\Out\Reporting\Queries\TransactionCashLedgerReportingQuery;
use App\Application\Reporting\UseCases\GetOperationalProfitSummaryHandler;
use App\Application\Reporting\UseCases\GetTransactionReportDatasetHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Support\BuildsPrimitiveLifecycleFixture;
use Tests\TestCase;

final class PrimitiveLifecycleReportingChainFeatureTest extends TestCase
{
    use BuildsPrimitiveLifecycleFixture;
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_chain_e_reports_final_domain_without_mutation_and_keeps_event_date_distinct(): void
    {
        $this->preparePrimitiveFixture();
        $cashier = $this->loginAsKasir();
        $create = $this->primitiveWorkspace($this->primitiveItems(), 'report-e-create');
        $create['inline_payment'] = ['decision' => 'pay_partial', 'payment_method' => 'cash', 'paid_at' => '2026-09-15',
            'amount_paid_rupiah' => 73129, 'amount_received_rupiah' => 100003];
        $this->post(route('notes.workspace.store'), $create)->assertSessionHasNoErrors();
        $id = (string) DB::table('notes')->value('id');
        $payment = route('cashier.notes.payments.store', ['noteId' => $id]);
        $this->checkpoint([395933, 73129, 0, 0, 73129, 322804, 82169, -62167]);
        $this->advancePrimitiveTime();
        $this->post($payment, $this->primitivePayment('report-e-dp2', 89457, 'transfer'))->assertSessionHasNoErrors();
        $this->checkpoint([395933, 162586, 0, 0, 162586, 233347, 82169, 27290]);
        $this->advancePrimitiveTime();
        $this->post($payment, $this->primitivePayment('report-e-full', 233347, 'cash', 250009))->assertSessionHasNoErrors();
        $this->checkpoint([395933, 395933, 0, 0, 395933, 0, 82169, 260637]);
        $oldProduct = (string) DB::table('work_items')->where('transaction_type', 'store_stock_sale_only')->value('id');
        $this->advancePrimitiveTime();
        $refund = ['selected_row_ids' => [$oldProduct], 'refunded_at' => '2026-09-15', 'reason' => 'Chain E source refund', 'idempotency_key' => 'report-e-refund'];
        $this->post(route('cashier.notes.refunds.store', ['noteId' => $id]), $refund)->assertSessionHasNoErrors();
        $this->checkpoint([253394, 395933, 142539, 0, 253394, 0, 23006, 177261]);
        $this->assertDatabaseHas('work_items', ['id' => $oldProduct, 'status' => 'canceled']);
        $admin = $this->loginAsAuthorizedAdmin();
        $this->advancePrimitiveTime();
        $down = $this->primitiveWorkspace(array_slice($this->primitiveItems(51983), 1), 'report-e-down');
        $down['base_revision_id'] = $this->revisionBaseForTest($id);
        $this->patch(route('admin.notes.workspace.update', ['noteId' => $id]), $down)->assertSessionHasNoErrors();
        $this->checkpoint([241658, 395933, 142539, 11736, 241658, 0, 23006, 165525]);
        $this->advancePrimitiveTime();
        $items = $this->primitiveItems(70211);
        $up = $this->primitiveWorkspace([$items[3], $items[2], $items[1], ['entry_mode' => 'product',
            'product_lines' => [['product_id' => 'primitive-r', 'qty' => 5, 'unit_price_rupiah' => 33571]]]], 'report-e-up');
        $up['base_revision_id'] = $this->revisionBaseForTest($id);
        $this->patch(route('admin.notes.workspace.update', ['noteId' => $id]), $up)->assertSessionHasNoErrors();
        $this->checkpoint([427741, 395933, 142539, 11736, 241658, 186083, 91551, 96980]);
        $this->actingAs($cashier);
        $this->advancePrimitiveTime();
        $this->post($payment, $this->primitivePayment('report-e-new-dp', 27119, 'transfer'))->assertSessionHasNoErrors();
        $this->checkpoint([427741, 423052, 142539, 11736, 268777, 158964, 91551, 124099]);
        // Integrate the one prescribed period probe at B8; the combined event window still owns E8 totals.
        Carbon::setTestNow('2026-09-16 10:11:12');
        $final = $this->primitivePayment('report-e-final', 158964, 'cash', 170003);
        $final['paid_at'] = '2026-09-16';
        $this->post($payment, $final)->assertSessionHasNoErrors();
        $this->checkpoint([427741, 582016, 142539, 11736, 427741, 0, 91551, 283063]);
        $ledger = app(TransactionCashLedgerReportingQuery::class);
        self::assertSame(['total_in_rupiah' => 582016, 'cash_in_rupiah' => 465440, 'transfer_in_rupiah' => 116576, 'total_out_rupiah' => 154275], $ledger->reconciliation('2026-09-15', '2026-09-16'));
        $events = collect($ledger->rows('2026-09-15', '2026-09-16'));
        self::assertCount(5, $events->where('direction', 'in'));
        self::assertCount(4, $events->where('direction', 'out'));
        self::assertSame(520015, $events->sum('cash_amount_received_rupiah'));
        self::assertSame(54575, $events->sum('cash_change_rupiah'));
        self::assertSame(423052, $ledger->reconciliation('2026-09-15', '2026-09-15')['total_in_rupiah']);
        self::assertSame(158964, $ledger->reconciliation('2026-09-16', '2026-09-16')['total_in_rupiah']);
        $current = app(GetTransactionReportDatasetHandler::class)->handle('2026-09-15', '2026-09-15')->data();
        self::assertSame(0, $current['summary']['outstanding_rupiah']);
        self::assertSame(427741, $current['summary']['gross_transaction_rupiah']);
    }

    private function checkpoint(array $expected): void
    {
        $before = $this->evidence();
        $first = null;
        for ($read = 0; $read < 2; $read++) {
            $report = app(GetTransactionReportDatasetHandler::class)->handle('2026-09-15', '2026-09-15');
            $profit = app(GetOperationalProfitSummaryHandler::class)->handle('2026-09-15', '2026-09-16');
            self::assertTrue($report->isSuccess(), (string) $report->message());
            self::assertTrue($profit->isSuccess(), (string) $profit->message());
            $summary = $report->data()['summary'];
            $costs = $profit->data()['row'];
            self::assertSame($expected, [$summary['gross_transaction_rupiah'], $costs['cash_in_rupiah'], $summary['refunded_rupiah'],
                $summary['surplus_refund_paid_rupiah'], $summary['net_cash_collected_rupiah'], $summary['outstanding_rupiah'],
                $costs['store_stock_cogs_rupiah'], $costs['cash_operational_profit_rupiah']]);
            self::assertSame(53127, $costs['external_purchase_cost_rupiah']);
            self::assertSame(0, $summary['remaining_refund_due_rupiah']);
            $all = [$report->data(), $profit->data(), app(TransactionCashLedgerReportingQuery::class)->rows('2026-09-15', '2026-09-16')];
            if ($first !== null) self::assertSame($first, $all);
            $first = $all;
        }
        self::assertSame($before, $this->evidence(), 'Reporting must be read-only across raw domain effects');
    }

    private function evidence(): array
    {
        $rows = [];
        foreach (['notes', 'work_items', 'note_revisions', 'note_revision_lines', 'customer_payments', 'payment_allocations',
            'payment_component_allocations', 'customer_refunds', 'refund_component_allocations', 'note_revision_surplus_dispositions',
            'note_revision_surplus_refund_payments', 'inventory_movements', 'note_mutation_events', 'audit_events', 'audit_outbox', 'audit_logs'] as $table) {
            $rows[$table] = $this->primitiveRows($table);
        }
        return $rows;
    }
}
