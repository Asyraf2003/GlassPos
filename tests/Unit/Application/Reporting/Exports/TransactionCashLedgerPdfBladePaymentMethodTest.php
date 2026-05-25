<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Reporting\Exports;

use Tests\TestCase;

final class TransactionCashLedgerPdfBladePaymentMethodTest extends TestCase
{
    public function test_pdf_blade_renders_payment_method_column_for_cash_ledger_detail_rows(): void
    {
        $html = view('admin.reporting.transaction_cash_ledger.export_pdf', [
            'title' => 'Laporan Buku Kas Transaksi',
            'periodLabel' => '01/01/2030 s/d 31/01/2030',
            'generatedAt' => '31/01/2030 10:00',
            'summaryItems' => [
                ['label' => 'Total Kejadian', 'value' => 2],
                ['label' => 'Kas Masuk', 'value' => 'Rp 115.000'],
                ['label' => 'Tunai Masuk', 'value' => 'Rp 85.000'],
                ['label' => 'Transfer Masuk', 'value' => 'Rp 30.000'],
                ['label' => 'Kas Keluar', 'value' => 'Rp 0'],
                ['label' => 'Nilai Bersih', 'value' => 'Rp 115.000'],
            ],
            'rows' => [
                [
                    'date' => '31/01/2030',
                    'note_label' => 'INV-001',
                    'event_type' => 'Alokasi Pembayaran',
                    'direction' => 'Masuk',
                    'payment_method' => 'Tunai',
                    'amount' => 'Rp 85.000',
                    'payment_marker' => 'Ada',
                    'refund_marker' => '-',
                    'source_table' => 'payment_component_allocations',
                    'source_id' => 'allocation-cash-001',
                    'source_disposition_id' => '-',
                ],
                [
                    'date' => '31/01/2030',
                    'note_label' => 'INV-002',
                    'event_type' => 'Alokasi Pembayaran',
                    'direction' => 'Masuk',
                    'payment_method' => 'Transfer',
                    'amount' => 'Rp 30.000',
                    'payment_marker' => 'Ada',
                    'refund_marker' => '-',
                    'source_table' => 'payment_component_allocations',
                    'source_id' => 'allocation-transfer-001',
                    'source_disposition_id' => '-',
                ],
            ],
        ])->render();

        $this->assertStringContainsString('Metode Pembayaran', $html);
        $this->assertStringContainsString('Tunai', $html);
        $this->assertStringContainsString('Transfer', $html);
    }
}
