<?php

declare(strict_types=1);

namespace App\Application\Reporting\Services;

final class SupplierPayableTemporalSummaryRows
{
    public static function build(array $summary, ?string $from, ?string $to): array
    {
        if ($from === null || $to === null) {
            return [['label' => 'Sisa hutang saat ini', 'value' => (int) ($summary['outstanding_rupiah'] ?? 0)]];
        }
        $labels = [
            'opening_outstanding_rupiah' => 'Saldo awal sebelum '.$from,
            'new_invoices_rupiah' => 'Faktur baru periode',
            'payments_in_period_rupiah' => 'Pembayaran periode',
            'reversals_in_period_rupiah' => 'Reversal pembayaran periode',
            'adjustments_in_period_rupiah' => 'Penyesuaian dan void periode',
            'outstanding_rupiah' => 'Sisa per '.$to,
        ];
        $expected = $summary['opening_outstanding_rupiah'] + $summary['new_invoices_rupiah']
            + $summary['adjustments_in_period_rupiah'] - $summary['payments_in_period_rupiah'] + $summary['reversals_in_period_rupiah'];
        if ($expected !== $summary['outstanding_rupiah']) {
            throw new \RuntimeException('Supplier payable temporal reconciliation mismatch.');
        }
        $rows = [];
        foreach ($labels as $key => $label) {
            $rows[] = ['label' => $label, 'value' => (int) $summary[$key]];
        }

        return $rows;
    }
}
