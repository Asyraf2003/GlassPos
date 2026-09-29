<?php

declare(strict_types=1);

namespace App\Application\Reporting\Services;

final class EmployeeDebtReportSummaryBuilder
{
    public function build(array $rows, array $statusRows): array
    {
        $paidRows = 0;
        $unpaidRows = 0;

        foreach ($statusRows as $statusRow) {
            if (($statusRow['status'] ?? null) === 'paid') {
                $paidRows += (int) ($statusRow['total_rows'] ?? 0);
            }

            if (($statusRow['status'] ?? null) === 'unpaid') {
                $unpaidRows += (int) ($statusRow['total_rows'] ?? 0);
            }
        }

        $temporal = [];
        foreach (['opening_outstanding_rupiah', 'new_debt_rupiah', 'adjustments_in_period_rupiah',
            'payments_in_period_rupiah', 'reversals_in_period_rupiah'] as $key) {
            $temporal[$key] = array_sum(array_column($rows, $key));
        }
        $closing = $temporal['opening_outstanding_rupiah'] + $temporal['new_debt_rupiah']
            + $temporal['adjustments_in_period_rupiah'] - $temporal['payments_in_period_rupiah']
            + $temporal['reversals_in_period_rupiah'];
        if ($closing !== array_sum(array_column($rows, 'remaining_balance'))) {
            throw new \RuntimeException('Reporting mismatch: employee debt opening and closing.');
        }

        return array_merge($temporal, [
            'total_rows' => count($rows),
            'total_debt' => array_sum(array_column($rows, 'total_debt')),
            'total_paid_amount' => array_sum(array_column($rows, 'total_paid_amount')),
            'total_remaining_balance' => array_sum(array_column($rows, 'remaining_balance')),
            'paid_rows' => $paidRows,
            'unpaid_rows' => $unpaidRows,
        ]);
    }
}
