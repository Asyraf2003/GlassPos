<?php

declare(strict_types=1);

namespace App\Providers;

use App\Adapters\Out\Procurement\DatabaseProcurementInvoiceDetailReaderAdapter;
use App\Adapters\Out\Procurement\DatabaseProcurementInvoiceTableReaderAdapter;
use App\Adapters\Out\Procurement\DatabaseSupplierInvoiceDuplicateNumberCheckerAdapter;
use App\Adapters\Out\Procurement\DatabaseSupplierInvoiceLineReaderAdapter;
use App\Adapters\Out\Procurement\DatabaseSupplierInvoiceListProjectionSourceReaderAdapter;
use App\Adapters\Out\Procurement\DatabaseSupplierInvoiceListProjectionWriterAdapter;
use App\Adapters\Out\Procurement\DatabaseSupplierInvoiceReaderAdapter;
use App\Adapters\Out\Procurement\DatabaseSupplierInvoiceVoidStatusReaderAdapter;
use App\Adapters\Out\Procurement\DatabaseSupplierInvoiceVoidWriterAdapter;
use App\Adapters\Out\Procurement\DatabaseVersionedSupplierInvoiceWriterAdapter;
use App\Application\Procurement\Context\SupplierInvoiceChangeContext;
use App\Application\Procurement\Services\SupplierInvoiceFactory;
use App\Application\Procurement\Services\SupplierInvoiceListProjectionService;
use App\Ports\Out\Procurement\ProcurementInvoiceDetailReaderPort;
use App\Ports\Out\Procurement\ProcurementInvoiceTableReaderPort;
use App\Ports\Out\Procurement\SupplierInvoiceDuplicateNumberCheckerPort;
use App\Ports\Out\Procurement\SupplierInvoiceLifecyclePort;
use App\Ports\Out\Procurement\SupplierInvoiceLineReaderPort;
use App\Ports\Out\Procurement\SupplierInvoiceListProjectionSourceReaderPort;
use App\Ports\Out\Procurement\SupplierInvoiceListProjectionWriterPort;
use App\Ports\Out\Procurement\SupplierInvoiceReaderPort;
use App\Ports\Out\Procurement\SupplierInvoiceVoidStatusReaderPort;
use App\Ports\Out\Procurement\SupplierInvoiceVoidWriterPort;
use App\Ports\Out\Procurement\SupplierInvoiceWriterPort;
use Illuminate\Support\ServiceProvider;

class ProcurementInvoiceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SupplierInvoiceFactory::class);
        $this->app->singleton(SupplierInvoiceListProjectionService::class);
        $this->app->scoped(SupplierInvoiceChangeContext::class, fn (): SupplierInvoiceChangeContext => new SupplierInvoiceChangeContext());

        $this->app->singleton(ProcurementInvoiceTableReaderPort::class, DatabaseProcurementInvoiceTableReaderAdapter::class);
        $this->app->scoped(SupplierInvoiceWriterPort::class, DatabaseVersionedSupplierInvoiceWriterAdapter::class);
        $this->app->scoped(SupplierInvoiceLifecyclePort::class, DatabaseVersionedSupplierInvoiceWriterAdapter::class);
        $this->app->singleton(SupplierInvoiceReaderPort::class, DatabaseSupplierInvoiceReaderAdapter::class);
        $this->app->singleton(SupplierInvoiceDuplicateNumberCheckerPort::class, DatabaseSupplierInvoiceDuplicateNumberCheckerAdapter::class);
        $this->app->singleton(SupplierInvoiceVoidStatusReaderPort::class, DatabaseSupplierInvoiceVoidStatusReaderAdapter::class);
        $this->app->singleton(SupplierInvoiceVoidWriterPort::class, DatabaseSupplierInvoiceVoidWriterAdapter::class);
        $this->app->singleton(ProcurementInvoiceDetailReaderPort::class, DatabaseProcurementInvoiceDetailReaderAdapter::class);
        $this->app->singleton(SupplierInvoiceLineReaderPort::class, DatabaseSupplierInvoiceLineReaderAdapter::class);
        $this->app->singleton(SupplierInvoiceListProjectionSourceReaderPort::class, DatabaseSupplierInvoiceListProjectionSourceReaderAdapter::class);
        $this->app->singleton(SupplierInvoiceListProjectionWriterPort::class, DatabaseSupplierInvoiceListProjectionWriterAdapter::class);
    }
}
