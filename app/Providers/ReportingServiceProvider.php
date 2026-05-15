<?php

declare(strict_types=1);

namespace App\Providers;

use App\Adapters\Out\Reporting\DatabaseDashboardInventoryOverviewReaderAdapter;
use App\Adapters\Out\Reporting\DatabaseDashboardOperationalPerformanceReaderAdapter;
use App\Adapters\Out\Reporting\DatabaseDashboardTopSellingProductReaderAdapter;
use App\Adapters\Out\Reporting\DatabaseEmployeeDebtReportingSourceReaderAdapter;
use App\Adapters\Out\Reporting\DatabaseInventoryMovementReportingSourceReaderAdapter;
use App\Adapters\Out\Reporting\DatabaseOperationalExpenseReportingSourceReaderAdapter;
use App\Adapters\Out\Reporting\DatabaseOperationalProfitReportingSourceReaderAdapter;
use App\Adapters\Out\Reporting\DatabasePayrollReportingSourceReaderAdapter;
use App\Adapters\Out\Reporting\DatabaseSupplierPayableReportingSourceReaderAdapter;
use App\Adapters\Out\Reporting\DatabaseTransactionReportingSourceReaderAdapter;
use App\Adapters\Out\Reporting\LaravelDashboardReportCacheAdapter;
use App\Ports\Out\Reporting\DashboardInventoryOverviewReaderPort;
use App\Ports\Out\Reporting\DashboardOperationalPerformanceReaderPort;
use App\Ports\Out\Reporting\DashboardReportCachePort;
use App\Ports\Out\Reporting\DashboardTopSellingProductReaderPort;
use App\Ports\Out\Reporting\EmployeeDebtReportingSourceReaderPort;
use App\Ports\Out\Reporting\InventoryMovementReportingSourceReaderPort;
use App\Ports\Out\Reporting\OperationalExpenseReportingSourceReaderPort;
use App\Ports\Out\Reporting\OperationalProfitReportingSourceReaderPort;
use App\Ports\Out\Reporting\PayrollReportingSourceReaderPort;
use App\Ports\Out\Reporting\SupplierPayableReportingSourceReaderPort;
use App\Ports\Out\Reporting\TransactionReportingSourceReaderPort;
use Illuminate\Support\ServiceProvider;

class ReportingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DashboardReportCachePort::class, LaravelDashboardReportCacheAdapter::class);
        $this->app->singleton(DashboardInventoryOverviewReaderPort::class, DatabaseDashboardInventoryOverviewReaderAdapter::class);
        $this->app->singleton(DashboardOperationalPerformanceReaderPort::class, DatabaseDashboardOperationalPerformanceReaderAdapter::class);
        $this->app->singleton(DashboardTopSellingProductReaderPort::class, DatabaseDashboardTopSellingProductReaderAdapter::class);
        $this->app->singleton(TransactionReportingSourceReaderPort::class, DatabaseTransactionReportingSourceReaderAdapter::class);
        $this->app->singleton(OperationalExpenseReportingSourceReaderPort::class, DatabaseOperationalExpenseReportingSourceReaderAdapter::class);
        $this->app->singleton(EmployeeDebtReportingSourceReaderPort::class, DatabaseEmployeeDebtReportingSourceReaderAdapter::class);
        $this->app->singleton(PayrollReportingSourceReaderPort::class, DatabasePayrollReportingSourceReaderAdapter::class);
        $this->app->singleton(SupplierPayableReportingSourceReaderPort::class, DatabaseSupplierPayableReportingSourceReaderAdapter::class);
        $this->app->singleton(InventoryMovementReportingSourceReaderPort::class, DatabaseInventoryMovementReportingSourceReaderAdapter::class);
        $this->app->singleton(OperationalProfitReportingSourceReaderPort::class, DatabaseOperationalProfitReportingSourceReaderAdapter::class);
    }
}
