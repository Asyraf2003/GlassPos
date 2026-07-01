<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Reporting\Exports;

use App\Application\Reporting\Exports\TransactionCashLedgerExcelDetailSheetWriter;
use App\Application\Reporting\Exports\TransactionReportExcelTableWriter;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Tests\TestCase;

final class TransactionCashLedgerExcelDetailPaymentMethodTest extends TestCase
{
    public function test_excel_detail_sheet_exposes_payment_method_for_money_in_rows(): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $writer = new TransactionCashLedgerExcelDetailSheetWriter(
            new TransactionReportExcelTableWriter(),
        );

        $writer->write($sheet, [
            [
                'event_date' => '2030-01-31',
                'note_id' => 'note-cash-ledger-export-001',
                'note_label' => 'INV-001',
                'event_type' => 'payment_allocation',
                'direction' => 'in',
                'payment_method' => 'cash',
                'event_amount_rupiah' => 85000,
                'cash_amount_paid_rupiah' => 85000,
                'cash_amount_received_rupiah' => 100000,
                'cash_change_rupiah' => 15000,
                'customer_payment_id' => 'payment-cash-001',
                'refund_id' => '',
                'source_table' => 'payment_component_allocations',
                'source_id' => 'allocation-cash-001',
                'source_disposition_id' => '',
            ],
            [
                'event_date' => '2030-01-31',
                'note_id' => 'note-cash-ledger-export-002',
                'note_label' => 'INV-002',
                'event_type' => 'payment_allocation',
                'direction' => 'in',
                'payment_method' => 'transfer',
                'event_amount_rupiah' => 30000,
                'cash_amount_paid_rupiah' => null,
                'cash_amount_received_rupiah' => null,
                'cash_change_rupiah' => null,
                'customer_payment_id' => 'payment-transfer-001',
                'refund_id' => '',
                'source_table' => 'payment_component_allocations',
                'source_id' => 'allocation-transfer-001',
                'source_disposition_id' => '',
            ],
        ]);

        $this->assertSame('Metode Pembayaran', $sheet->getCell('G1')->getValue());
        $this->assertSame('Tunai', $sheet->getCell('G2')->getValue());
        $this->assertSame('Transfer', $sheet->getCell('G3')->getValue());

        $this->assertSame('Nominal', $sheet->getCell('H1')->getValue());
        $this->assertSame(85000, $sheet->getCell('H2')->getValue());
        $this->assertSame(30000, $sheet->getCell('H3')->getValue());
        $this->assertSame('Tunai Dibayar', $sheet->getCell('I1')->getValue());
        $this->assertSame(85000, $sheet->getCell('I2')->getValue());
        $this->assertNull($sheet->getCell('I3')->getValue());
        $this->assertSame('Uang Pelanggan', $sheet->getCell('J1')->getValue());
        $this->assertSame(100000, $sheet->getCell('J2')->getValue());
        $this->assertNull($sheet->getCell('J3')->getValue());
        $this->assertSame('Kembalian Tunai', $sheet->getCell('K1')->getValue());
        $this->assertSame(15000, $sheet->getCell('K2')->getValue());
        $this->assertNull($sheet->getCell('K3')->getValue());

        $spreadsheet->disconnectWorksheets();
    }
}
