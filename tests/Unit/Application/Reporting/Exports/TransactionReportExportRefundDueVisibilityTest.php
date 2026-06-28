<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Reporting\Exports;

use App\Application\Reporting\Exports\TransactionReportExcelCustomerSheetWriter;
use App\Application\Reporting\Exports\TransactionReportExcelDetailSheetWriter;
use App\Application\Reporting\Exports\TransactionReportExcelPeriodSheetWriter;
use App\Application\Reporting\Exports\TransactionReportExcelSummarySheetWriter;
use App\Application\Reporting\Exports\TransactionReportExcelTableWriter;
use App\Application\Reporting\Exports\TransactionReportExcelWorkbookBuilder;
use App\Application\Reporting\Exports\TransactionReportPdfViewDataBuilder;
use App\Ports\Out\ClockPort;
use DateTimeImmutable;
use Tests\TestCase;

final class TransactionReportExportRefundDueVisibilityTest extends TestCase
{
    public function test_excel_export_includes_refund_due_across_transaction_report_sheets(): void
    {
        $tableWriter = new TransactionReportExcelTableWriter();

        $builder = new TransactionReportExcelWorkbookBuilder(
            new TransactionReportExcelSummarySheetWriter($tableWriter),
            new TransactionReportExcelDetailSheetWriter($tableWriter),
            new TransactionReportExcelPeriodSheetWriter($tableWriter),
            new TransactionReportExcelCustomerSheetWriter($tableWriter),
        );

        $spreadsheet = $builder->build($this->dataset(), [
            'date_from' => '2030-01-01',
            'date_to' => '2030-01-31',
        ]);

        $summary = $spreadsheet->getSheetByName('Ringkasan');
        $detail = $spreadsheet->getSheetByName('Rincian Nota');
        $period = $spreadsheet->getSheetByName('Rekap Per Tanggal');
        $customer = $spreadsheet->getSheetByName('Rekap Per Pelanggan');

        $this->assertNotNull($summary);
        $this->assertNotNull($detail);
        $this->assertNotNull($period);
        $this->assertNotNull($customer);

        $this->assertSame('Total Pengembalian Belum Dibayar', $summary->getCell('A10')->getValue());
        $this->assertSame(7000, $summary->getCell('B10')->getValue());

        $this->assertSame('Pengembalian Belum Dibayar', $detail->getCell('H1')->getValue());
        $this->assertSame(7000, $detail->getCell('H2')->getValue());

        $this->assertSame('Pengembalian Belum Dibayar', $period->getCell('F1')->getValue());
        $this->assertSame(7000, $period->getCell('F2')->getValue());

        $this->assertSame('Pengembalian Belum Dibayar', $customer->getCell('F1')->getValue());
        $this->assertSame(7000, $customer->getCell('F2')->getValue());

        $spreadsheet->disconnectWorksheets();
    }

    public function test_pdf_export_view_data_includes_refund_due_from_current_dataset_keys(): void
    {
        $builder = new TransactionReportPdfViewDataBuilder(new class implements ClockPort {
            public function now(): DateTimeImmutable
            {
                return new DateTimeImmutable('2030-01-31 10:00:00');
            }
        });

        $viewData = $builder->build($this->dataset(), [
            'date_from' => '2030-01-01',
            'date_to' => '2030-01-31',
        ]);

        $this->assertContains(
            ['label' => 'Pengembalian Belum Dibayar', 'value' => 'Rp 7.000'],
            $viewData['summaryItems'],
        );

        $this->assertSame('Rp 100.000', $viewData['rows'][0]['total']);
        $this->assertSame('Rp 99.999', $viewData['rows'][0]['paid']);
        $this->assertSame('Rp 9.000', $viewData['rows'][0]['refund']);
        $this->assertSame('Rp 7.000', $viewData['rows'][0]['refund_due']);
        $this->assertSame('Rp 90.999', $viewData['rows'][0]['net_paid']);
        $this->assertSame('Rp 9.001', $viewData['rows'][0]['outstanding']);
    }


    public function test_exports_include_surplus_refund_paid_and_remaining_refund_due_from_dataset(): void
    {
        $tableWriter = new TransactionReportExcelTableWriter();

        $workbookBuilder = new TransactionReportExcelWorkbookBuilder(
            new TransactionReportExcelSummarySheetWriter($tableWriter),
            new TransactionReportExcelDetailSheetWriter($tableWriter),
            new TransactionReportExcelPeriodSheetWriter($tableWriter),
            new TransactionReportExcelCustomerSheetWriter($tableWriter),
        );

        $spreadsheet = $workbookBuilder->build($this->datasetWithSurplusRefundPaid(), [
            'date_from' => '2030-01-01',
            'date_to' => '2030-01-31',
        ]);

        $summary = $spreadsheet->getSheetByName('Ringkasan');
        $detail = $spreadsheet->getSheetByName('Rincian Nota');
        $period = $spreadsheet->getSheetByName('Rekap Per Tanggal');
        $customer = $spreadsheet->getSheetByName('Rekap Per Pelanggan');

        $this->assertNotNull($summary);
        $this->assertNotNull($detail);
        $this->assertNotNull($period);
        $this->assertNotNull($customer);

        $this->assertSame('Total Pengembalian Surplus Sudah Dibayar', $summary->getCell('A11')->getValue());
        $this->assertSame(3000, $summary->getCell('B11')->getValue());
        $this->assertSame('Total Sisa Pengembalian Belum Dibayar', $summary->getCell('A12')->getValue());
        $this->assertSame(4000, $summary->getCell('B12')->getValue());

        $this->assertSame('Pengembalian Surplus Sudah Dibayar', $detail->getCell('I1')->getValue());
        $this->assertSame(3000, $detail->getCell('I2')->getValue());
        $this->assertSame('Sisa Pengembalian Belum Dibayar', $detail->getCell('J1')->getValue());
        $this->assertSame(4000, $detail->getCell('J2')->getValue());

        $this->assertSame('Pengembalian Surplus Sudah Dibayar', $period->getCell('G1')->getValue());
        $this->assertSame(3000, $period->getCell('G2')->getValue());
        $this->assertSame('Sisa Pengembalian Belum Dibayar', $period->getCell('H1')->getValue());
        $this->assertSame(4000, $period->getCell('H2')->getValue());

        $this->assertSame('Pengembalian Surplus Sudah Dibayar', $customer->getCell('G1')->getValue());
        $this->assertSame(3000, $customer->getCell('G2')->getValue());
        $this->assertSame('Sisa Pengembalian Belum Dibayar', $customer->getCell('H1')->getValue());
        $this->assertSame(4000, $customer->getCell('H2')->getValue());

        $spreadsheet->disconnectWorksheets();

        $pdfBuilder = new TransactionReportPdfViewDataBuilder(new class implements ClockPort {
            public function now(): DateTimeImmutable
            {
                return new DateTimeImmutable('2030-01-31 10:00:00');
            }
        });

        $viewData = $pdfBuilder->build($this->datasetWithSurplusRefundPaid(), [
            'date_from' => '2030-01-01',
            'date_to' => '2030-01-31',
        ]);

        $this->assertContains(
            ['label' => 'Pengembalian Surplus Sudah Dibayar', 'value' => 'Rp 3.000'],
            $viewData['summaryItems'],
        );
        $this->assertContains(
            ['label' => 'Sisa Pengembalian Belum Dibayar', 'value' => 'Rp 4.000'],
            $viewData['summaryItems'],
        );

        $this->assertSame('Rp 3.000', $viewData['rows'][0]['surplus_refund_paid']);
        $this->assertSame('Rp 4.000', $viewData['rows'][0]['remaining_refund_due']);
    }


    public function test_pdf_export_blade_renders_surplus_refund_paid_and_remaining_refund_due(): void
    {
        $builder = new TransactionReportPdfViewDataBuilder(new class implements ClockPort {
            public function now(): DateTimeImmutable
            {
                return new DateTimeImmutable('2030-01-31 10:00:00');
            }
        });

        $viewData = $builder->build($this->datasetWithSurplusRefundPaid(), [
            'date_from' => '2030-01-01',
            'date_to' => '2030-01-31',
        ]);

        $html = view('admin.reporting.transaction_summary.export_pdf', $viewData)->render();

        $this->assertStringContainsString('Pengembalian Surplus Sudah Dibayar', $html);
        $this->assertStringContainsString('Sisa Pengembalian Belum Dibayar', $html);
        $this->assertStringContainsString('Rp 3.000', $html);
        $this->assertStringContainsString('Rp 4.000', $html);
    }

    private function dataset(): array
    {
        return [
            'summary' => [
                'total_rows' => 1,
                'gross_transaction_rupiah' => 100000,
                'allocated_payment_rupiah' => 99999,
                'refunded_rupiah' => 9000,
                'refund_due_rupiah' => 7000,
                'net_cash_collected_rupiah' => 90999,
                'outstanding_rupiah' => 9001,
                'settled_rows' => 0,
                'outstanding_rows' => 1,
            ],
            'rows' => [[
                'note_id' => 'note-export-refund-due-001',
                'transaction_date' => '2030-01-07',
                'customer_name' => 'Customer Export Pengembalian Belum Dibayar',
                'gross_transaction_rupiah' => 100000,
                'allocated_payment_rupiah' => 99999,
                'refunded_rupiah' => 9000,
                'refund_due_rupiah' => 7000,
                'net_cash_collected_rupiah' => 90999,
                'outstanding_rupiah' => 9001,
                'payment_status_label' => 'Belum Lunas',
            ]],
            'period_rows' => [[
                'period_label' => '2030-01-07',
                'total_rows' => 1,
                'gross_transaction_rupiah' => 100000,
                'allocated_payment_rupiah' => 99999,
                'refunded_rupiah' => 9000,
                'refund_due_rupiah' => 7000,
                'net_cash_collected_rupiah' => 90999,
                'outstanding_rupiah' => 9001,
            ]],
            'customer_rows' => [[
                'customer_name' => 'Customer Export Pengembalian Belum Dibayar',
                'total_rows' => 1,
                'gross_transaction_rupiah' => 100000,
                'allocated_payment_rupiah' => 99999,
                'refunded_rupiah' => 9000,
                'refund_due_rupiah' => 7000,
                'net_cash_collected_rupiah' => 90999,
                'outstanding_rupiah' => 9001,
            ]],
        ];
    }
    private function datasetWithSurplusRefundPaid(): array
    {
        return [
            'summary' => [
                'total_rows' => 1,
                'gross_transaction_rupiah' => 100000,
                'allocated_payment_rupiah' => 99999,
                'refunded_rupiah' => 9000,
                'refund_due_rupiah' => 7000,
                'surplus_refund_paid_rupiah' => 3000,
                'remaining_refund_due_rupiah' => 4000,
                'net_cash_collected_rupiah' => 90999,
                'outstanding_rupiah' => 9001,
                'settled_rows' => 0,
                'outstanding_rows' => 1,
            ],
            'rows' => [[
                'note_id' => 'note-export-surplus-refund-paid-001',
                'transaction_date' => '2030-01-07',
                'customer_name' => 'Customer Export Pengembalian Surplus Sudah Dibayar',
                'gross_transaction_rupiah' => 100000,
                'allocated_payment_rupiah' => 99999,
                'refunded_rupiah' => 9000,
                'refund_due_rupiah' => 7000,
                'surplus_refund_paid_rupiah' => 3000,
                'remaining_refund_due_rupiah' => 4000,
                'net_cash_collected_rupiah' => 90999,
                'outstanding_rupiah' => 9001,
                'payment_status_label' => 'Belum Lunas',
            ]],
            'period_rows' => [[
                'period_label' => '2030-01-07',
                'total_rows' => 1,
                'gross_transaction_rupiah' => 100000,
                'allocated_payment_rupiah' => 99999,
                'refunded_rupiah' => 9000,
                'refund_due_rupiah' => 7000,
                'surplus_refund_paid_rupiah' => 3000,
                'remaining_refund_due_rupiah' => 4000,
                'net_cash_collected_rupiah' => 90999,
                'outstanding_rupiah' => 9001,
            ]],
            'customer_rows' => [[
                'customer_name' => 'Customer Export Pengembalian Surplus Sudah Dibayar',
                'total_rows' => 1,
                'gross_transaction_rupiah' => 100000,
                'allocated_payment_rupiah' => 99999,
                'refunded_rupiah' => 9000,
                'refund_due_rupiah' => 7000,
                'surplus_refund_paid_rupiah' => 3000,
                'remaining_refund_due_rupiah' => 4000,
                'net_cash_collected_rupiah' => 90999,
                'outstanding_rupiah' => 9001,
            ]],
        ];
    }


}
