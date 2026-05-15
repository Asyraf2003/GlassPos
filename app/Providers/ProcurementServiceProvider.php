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
use App\Adapters\Out\Procurement\DatabaseSupplierListProjectionSourceReaderAdapter;
use App\Adapters\Out\Procurement\DatabaseSupplierListProjectionWriterAdapter;
use App\Adapters\Out\Procurement\DatabaseSupplierPayableReminderReaderAdapter;
use App\Adapters\Out\Procurement\DatabaseSupplierPaymentProofAttachmentReaderAdapter;
use App\Adapters\Out\Procurement\DatabaseSupplierPaymentProofAttachmentWriterAdapter;
use App\Adapters\Out\Procurement\DatabaseSupplierPaymentReaderAdapter;
use App\Adapters\Out\Procurement\DatabaseSupplierPaymentReversalWriterAdapter;
use App\Adapters\Out\Procurement\DatabaseSupplierPaymentWriterAdapter;
use App\Adapters\Out\Procurement\DatabaseSupplierReaderAdapter;
use App\Adapters\Out\Procurement\DatabaseSupplierReceiptLineReaderAdapter;
use App\Adapters\Out\Procurement\DatabaseSupplierReceiptReversalWriterAdapter;
use App\Adapters\Out\Procurement\DatabaseSupplierReceiptWriterAdapter;
use App\Adapters\Out\Procurement\DatabaseSupplierTableReaderAdapter;
use App\Adapters\Out\Procurement\DatabaseSupplierWriterAdapter;
use App\Adapters\Out\Procurement\DatabaseVersionedSupplierInvoiceWriterAdapter;
use App\Adapters\Out\Procurement\LaravelSupplierPaymentProofFileStorageAdapter;
use App\Application\Procurement\Context\SupplierInvoiceChangeContext;
use App\Application\Procurement\Services\SupplierInvoiceFactory;
use App\Application\Procurement\Services\SupplierInvoiceListProjectionService;
use App\Application\Procurement\Services\SupplierListProjectionService;
use App\Application\Procurement\Services\SupplierReceiptFactory;
use App\Application\Procurement\Services\SupplierService;
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
use App\Ports\Out\Procurement\SupplierListProjectionSourceReaderPort;
use App\Ports\Out\Procurement\SupplierListProjectionWriterPort;
use App\Ports\Out\Procurement\SupplierPayableReminderReaderPort;
use App\Ports\Out\Procurement\SupplierPaymentProofAttachmentReaderPort;
use App\Ports\Out\Procurement\SupplierPaymentProofAttachmentWriterPort;
use App\Ports\Out\Procurement\SupplierPaymentProofFileStoragePort;
use App\Ports\Out\Procurement\SupplierPaymentReaderPort;
use App\Ports\Out\Procurement\SupplierPaymentReversalWriterPort;
use App\Ports\Out\Procurement\SupplierPaymentWriterPort;
use App\Ports\Out\Procurement\SupplierReaderPort;
use App\Ports\Out\Procurement\SupplierReceiptLineReaderPort;
use App\Ports\Out\Procurement\SupplierReceiptReversalWriterPort;
use App\Ports\Out\Procurement\SupplierReceiptWriterPort;
use App\Ports\Out\Procurement\SupplierTableReaderPort;
use App\Ports\Out\Procurement\SupplierWriterPort;
use Illuminate\Support\ServiceProvider;

class ProcurementServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SupplierService::class);
        $this->app->singleton(SupplierInvoiceFactory::class);
        $this->app->singleton(SupplierInvoiceListProjectionService::class);
        $this->app->singleton(SupplierListProjectionService::class);
        $this->app->singleton(SupplierReceiptFactory::class);
        $this->app->scoped(SupplierInvoiceChangeContext::class, fn (): SupplierInvoiceChangeContext => new SupplierInvoiceChangeContext());

        $this->app->singleton(SupplierReaderPort::class, DatabaseSupplierReaderAdapter::class);
        $this->app->singleton(SupplierWriterPort::class, DatabaseSupplierWriterAdapter::class);
        $this->app->singleton(ProcurementInvoiceTableReaderPort::class, DatabaseProcurementInvoiceTableReaderAdapter::class);
        $this->app->singleton(SupplierTableReaderPort::class, DatabaseSupplierTableReaderAdapter::class);
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
        $this->app->singleton(SupplierListProjectionSourceReaderPort::class, DatabaseSupplierListProjectionSourceReaderAdapter::class);
        $this->app->singleton(SupplierListProjectionWriterPort::class, DatabaseSupplierListProjectionWriterAdapter::class);
        $this->app->singleton(SupplierReceiptLineReaderPort::class, DatabaseSupplierReceiptLineReaderAdapter::class);
        $this->app->singleton(SupplierReceiptReversalWriterPort::class, DatabaseSupplierReceiptReversalWriterAdapter::class);
        $this->app->singleton(SupplierReceiptWriterPort::class, DatabaseSupplierReceiptWriterAdapter::class);
        $this->app->singleton(SupplierPaymentWriterPort::class, DatabaseSupplierPaymentWriterAdapter::class);
        $this->app->singleton(SupplierPaymentReaderPort::class, DatabaseSupplierPaymentReaderAdapter::class);
        $this->app->singleton(SupplierPaymentReversalWriterPort::class, DatabaseSupplierPaymentReversalWriterAdapter::class);
        $this->app->singleton(SupplierPayableReminderReaderPort::class, DatabaseSupplierPayableReminderReaderAdapter::class);
        $this->app->singleton(SupplierPaymentProofAttachmentWriterPort::class, DatabaseSupplierPaymentProofAttachmentWriterAdapter::class);
        $this->app->singleton(SupplierPaymentProofAttachmentReaderPort::class, DatabaseSupplierPaymentProofAttachmentReaderAdapter::class);
        $this->app->singleton(SupplierPaymentProofFileStoragePort::class, LaravelSupplierPaymentProofFileStorageAdapter::class);
    }
}
