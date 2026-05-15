<?php

declare(strict_types=1);

namespace App\Providers;

use App\Adapters\Out\Procurement\DatabaseSupplierPayableReminderReaderAdapter;
use App\Adapters\Out\Procurement\DatabaseSupplierPaymentProofAttachmentReaderAdapter;
use App\Adapters\Out\Procurement\DatabaseSupplierPaymentProofAttachmentWriterAdapter;
use App\Adapters\Out\Procurement\DatabaseSupplierPaymentReaderAdapter;
use App\Adapters\Out\Procurement\DatabaseSupplierPaymentReversalWriterAdapter;
use App\Adapters\Out\Procurement\DatabaseSupplierPaymentWriterAdapter;
use App\Adapters\Out\Procurement\LaravelSupplierPaymentProofFileStorageAdapter;
use App\Ports\Out\Procurement\SupplierPayableReminderReaderPort;
use App\Ports\Out\Procurement\SupplierPaymentProofAttachmentReaderPort;
use App\Ports\Out\Procurement\SupplierPaymentProofAttachmentWriterPort;
use App\Ports\Out\Procurement\SupplierPaymentProofFileStoragePort;
use App\Ports\Out\Procurement\SupplierPaymentReaderPort;
use App\Ports\Out\Procurement\SupplierPaymentReversalWriterPort;
use App\Ports\Out\Procurement\SupplierPaymentWriterPort;
use Illuminate\Support\ServiceProvider;

class ProcurementPaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SupplierPaymentWriterPort::class, DatabaseSupplierPaymentWriterAdapter::class);
        $this->app->singleton(SupplierPaymentReaderPort::class, DatabaseSupplierPaymentReaderAdapter::class);
        $this->app->singleton(SupplierPaymentReversalWriterPort::class, DatabaseSupplierPaymentReversalWriterAdapter::class);
        $this->app->singleton(SupplierPayableReminderReaderPort::class, DatabaseSupplierPayableReminderReaderAdapter::class);
        $this->app->singleton(SupplierPaymentProofAttachmentWriterPort::class, DatabaseSupplierPaymentProofAttachmentWriterAdapter::class);
        $this->app->singleton(SupplierPaymentProofAttachmentReaderPort::class, DatabaseSupplierPaymentProofAttachmentReaderAdapter::class);
        $this->app->singleton(SupplierPaymentProofFileStoragePort::class, LaravelSupplierPaymentProofFileStorageAdapter::class);
    }
}
