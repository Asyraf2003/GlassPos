<?php

declare(strict_types=1);

use App\Adapters\In\Http\Controllers\Api\V1\Auth\LoginMobileApiController;
use App\Adapters\In\Http\Controllers\Api\V1\Auth\LogoutMobileApiController;
use App\Adapters\In\Http\Controllers\Api\V1\Auth\MeMobileApiController;
use App\Adapters\In\Http\Controllers\Api\V1\Product\SearchMobileApiProductsController;
use App\Adapters\In\Http\Controllers\Api\V1\Procurement\ShowMobileApiSupplierInvoiceController;
use App\Adapters\In\Http\Controllers\Api\V1\Procurement\UploadMobileApiSupplierPaymentProofController;
use App\Adapters\In\Http\Controllers\Api\V1\Procurement\UploadMobileApiSupplierInvoicePaymentProofController;
use App\Adapters\In\Http\Controllers\Api\V1\Procurement\ShowMobileApiSupplierPaymentProofAttachmentController;
use App\Adapters\In\Http\Controllers\Api\V1\Procurement\ListMobileApiSupplierInvoicesController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('/auth/login', LoginMobileApiController::class)
        ->middleware('throttle:mobile-login')
        ->name('api.v1.auth.login');

    Route::middleware('mobile.api.auth')->group(function (): void {
        Route::get('/me', MeMobileApiController::class)
            ->name('api.v1.me');

        Route::post('/auth/logout', LogoutMobileApiController::class)
            ->name('api.v1.auth.logout');

        Route::get('/products/search', SearchMobileApiProductsController::class)
            ->name('api.v1.products.search');

        Route::get('/supplier-invoices', ListMobileApiSupplierInvoicesController::class)
            ->name('api.v1.supplier-invoices.index');

        Route::get('/supplier-invoices/{supplierInvoiceId}', ShowMobileApiSupplierInvoiceController::class)
            ->name('api.v1.supplier-invoices.show');

        Route::post('/supplier-invoices/{supplierInvoiceId}/payment-proof', UploadMobileApiSupplierInvoicePaymentProofController::class)
            ->name('api.v1.supplier-invoices.payment-proof.store');

        Route::post('/supplier-payments/{supplierPaymentId}/proofs', UploadMobileApiSupplierPaymentProofController::class)
            ->name('api.v1.supplier-payments.proofs.store');

        Route::get('/supplier-payment-proof-attachments/{attachmentId}', ShowMobileApiSupplierPaymentProofAttachmentController::class)
            ->name('api.v1.supplier-payment-proof-attachments.show');
    });
});
