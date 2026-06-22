<?php

declare(strict_types=1);

use App\Adapters\In\Http\Controllers\Admin\Reporting\EmployeeDebtReportExcelExportController;
use App\Adapters\In\Http\Controllers\Admin\Reporting\EmployeeDebtReportPageController;
use App\Adapters\In\Http\Controllers\Admin\Reporting\EmployeeDebtReportPdfExportController;
use App\Adapters\In\Http\Controllers\Admin\Reporting\InventoryStockValueReportExcelExportController;
use App\Adapters\In\Http\Controllers\Admin\Reporting\InventoryStockValueReportPdfExportController;
use App\Adapters\In\Http\Controllers\Admin\Reporting\InventoryStockValueReportPageController;
use App\Adapters\In\Http\Controllers\Admin\Reporting\OperationalExpenseReportExcelExportController;
use App\Adapters\In\Http\Controllers\Admin\Reporting\OperationalExpenseReportPdfExportController;
use App\Adapters\In\Http\Controllers\Admin\Reporting\OperationalExpenseReportPageController;
use App\Adapters\In\Http\Controllers\Admin\Reporting\OperationalProfitReportExcelExportController;
use App\Adapters\In\Http\Controllers\Admin\Reporting\OperationalProfitReportPageController;
use App\Adapters\In\Http\Controllers\Admin\Reporting\OperationalProfitReportPdfExportController;
use App\Adapters\In\Http\Controllers\Admin\Reporting\PayrollReportExcelExportController;
use App\Adapters\In\Http\Controllers\Admin\Reporting\ServicePackageProfitBreakdownReportPageController;
use App\Adapters\In\Http\Controllers\Admin\Reporting\PayrollReportPageController;
use App\Adapters\In\Http\Controllers\Admin\Reporting\PayrollReportPdfExportController;
use App\Adapters\In\Http\Controllers\Admin\Reporting\SupplierPayableReportExcelExportController;
use App\Adapters\In\Http\Controllers\Admin\Reporting\SupplierPayableReportPageController;
use App\Adapters\In\Http\Controllers\Admin\Reporting\SupplierPayableReportPdfExportController;
use App\Adapters\In\Http\Controllers\Admin\Reporting\TransactionCashLedgerExcelExportController;
use App\Adapters\In\Http\Controllers\Admin\Reporting\TransactionCashLedgerPageController;
use App\Adapters\In\Http\Controllers\Admin\Reporting\TransactionCashLedgerPdfExportController;
use App\Adapters\In\Http\Controllers\Admin\Reporting\TransactionReportExcelExportController;
use App\Adapters\In\Http\Controllers\Admin\Reporting\TransactionReportPdfExportController;
use App\Adapters\In\Http\Controllers\Admin\Reporting\TransactionReportPageController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'admin.page', 'app.shell'])->group(function (): void {
    Route::get('/admin/reports/transaction-cash-ledger/export.xlsx', TransactionCashLedgerExcelExportController::class)
        ->name('admin.reports.transaction_cash_ledger.export_excel');

    Route::get('/admin/reports/transaction-cash-ledger/export.pdf', TransactionCashLedgerPdfExportController::class)
        ->name('admin.reports.transaction_cash_ledger.export_pdf');

    Route::get('/admin/reports/transaction-cash-ledger', TransactionCashLedgerPageController::class)
        ->name('admin.reports.transaction_cash_ledger.index');


    Route::get('/admin/reports/payrolls/export.xlsx', PayrollReportExcelExportController::class)
        ->name('admin.reports.payroll.export_excel');

    Route::get('/admin/reports/payrolls/export.pdf', PayrollReportPdfExportController::class)
        ->name('admin.reports.payroll.export_pdf');

    Route::get('/admin/reports/payrolls', PayrollReportPageController::class)
        ->name('admin.reports.payroll.index');

    Route::get('/admin/reports/employee-debts/export.xlsx', EmployeeDebtReportExcelExportController::class)
        ->name('admin.reports.employee_debt.export_excel');

    Route::get('/admin/reports/employee-debts/export.pdf', EmployeeDebtReportPdfExportController::class)
        ->name('admin.reports.employee_debt.export_pdf');

    Route::get('/admin/reports/employee-debts', EmployeeDebtReportPageController::class)
        ->name('admin.reports.employee_debt.index');

    Route::get('/admin/reports/operational-profit/export.xlsx', OperationalProfitReportExcelExportController::class)
        ->name('admin.reports.operational_profit.export_excel');

    Route::get('/admin/reports/operational-profit/export.pdf', OperationalProfitReportPdfExportController::class)
        ->name('admin.reports.operational_profit.export_pdf');

    Route::get('/admin/reports/operational-profit', OperationalProfitReportPageController::class)
        ->name('admin.reports.operational_profit.index');

    Route::get('/admin/reports/service-package-profit-breakdown', ServicePackageProfitBreakdownReportPageController::class)
        ->name('admin.reports.service_package_profit_breakdown.index');


    Route::get('/admin/reports/operational-expenses/export.xlsx', OperationalExpenseReportExcelExportController::class)
        ->name('admin.reports.operational_expense.export_excel');

    Route::get('/admin/reports/operational-expenses/export.pdf', OperationalExpenseReportPdfExportController::class)
        ->name('admin.reports.operational_expense.export_pdf');

    Route::get('/admin/reports/operational-expenses', OperationalExpenseReportPageController::class)
        ->name('admin.reports.operational_expense.index');

    Route::get('/admin/reports/supplier-payables/export.xlsx', SupplierPayableReportExcelExportController::class)
        ->name('admin.reports.supplier_payable.export_excel');

    Route::get('/admin/reports/supplier-payables/export.pdf', SupplierPayableReportPdfExportController::class)
        ->name('admin.reports.supplier_payable.export_pdf');

    Route::get('/admin/reports/supplier-payables', SupplierPayableReportPageController::class)
        ->name('admin.reports.supplier_payable.index');

    Route::get('/admin/reports/inventory-stock-value/export.pdf', InventoryStockValueReportPdfExportController::class)
        ->name('admin.reports.inventory_stock_value.export_pdf');

    Route::get('/admin/reports/inventory-stock-value/export.xlsx', InventoryStockValueReportExcelExportController::class)
        ->name('admin.reports.inventory_stock_value.export_excel');

    Route::get('/admin/reports/inventory-stock-value', InventoryStockValueReportPageController::class)
        ->name('admin.reports.inventory_stock_value.index');

    Route::get('/admin/reports/transactions/export.xlsx', TransactionReportExcelExportController::class)
        ->name('admin.reports.transaction_summary.export_excel');

    Route::get('/admin/reports/transactions/export.pdf', TransactionReportPdfExportController::class)
        ->name('admin.reports.transaction_summary.export_pdf');

    Route::get('/admin/reports/transactions', TransactionReportPageController::class)
        ->name('admin.reports.transaction_summary.index');
});
