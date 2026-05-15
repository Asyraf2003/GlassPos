<?php

declare(strict_types=1);

namespace App\Providers;

use App\Adapters\Out\EmployeeFinance\DatabaseEmployeePayrollTableReaderAdapter;
use App\Application\EmployeeFinance\Context\EmployeeChangeContext;
use App\Ports\Out\EmployeeFinance\EmployeeDebtAdjustmentWriterPort;
use App\Ports\Out\EmployeeFinance\EmployeeDebtReaderPort;
use App\Ports\Out\EmployeeFinance\EmployeeDebtWriterPort;
use App\Ports\Out\EmployeeFinance\EmployeePayrollTableReaderPort;
use App\Ports\Out\EmployeeFinance\EmployeeReaderPort;
use App\Ports\Out\EmployeeFinance\EmployeeWriterPort;
use App\Ports\Out\EmployeeFinance\PayrollDisbursementReversalWriterPort;
use App\Ports\Out\EmployeeFinance\PayrollDisbursementWriterPort;
use Illuminate\Support\ServiceProvider;

class EmployeeFinanceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(EmployeeChangeContext::class, fn (): EmployeeChangeContext => new EmployeeChangeContext());

        $this->app->singleton(EmployeeReaderPort::class, \App\Adapters\Out\EmployeeFinance\DatabaseEmployeeReaderAdapter::class);
        $this->app->singleton(\App\Ports\Out\EmployeeFinance\EmployeeDetailPageReaderPort::class, \App\Adapters\Out\EmployeeFinance\DatabaseEmployeeDetailPageQuery::class);
        $this->app->singleton(\App\Ports\Out\EmployeeFinance\EmployeeDebtSummaryByEmployeeReaderPort::class, \App\Adapters\Out\EmployeeFinance\DatabaseEmployeeDebtSummaryByEmployeeQuery::class);
        $this->app->singleton(\App\Ports\Out\EmployeeFinance\EmployeePayrollSummaryByEmployeeReaderPort::class, \App\Adapters\Out\EmployeeFinance\DatabaseEmployeePayrollSummaryByEmployeeQuery::class);
        $this->app->singleton(\App\Ports\Out\EmployeeFinance\EmployeeListPageReaderPort::class, \App\Adapters\Out\EmployeeFinance\DatabaseEmployeeListPageQuery::class);
        $this->app->scoped(EmployeeWriterPort::class, \App\Adapters\Out\EmployeeFinance\DatabaseVersionedEmployeeWriterAdapter::class);
        $this->app->singleton(\App\Ports\Out\EmployeeFinance\EmployeeTableReaderPort::class, \App\Adapters\Out\EmployeeFinance\DatabaseEmployeeTableReaderAdapter::class);
        $this->app->singleton(EmployeeDebtReaderPort::class, \App\Adapters\Out\EmployeeFinance\DatabaseEmployeeDebtReaderAdapter::class);
        $this->app->singleton(\App\Ports\Out\EmployeeFinance\EmployeeDebtDetailPageReaderPort::class, \App\Adapters\Out\EmployeeFinance\DatabaseEmployeeDebtDetailPageQuery::class);
        $this->app->singleton(\App\Ports\Out\EmployeeFinance\EmployeeDebtAdjustmentListReaderPort::class, \App\Adapters\Out\EmployeeFinance\DatabaseEmployeeDebtAdjustmentListQuery::class);
        $this->app->singleton(\App\Ports\Out\EmployeeFinance\EmployeeDebtPaymentReversalListReaderPort::class, \App\Adapters\Out\EmployeeFinance\DatabaseEmployeeDebtPaymentReversalListQuery::class);
        $this->app->singleton(\App\Ports\Out\EmployeeFinance\EmployeeDebtTableReaderPort::class, \App\Adapters\Out\EmployeeFinance\DatabaseEmployeeDebtListPageQuery::class);
        $this->app->singleton(EmployeeDebtAdjustmentWriterPort::class, \App\Adapters\Out\EmployeeFinance\DatabaseEmployeeDebtAdjustmentWriterAdapter::class);
        $this->app->singleton(EmployeeDebtWriterPort::class, \App\Adapters\Out\EmployeeFinance\DatabaseEmployeeDebtWriterAdapter::class);
        $this->app->singleton(\App\Ports\Out\EmployeeFinance\EmployeeDebtPaymentReversalWriterPort::class, \App\Adapters\Out\EmployeeFinance\DatabaseEmployeeDebtPaymentReversalWriterAdapter::class);
        $this->app->singleton(EmployeePayrollTableReaderPort::class, DatabaseEmployeePayrollTableReaderAdapter::class);
        $this->app->singleton(PayrollDisbursementWriterPort::class, \App\Adapters\Out\EmployeeFinance\DatabasePayrollDisbursementWriterAdapter::class);
        $this->app->singleton(PayrollDisbursementReversalWriterPort::class, \App\Adapters\Out\EmployeeFinance\DatabasePayrollDisbursementReversalWriterAdapter::class);
        $this->app->singleton(\App\Ports\Out\EmployeeFinance\PayrollTableReaderPort::class, \App\Adapters\Out\EmployeeFinance\DatabasePayrollTableReaderAdapter::class);
    }
}
