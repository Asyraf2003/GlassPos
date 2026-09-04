<?php

declare(strict_types=1);

namespace App\Providers;

use App\Adapters\Out\Payment\DatabaseCustomerPaymentReaderAdapter;
use App\Adapters\Out\Payment\DatabaseCustomerPaymentWriterAdapter;
use App\Adapters\Out\Payment\DatabaseCustomerRefundReaderAdapter;
use App\Adapters\Out\Payment\DatabaseCustomerRefundWriterAdapter;
use App\Adapters\Out\Payment\DatabaseLegacyPaymentAllocationReaderAdapter;
use App\Adapters\Out\Payment\DatabaseNotePaymentTimelineReaderAdapter;
use App\Adapters\Out\Payment\DatabasePaymentAllocationReaderAdapter;
use App\Adapters\Out\Payment\DatabasePaymentAllocationWriterAdapter;
use App\Adapters\Out\Payment\DatabasePaymentComponentAllocationReaderAdapter;
use App\Adapters\Out\Payment\DatabasePaymentComponentAllocationWriterAdapter;
use App\Adapters\Out\Payment\DatabaseRefundComponentAllocationReaderAdapter;
use App\Adapters\Out\Payment\DatabaseRefundComponentAllocationWriterAdapter;
use App\Application\Payment\Services\AllocatePaymentAcrossComponents;
use App\Application\Payment\Services\AllocatePaymentErrorClassifier;
use App\Application\Payment\Services\AllocateRefundAcrossComponents;
use App\Application\Payment\Services\ResolveNotePayableComponents;
use App\Application\Payment\Services\ReversedRefundedStoreStockPartPaymentGuard;
use App\Ports\Out\Payment\CustomerPaymentReaderPort;
use App\Ports\Out\Payment\CustomerPaymentWriterPort;
use App\Ports\Out\Payment\CustomerRefundReaderPort;
use App\Ports\Out\Payment\CustomerRefundWriterPort;
use App\Ports\Out\Payment\LegacyPaymentAllocationReaderPort;
use App\Ports\Out\Payment\NotePaymentTimelineReaderPort;
use App\Ports\Out\Payment\PaymentAllocationReaderPort;
use App\Ports\Out\Payment\PaymentAllocationWriterPort;
use App\Ports\Out\Payment\PaymentComponentAllocationReaderPort;
use App\Ports\Out\Payment\PaymentComponentAllocationWriterPort;
use App\Ports\Out\Payment\RefundComponentAllocationReaderPort;
use App\Ports\Out\Payment\RefundComponentAllocationWriterPort;
use Illuminate\Support\ServiceProvider;

class PaymentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AllocatePaymentErrorClassifier::class);
        $this->app->singleton(ResolveNotePayableComponents::class);
        $this->app->singleton(ReversedRefundedStoreStockPartPaymentGuard::class);
        $this->app->singleton(AllocatePaymentAcrossComponents::class);
        $this->app->singleton(AllocateRefundAcrossComponents::class);

        $this->app->singleton(CustomerPaymentWriterPort::class, DatabaseCustomerPaymentWriterAdapter::class);
        $this->app->singleton(CustomerPaymentReaderPort::class, DatabaseCustomerPaymentReaderAdapter::class);
        $this->app->singleton(CustomerRefundWriterPort::class, DatabaseCustomerRefundWriterAdapter::class);
        $this->app->singleton(CustomerRefundReaderPort::class, DatabaseCustomerRefundReaderAdapter::class);
        $this->app->singleton(RefundComponentAllocationWriterPort::class, DatabaseRefundComponentAllocationWriterAdapter::class);
        $this->app->singleton(RefundComponentAllocationReaderPort::class, DatabaseRefundComponentAllocationReaderAdapter::class);
        $this->app->singleton(PaymentAllocationWriterPort::class, DatabasePaymentAllocationWriterAdapter::class);
        $this->app->singleton(PaymentAllocationReaderPort::class, DatabasePaymentAllocationReaderAdapter::class);
        $this->app->singleton(PaymentComponentAllocationWriterPort::class, DatabasePaymentComponentAllocationWriterAdapter::class);
        $this->app->singleton(PaymentComponentAllocationReaderPort::class, DatabasePaymentComponentAllocationReaderAdapter::class);
        $this->app->singleton(LegacyPaymentAllocationReaderPort::class, DatabaseLegacyPaymentAllocationReaderAdapter::class);
        $this->app->singleton(NotePaymentTimelineReaderPort::class, DatabaseNotePaymentTimelineReaderAdapter::class);
    }
}
