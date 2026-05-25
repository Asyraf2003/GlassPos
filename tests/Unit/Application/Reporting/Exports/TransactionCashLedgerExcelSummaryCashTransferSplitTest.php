<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Reporting\Exports;

use App\Application\Reporting\Exports\TransactionCashLedgerExcelSummarySheetWriter;
use App\Application\Reporting\Exports\TransactionReportExcelTableWriter;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

final class TransactionCashLedgerExcelSummaryCashTransferSplitTest extends TestCase
{
    public function test_excel_summary_sheet_exposes_cash_and_transfer_money_in_split(): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $writer = new TransactionCashLedgerExcelSummarySheetWriter(
            new TransactionReportExcelTableWriter(),
        );

        $writer->write($sheet, [
            'total_events' => 3,
            'total_cash_in_rupiah' => 115000,
            'cash_in_rupiah' => 85000,
            'transfer_in_rupiah' => 30000,
            'total_cash_out_rupiah' => 10000,
            'net_amount_rupiah' => 105000,
        ], [
            'date_from' => '2030-01-01',
            'date_to' => '2030-01-31',
        ]);

        $this->assertSame('Kas Masuk', $sheet->getCell('A7')->getValue());
        $this->assertSame(115000, $sheet->getCell('B7')->getValue());

        $this->assertSame('Tunai Masuk', $sheet->getCell('A8')->getValue());
        $this->assertSame(85000, $sheet->getCell('B8')->getValue());

        $this->assertSame('Transfer Masuk', $sheet->getCell('A9')->getValue());
        $this->assertSame(30000, $sheet->getCell('B9')->getValue());

        $spreadsheet->disconnectWorksheets();
    }
}
