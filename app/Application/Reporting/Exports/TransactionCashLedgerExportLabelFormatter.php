<?php

declare(strict_types=1);

namespace App\Application\Reporting\Exports;

final class TransactionCashLedgerExportLabelFormatter
{
    public function paymentMethodLabel(string $paymentMethod): string
    {
        return match ($paymentMethod) {
            'cash' => 'Tunai',
            'transfer' => 'Transfer',
            '' => '-',
            default => $paymentMethod,
        };
    }

    public function eventTypeLabel(string $type): string
    {
        return match ($type) {
            'payment_allocation' => 'Alokasi Pembayaran',
            'payment' => 'Pembayaran',
            'refund' => 'Pengembalian Dana',
            default => $type,
        };
    }

    public function sourceLabel(string $source): string
    {
        return match ($source) {
            'payment_allocations' => 'Pembayaran Nota',
            'payment_component_allocations' => 'Pembayaran Rincian Nota',
            'customer_payments' => 'Pembayaran Pelanggan',
            'customer_refunds' => 'Pengembalian Dana',
            'refund_component_allocations' => 'Pengembalian Rincian Nota',
            'note_revision_surplus_refund_payments' => 'Pengembalian Surplus Dibayar',
            'note_revision_surplus_dispositions' => 'Pengembalian Surplus Ditandai',
            '', '-' => '-',
            default => $source,
        };
    }

    public function directionLabel(string $direction): string
    {
        return match ($direction) {
            'in' => 'Masuk',
            'out' => 'Keluar',
            default => $direction,
        };
    }
}
