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
        $customer = $spreadsheet->getSheetByName('Rekap Per Customer');

        $this->assertNotNull($summary);
        $this->assertNotNull($detail);
        $this->assertNotNull($period);
        $this->assertNotNull($customer);

        $this->assertSame('Total Refund Due', $summary->getCell('A10')->getValue());
        $this->assertSame(7000, $summary->getCell('B10')->getValue());

        $this->assertSame('Refund Due', $detail->getCell('H1')->getValue());
        $this->assertSame(7000, $detail->getCell('H2')->getValue());

        $this->assertSame('Refund Due', $period->getCell('F1')->getValue());
        $this->assertSame(7000, $period->getCell('F2')->getValue());

        $this->assertSame('Refund Due', $customer->getCell('F1')->getValue());
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
            ['label' => 'Refund Due', 'value' => 'Rp 7.000'],
            $viewData['summaryItems'],
        );

        $this->assertSame('Rp 100.000', $viewData['rows'][0]['total']);
        $this->assertSame('Rp 99.999', $viewData['rows'][0]['paid']);
        $this->assertSame('Rp 9.000', $viewData['rows'][0]['refund']);
        $this->assertSame('Rp 7.000', $viewData['rows'][0]['refund_due']);
        $this->assertSame('Rp 90.999', $viewData['rows'][0]['net_paid']);
        $this->assertSame('Rp 9.001', $viewData['rows'][0]['outstanding']);
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
                'customer_name' => 'Customer Export Refund Due',
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
                'customer_name' => 'Customer Export Refund Due',
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
}
