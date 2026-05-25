<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Reporting\Exports;

use App\Application\Reporting\Exports\TransactionCashLedgerPdfViewDataBuilder;
use App\Ports\Out\ClockPort;
use DateTimeImmutable;
use Tests\TestCase;

final class TransactionCashLedgerPdfExportCashTransferSplitTest extends TestCase
{
    public function test_pdf_view_data_summary_exposes_cash_and_transfer_money_in_split(): void
    {
        $builder = new TransactionCashLedgerPdfViewDataBuilder(new class implements ClockPort {
            public function now(): DateTimeImmutable
            {
                return new DateTimeImmutable('2030-01-31 10:00:00');
            }
        });

        $viewData = $builder->build([
            'summary' => [
                'total_events' => 3,
                'total_cash_in_rupiah' => 115000,
                'cash_in_rupiah' => 85000,
                'transfer_in_rupiah' => 30000,
                'total_cash_out_rupiah' => 10000,
                'net_amount_rupiah' => 105000,
            ],
            'rows' => [],
        ], [
            'date_from' => '2030-01-01',
            'date_to' => '2030-01-31',
        ]);

        $this->assertContains(
            ['label' => 'Kas Masuk', 'value' => 'Rp 115.000'],
            $viewData['summaryItems'],
        );

        $this->assertContains(
            ['label' => 'Tunai Masuk', 'value' => 'Rp 85.000'],
            $viewData['summaryItems'],
        );

        $this->assertContains(
            ['label' => 'Transfer Masuk', 'value' => 'Rp 30.000'],
            $viewData['summaryItems'],
        );
    }
}
