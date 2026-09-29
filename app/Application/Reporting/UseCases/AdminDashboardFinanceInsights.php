<?php

declare(strict_types=1);

namespace App\Application\Reporting\UseCases;

final class AdminDashboardFinanceInsights
{
    public static function build(array $expenses, array $payroll): array
    {
        $employees = [];
        foreach ($payroll['rows'] ?? [] as $row) {
            $id = $row['employee_id'];
            $employees[$id] ??= ['name' => $row['employee_name'], 'amount_rupiah' => 0];
            $employees[$id]['amount_rupiah'] += (int) $row['amount_rupiah'];
        }
        $employees = array_values($employees);
        usort($employees, static fn (array $a, array $b): int => ($b['amount_rupiah'] <=> $a['amount_rupiah']) ?: strcmp($a['name'], $b['name']));

        $categories = array_map(static fn (array $row): array => [
            'name' => $row['category_name'],
            'amount_rupiah' => (int) $row['total_amount_rupiah'],
        ], array_slice($expenses['category_rows'] ?? [], 0, 5));

        return [
            'payroll_total_rupiah' => (int) ($payroll['summary']['total_amount_rupiah'] ?? 0),
            'payroll_count' => (int) ($payroll['summary']['total_rows'] ?? 0),
            'employee_count' => count($employees),
            'employee_rows' => array_slice($employees, 0, 5),
            'expense_categories' => $categories,
            'composition' => [
                ['name' => 'Biaya operasional', 'amount_rupiah' => (int) ($expenses['summary']['total_amount_rupiah'] ?? 0)],
                ['name' => 'Gaji dibayarkan', 'amount_rupiah' => (int) ($payroll['summary']['total_amount_rupiah'] ?? 0)],
            ],
        ];
    }
}
