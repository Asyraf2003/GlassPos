<?php

declare(strict_types=1);

namespace App\Application\Reporting\Services;

final class SupplierPayableReportSummaryBuilder
{
    public function build(array $rows): array
    {
        $openRows = 0;
        $voidRows = 0;
        $settledRows = 0;
        $notDueRows = 0;
        $dueTodayRows = 0;
        $overdueRows = 0;
        $overdueOutstanding = 0;

        foreach ($rows as $row) {
            $outstanding = (int) ($row['outstanding_rupiah'] ?? 0);
            $dueStatus = (string) ($row['due_status'] ?? '');

            if ($dueStatus === 'voided') {
                $voidRows++;

                continue;
            }

            if ($outstanding > 0) {
                $openRows++;
            } else {
                $settledRows++;
            }

            if ($dueStatus === 'not_due') {
                $notDueRows++;

                continue;
            }

            if ($dueStatus === 'due_today') {
                $dueTodayRows++;

                continue;
            }

            if ($dueStatus === 'overdue') {
                $overdueRows++;
                $overdueOutstanding += $outstanding;
            }
        }

        $temporal = [];
        foreach (['opening_outstanding_rupiah', 'new_invoices_rupiah', 'adjustments_in_period_rupiah', 'payments_in_period_rupiah', 'reversals_in_period_rupiah'] as $field) {
            $temporal[$field] = array_sum(array_column($rows, $field));
        }

        return [
            ...$temporal,
            'void_rows' => $voidRows,
            'total_rows' => count($rows),
            'grand_total_rupiah' => array_sum(array_column($rows, 'grand_total_rupiah')),
            'total_paid_rupiah' => array_sum(array_column($rows, 'total_paid_rupiah')),
            'outstanding_rupiah' => array_sum(array_column($rows, 'outstanding_rupiah')),
            'receipt_count' => array_sum(array_column($rows, 'receipt_count')),
            'total_received_qty' => array_sum(array_column($rows, 'total_received_qty')),
            'open_rows' => $openRows,
            'settled_rows' => $settledRows,
            'not_due_rows' => $notDueRows,
            'due_today_rows' => $dueTodayRows,
            'overdue_rows' => $overdueRows,
            'overdue_outstanding_rupiah' => $overdueOutstanding,
        ];
    }
}
