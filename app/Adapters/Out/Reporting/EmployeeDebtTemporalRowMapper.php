<?php

declare(strict_types=1);

namespace App\Adapters\Out\Reporting;

final class EmployeeDebtTemporalRowMapper
{
    public static function map(object $row, string $start): array
    {
        $original = (int) $row->total_debt - (int) $row->all_adjustments;
        $principal = $original + (int) $row->closing_adjustments;
        $paid = (int) $row->closing_paid;
        $closing = $principal - $paid;
        $opening = (string) $row->created_at < $start
            ? $original + (int) $row->opening_adjustments - (int) $row->opening_paid : 0;

        return [
            'debt_id' => (string) $row->id,
            'employee_id' => (string) $row->employee_id,
            'employee_name' => isset($row->employee_name) && $row->employee_name !== null ? (string) $row->employee_name : '-',
            'recorded_at' => (string) $row->created_at,
            'total_debt' => $principal,
            'total_paid_amount' => $paid,
            'remaining_balance' => $closing,
            'status' => $closing > 0 ? 'unpaid' : 'paid',
            'notes' => $row->notes !== null ? (string) $row->notes : null,
            'opening_outstanding_rupiah' => $opening,
            'new_debt_rupiah' => (string) $row->created_at >= $start ? $original : 0,
            'adjustments_in_period_rupiah' => (int) $row->closing_adjustments - (int) $row->opening_adjustments,
            'payments_in_period_rupiah' => (int) $row->period_payments,
            'reversals_in_period_rupiah' => (int) $row->period_reversals,
        ];
    }
}
