<?php

declare(strict_types=1);

namespace App\Providers;

use App\Adapters\Out\Expense\DatabaseExpenseCategoryListPageQuery;
use App\Adapters\Out\Expense\DatabaseExpenseCategoryReaderAdapter;
use App\Adapters\Out\Expense\DatabaseExpenseCategoryTableReaderAdapter;
use App\Adapters\Out\Expense\DatabaseExpenseCategoryWriterAdapter;
use App\Adapters\Out\Expense\DatabaseOperationalExpenseTableReaderAdapter;
use App\Adapters\Out\Expense\DatabaseOperationalExpenseWriterAdapter;
use App\Ports\Out\Expense\ExpenseCategoryOptionReaderPort;
use App\Ports\Out\Expense\ExpenseCategoryReaderPort;
use App\Ports\Out\Expense\ExpenseCategoryTableReaderPort;
use App\Ports\Out\Expense\ExpenseCategoryWriterPort;
use App\Ports\Out\Expense\OperationalExpenseTableReaderPort;
use App\Ports\Out\Expense\OperationalExpenseWriterPort;
use Illuminate\Support\ServiceProvider;

class ExpenseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ExpenseCategoryReaderPort::class, DatabaseExpenseCategoryReaderAdapter::class);
        $this->app->singleton(ExpenseCategoryOptionReaderPort::class, DatabaseExpenseCategoryListPageQuery::class);
        $this->app->singleton(ExpenseCategoryWriterPort::class, DatabaseExpenseCategoryWriterAdapter::class);
        $this->app->singleton(ExpenseCategoryTableReaderPort::class, DatabaseExpenseCategoryTableReaderAdapter::class);
        $this->app->singleton(OperationalExpenseWriterPort::class, DatabaseOperationalExpenseWriterAdapter::class);
        $this->app->singleton(OperationalExpenseTableReaderPort::class, DatabaseOperationalExpenseTableReaderAdapter::class);
    }
}
