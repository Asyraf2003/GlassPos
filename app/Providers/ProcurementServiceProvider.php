<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class ProcurementServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(ProcurementSupplierServiceProvider::class);
        $this->app->register(ProcurementInvoiceServiceProvider::class);
        $this->app->register(ProcurementPaymentServiceProvider::class);
    }
}
