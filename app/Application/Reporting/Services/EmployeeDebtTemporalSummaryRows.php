<?php

declare(strict_types=1);

namespace App\Application\Reporting\Services;

final class EmployeeDebtTemporalSummaryRows
{
    /** @return list<array{label:string,value:int}> */
    public function build(array $summary, string $asOf): array
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $asOf);
        $label = $date === false ? $asOf : $date->format('d-m-Y');
        $fields = [
            'opening_outstanding_rupiah' => 'Sisa awal periode',
            'new_debt_rupiah' => 'Hutang baru dalam periode',
            'adjustments_in_period_rupiah' => 'Penyesuaian pokok dalam periode',
            'payments_in_period_rupiah' => 'Pembayaran dalam periode',
            'reversals_in_period_rupiah' => 'Pembatalan pembayaran dalam periode',
            'total_remaining_balance' => 'Sisa per '.$label,
        ];
        $rows = [];
        foreach ($fields as $key => $title) {
            $rows[] = ['label' => $title, 'value' => (int) ($summary[$key] ?? 0)];
        }

        return $rows;
    }
}
