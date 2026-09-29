<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\ViewData\Admin;

final class AdminDashboardReportExportShortcuts
{
    /**
     * @return list<array{label: string, index: string, pdf: string, excel: string}>
     */
    public static function all(): array
    {
        return [
            [
                'label' => 'Laba Paket Service',
                'index' => 'admin.reports.service_package_profit_breakdown.index',
                'pdf' => 'admin.reports.service_package_profit_breakdown.export_pdf',
                'excel' => 'admin.reports.service_package_profit_breakdown.export_excel',
            ],
            [
                'label' => 'Laporan Transaksi',
                'index' => 'admin.reports.transaction_summary.index',
                'pdf' => 'admin.reports.transaction_summary.export_pdf',
                'excel' => 'admin.reports.transaction_summary.export_excel',
            ],
            [
                'label' => 'Buku Kas Transaksi',
                'index' => 'admin.reports.transaction_cash_ledger.index',
                'pdf' => 'admin.reports.transaction_cash_ledger.export_pdf',
                'excel' => 'admin.reports.transaction_cash_ledger.export_excel',
            ],
            [
                'label' => 'Stok dan Nilai Modal',
                'index' => 'admin.reports.inventory_stock_value.index',
                'pdf' => 'admin.reports.inventory_stock_value.export_pdf',
                'excel' => 'admin.reports.inventory_stock_value.export_excel',
            ],
            [
                'label' => 'Ringkasan Kas Operasional',
                'index' => 'admin.reports.operational_profit.index',
                'pdf' => 'admin.reports.operational_profit.export_pdf',
                'excel' => 'admin.reports.operational_profit.export_excel',
            ],
            [
                'label' => 'Biaya Operasional',
                'index' => 'admin.reports.operational_expense.index',
                'pdf' => 'admin.reports.operational_expense.export_pdf',
                'excel' => 'admin.reports.operational_expense.export_excel',
            ],
            [
                'label' => 'Gaji Karyawan',
                'index' => 'admin.reports.payroll.index',
                'pdf' => 'admin.reports.payroll.export_pdf',
                'excel' => 'admin.reports.payroll.export_excel',
            ],
            [
                'label' => 'Hutang Karyawan',
                'index' => 'admin.reports.employee_debt.index',
                'pdf' => 'admin.reports.employee_debt.export_pdf',
                'excel' => 'admin.reports.employee_debt.export_excel',
            ],
            [
                'label' => 'Hutang Pemasok',
                'index' => 'admin.reports.supplier_payable.index',
                'pdf' => 'admin.reports.supplier_payable.export_pdf',
                'excel' => 'admin.reports.supplier_payable.export_excel',
            ],
        ];
    }
}
