<?php

declare(strict_types=1);

namespace Tests\Feature\ReportingExports;

use App\Application\Reporting\DTO\SupplierPayableReportPageQuery;
use App\Application\Reporting\Exports\SupplierPayableReportPdfViewDataBuilder;
use App\Application\Reporting\UseCases\GetSupplierPayableReportDatasetHandler;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\Support\Procurement\CurrentSupplierPayableFixture;
use Tests\TestCase;

final class SupplierPayableAllPeriodExportFeatureTest extends TestCase
{
    use RefreshDatabase;
    use CurrentSupplierPayableFixture;

    public function test_html_pdf_and_excel_share_all_period_and_explicit_cohort_datasets(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-27'));
        $this->loginAsAuthorizedAdmin();
        $id = $this->oldInvoice();
        $this->seedSupplierPayment('partial', $id, 2000000);
        $pdfData = [];
        View::composer('admin.reporting.supplier_payable.export_pdf', function ($view) use (&$pdfData): void {
            $pdfData = $view->getData();
        });

        foreach ([[], ['period_mode' => 'monthly', 'reference_date' => '2026-09-27'],
            ['period_mode' => 'monthly', 'reference_date' => '2026-08-27']] as $params) {
            $query = SupplierPayableReportPageQuery::fromValidated($params);
            $dataset = app(GetSupplierPayableReportDatasetHandler::class)
                ->handle($query->fromShipmentDate(), $query->toShipmentDate(), $query->referenceDate())->data();
            $expected = $params === [] || $params['reference_date'] === '2026-08-27' ? 8000000 : 0;
            self::assertSame($expected, $dataset['summary']['outstanding_rupiah']);
            $this->get(route('admin.reports.supplier_payable.index', $params))->assertOk()
                ->assertViewHas('summary', $dataset['summary']);
            $pdf = $this->get(route('admin.reports.supplier_payable.export_pdf', $params));
            $pdf->assertOk()->assertHeader('Content-Type', 'application/pdf');
            self::assertStringStartsWith('%PDF', $pdf->getContent());
            $expectedPdf = app(SupplierPayableReportPdfViewDataBuilder::class)->build($dataset, $query->toViewData());
            self::assertSame($expectedPdf['summaryItems'], $pdfData['summaryItems']);
            self::assertSame($expectedPdf['rows'], $pdfData['rows']);
            $xlsx = $this->get(route('admin.reports.supplier_payable.export_excel', $params));
            $xlsx->assertOk();
            $file = tempnam(sys_get_temp_dir(), 'payable-all-');
            file_put_contents($file, $xlsx->streamedContent());
            try {
                $book = IOFactory::load($file);
                self::assertSame($expected, $book->getSheetByName('Ringkasan')->getCell('B10')->getValue());
                self::assertSame($expected > 0 ? 'NF-august' : null, $book->getSheetByName('Detail Hutang Pemasok')->getCell('B2')->getValue());
                if ($params === []) {
                    $pdf->assertDownload('laporan-hutang-pemasok-seluruh-periode.pdf');
                    $xlsx->assertDownload('laporan-hutang-pemasok-seluruh-periode.xlsx');
                    self::assertSame('Seluruh Periode', $book->getSheetByName('Ringkasan')->getCell('B2')->getValue());
                    self::assertSame('Seluruh Periode', $pdfData['periodLabel']);
                }
                $book->disconnectWorksheets();
            } finally {
                unlink($file);
            }
        }
    }

    public function test_all_ignores_supplied_date_bounds_and_explicit_modes_keep_their_ranges(): void
    {
        $query = SupplierPayableReportPageQuery::fromValidated([
            'period_mode' => 'all', 'date_from' => '2026-09-01', 'date_to' => '2026-09-30',
        ]);
        self::assertNull($query->fromShipmentDate());
        self::assertNull($query->toShipmentDate());
        foreach (['daily' => ['2026-09-27', '2026-09-27'], 'weekly' => ['2026-09-21', '2026-09-27'],
            'monthly' => ['2026-09-01', '2026-09-30'], 'custom' => ['2026-08-01', '2026-09-20']] as $mode => $dates) {
            $query = SupplierPayableReportPageQuery::fromValidated([
                'period_mode' => $mode, 'reference_date' => '2026-09-27',
                'date_from' => '2026-08-01', 'date_to' => '2026-09-20',
            ]);
            self::assertSame($dates, [$query->fromShipmentDate(), $query->toShipmentDate()]);
        }
    }
}
