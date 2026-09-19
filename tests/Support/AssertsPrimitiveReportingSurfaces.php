<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Application\Reporting\Exports\TransactionReportPdfViewDataBuilder;
use App\Application\Reporting\UseCases\GetServicePackageProfitBreakdownHandler;
use App\Application\Reporting\UseCases\GetInventoryStockValueReportDatasetHandler;
use App\Adapters\Out\Note\Queries\CashierNoteHistoryTableQuery;
use App\Ports\Out\Reporting\TransactionReportingSourceReaderPort;
use Illuminate\Support\Facades\View;
use Mockery;
use PhpOffice\PhpSpreadsheet\IOFactory;

trait AssertsPrimitiveReportingSurfaces
{
    private function assertPrimitiveRelatedReports(int $outstanding, int $cogs): void
    {
        $packages = app(GetServicePackageProfitBreakdownHandler::class)->handle('2026-09-15', '2026-09-15')->data();
        self::assertCount(1, $packages['rows']);
        foreach (['package_sold_amount_rupiah' => 99257, 'parts_total_rupiah' => 57274,
            'total_service_component_rupiah' => 41983, 'sparepart_cogs_rupiah' => 23006] as $key => $value) {
            self::assertSame($value, $packages['rows'][0][$key]);
        }
        $stock = app(GetInventoryStockValueReportDatasetHandler::class)->handle('2026-09-15', '2026-09-16')->data();
        self::assertSame(0, $stock['summary']['total_ledger_qty_diff']);
        self::assertSame(0, $stock['summary']['total_ledger_value_diff_rupiah']);
        self::assertSame(860297 - $cogs, $stock['summary']['total_inventory_value_rupiah']);
        $queue = app(CashierNoteHistoryTableQuery::class)->get(['bucket' => 'unfinished']);
        self::assertCount($outstanding > 0 ? 1 : 0, $queue['items']);
        if ($outstanding > 0) {
            self::assertSame('Rp '.number_format($outstanding, 0, ',', '.'), $queue['items'][0]['outstanding_text']);
        }
    }

    private function assertPrimitiveReportSurfaces(array $dataset): void
    {
        $actor = auth()->guard()->user();
        $this->loginAsAuthorizedAdmin();
        $filters = ['period_mode' => 'custom', 'date_from' => '2026-09-15', 'date_to' => '2026-09-15'];
        $source = app(TransactionReportingSourceReaderPort::class);
        $raw = $source->getTransactionSummaryPerNoteRows('2026-09-15', '2026-09-15');
        // Observe real reads, never replace the source with canned financial answers.
        $reader = Mockery::mock(TransactionReportingSourceReaderPort::class);
        $reader->shouldReceive('getTransactionSummaryPerNoteRows')->with('2026-09-15', '2026-09-15')->times(3)
            ->andReturnUsing(function (string $from, string $to) use ($source, $raw): array {
                $actual = $source->getTransactionSummaryPerNoteRows($from, $to);
                self::assertSame($raw, $actual);
                return $actual;
            });
        $this->app->instance(TransactionReportingSourceReaderPort::class, $reader);
        $pdfData = null;
        View::composer('admin.reporting.transaction_summary.export_pdf', function ($view) use (&$pdfData): void {
            $pdfData = $view->getData();
        });
        try {
            $page = $this->get(route('admin.reports.transaction_summary.index', $filters))->assertOk();
            $page->assertViewHas('summary', $dataset['summary']);
            $page->assertViewHas('periodRows', $dataset['period_rows']);
            self::assertSame($dataset['rows'], $page->viewData('rows')->items());
            self::assertSame($dataset['customer_rows'], $page->viewData('customerRows')->items());
            $pdf = $this->get(route('admin.reports.transaction_summary.export_pdf', $filters))->assertOk();
            self::assertStringStartsWith('%PDF', $pdf->getContent());
            $expectedPdf = app(TransactionReportPdfViewDataBuilder::class)->build($dataset, $filters);
            foreach ($expectedPdf as $key => $value) {
                self::assertSame($value, $pdfData[$key]);
            }
            $excel = $this->get(route('admin.reports.transaction_summary.export_excel', $filters))->assertOk();
            $path = tempnam(sys_get_temp_dir(), 'primitive-report-');
            try {
                file_put_contents($path, $excel->streamedContent());
                $book = IOFactory::load($path);
                $summary = $book->getSheetByName('Ringkasan');
                $detail = $book->getSheetByName('Rincian Nota');
                foreach (['gross_transaction_rupiah', 'allocated_payment_rupiah', 'refunded_rupiah', 'refund_due_rupiah',
                    'surplus_refund_paid_rupiah', 'remaining_refund_due_rupiah', 'net_cash_collected_rupiah', 'outstanding_rupiah'] as $index => $key) {
                    self::assertSame($dataset['summary'][$key], $summary->getCell('B'.($index + 7))->getValue());
                    self::assertSame($dataset['rows'][0][$key], $detail->getCell(chr(69 + $index).'2')->getValue());
                }
                $book->disconnectWorksheets();
            } finally {
                unlink($path);
            }
        } finally {
            $this->app->instance(TransactionReportingSourceReaderPort::class, $source);
            $this->actingAs($actor);
        }
    }
}
