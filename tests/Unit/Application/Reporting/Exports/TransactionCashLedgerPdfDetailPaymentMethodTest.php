<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Reporting\Exports;

use App\Application\Reporting\Exports\TransactionCashLedgerPdfViewDataBuilder;
use App\Ports\Out\ClockPort;
use DateTimeImmutable;
use Tests\TestCase;

final class TransactionCashLedgerPdfDetailPaymentMethodTest extends TestCase
{
    public function test_pdf_view_data_detail_rows_expose_payment_method_for_money_in_rows(): void
    {
        $builder = new TransactionCashLedgerPdfViewDataBuilder(new class implements ClockPort {
            public function now(): DateTimeImmutable
            {
                return new DateTimeImmutable('2030-01-31 10:00:00');
            }
        });

        $viewData = $builder->build([
            'summary' => [
                'total_events' => 2,
                'total_cash_in_rupiah' => 115000,
                'cash_in_rupiah' => 85000,
                'transfer_in_rupiah' => 30000,
                'total_cash_out_rupiah' => 0,
                'net_amount_rupiah' => 115000,
            ],
            'rows' => [
                [
                    'event_date' => '2030-01-31',
                    'note_id' => 'note-pdf-cash-ledger-001',
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
                    'note_id' => 'note-pdf-cash-ledger-002',
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
            ],
        ], [
            'date_from' => '2030-01-01',
            'date_to' => '2030-01-31',
        ]);

        $this->assertArrayHasKey('payment_method', $viewData['rows'][0]);
        $this->assertSame('Tunai', $viewData['rows'][0]['payment_method']);
        $this->assertSame('Rp 85.000', $viewData['rows'][0]['cash_amount_paid']);
        $this->assertSame('Rp 100.000', $viewData['rows'][0]['cash_amount_received']);
        $this->assertSame('Rp 15.000', $viewData['rows'][0]['cash_change']);

        $this->assertArrayHasKey('payment_method', $viewData['rows'][1]);
        $this->assertSame('Transfer', $viewData['rows'][1]['payment_method']);
        $this->assertSame('-', $viewData['rows'][1]['cash_amount_paid']);
        $this->assertSame('-', $viewData['rows'][1]['cash_amount_received']);
        $this->assertSame('-', $viewData['rows'][1]['cash_change']);
    }
}
