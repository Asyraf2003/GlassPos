<?php

declare(strict_types=1);

namespace App\Providers;

use App\Adapters\Out\Procurement\DatabaseSupplierListProjectionSourceReaderAdapter;
use App\Adapters\Out\Procurement\DatabaseSupplierListProjectionWriterAdapter;
use App\Adapters\Out\Procurement\DatabaseSupplierReaderAdapter;
use App\Adapters\Out\Procurement\DatabaseSupplierReceiptLineReaderAdapter;
use App\Adapters\Out\Procurement\DatabaseSupplierReceiptReversalWriterAdapter;
use App\Adapters\Out\Procurement\DatabaseSupplierReceiptWriterAdapter;
use App\Adapters\Out\Procurement\DatabaseSupplierTableReaderAdapter;
use App\Adapters\Out\Procurement\DatabaseSupplierWriterAdapter;
use App\Application\Procurement\Services\SupplierListProjectionService;
use App\Application\Procurement\Services\SupplierReceiptFactory;
use App\Application\Procurement\Services\SupplierService;
use App\Ports\Out\Procurement\SupplierListProjectionSourceReaderPort;
use App\Ports\Out\Procurement\SupplierListProjectionWriterPort;
use App\Ports\Out\Procurement\SupplierReaderPort;
use App\Ports\Out\Procurement\SupplierReceiptLineReaderPort;
use App\Ports\Out\Procurement\SupplierReceiptReversalWriterPort;
use App\Ports\Out\Procurement\SupplierReceiptWriterPort;
use App\Ports\Out\Procurement\SupplierTableReaderPort;
use App\Ports\Out\Procurement\SupplierWriterPort;
use Illuminate\Support\ServiceProvider;

class ProcurementSupplierServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SupplierService::class);
        $this->app->singleton(SupplierListProjectionService::class);
        $this->app->singleton(SupplierReceiptFactory::class);

        $this->app->singleton(SupplierReaderPort::class, DatabaseSupplierReaderAdapter::class);
        $this->app->singleton(SupplierWriterPort::class, DatabaseSupplierWriterAdapter::class);
        $this->app->singleton(SupplierTableReaderPort::class, DatabaseSupplierTableReaderAdapter::class);
        $this->app->singleton(SupplierListProjectionSourceReaderPort::class, DatabaseSupplierListProjectionSourceReaderAdapter::class);
        $this->app->singleton(SupplierListProjectionWriterPort::class, DatabaseSupplierListProjectionWriterAdapter::class);
        $this->app->singleton(SupplierReceiptLineReaderPort::class, DatabaseSupplierReceiptLineReaderAdapter::class);
        $this->app->singleton(SupplierReceiptReversalWriterPort::class, DatabaseSupplierReceiptReversalWriterAdapter::class);
        $this->app->singleton(SupplierReceiptWriterPort::class, DatabaseSupplierReceiptWriterAdapter::class);
    }
}
