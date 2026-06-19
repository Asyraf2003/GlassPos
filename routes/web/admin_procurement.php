<?php

declare(strict_types=1);

use App\Adapters\In\Http\Controllers\Admin\Procurement\AttachSupplierPaymentProofController;
use App\Adapters\In\Http\Controllers\Admin\Procurement\CreateSupplierInvoicePageController;
use App\Adapters\In\Http\Controllers\Admin\Procurement\EditSupplierInvoicePageController;
use App\Adapters\In\Http\Controllers\Admin\Procurement\ProcurementInvoicePaymentProofPageController;
use App\Adapters\In\Http\Controllers\Admin\Procurement\ProductLookupController;
use App\Adapters\In\Http\Controllers\Admin\Procurement\ProcurementInvoiceDetailPageController;
use App\Adapters\In\Http\Controllers\Admin\Procurement\ProcurementInvoiceIndexPageController;
use App\Adapters\In\Http\Controllers\Admin\Procurement\ProcurementInvoiceTableDataController;
use App\Adapters\In\Http\Controllers\Admin\Procurement\RecordSupplierPaymentController;
use App\Adapters\In\Http\Controllers\Admin\Procurement\ReverseSupplierPaymentController;
use App\Adapters\In\Http\Controllers\Admin\Procurement\ReceiveSupplierInvoiceController;
use App\Adapters\In\Http\Controllers\Admin\Procurement\ReverseSupplierReceiptController;
use App\Adapters\In\Http\Controllers\Admin\Procurement\ReviseSupplierInvoicePageController;
use App\Adapters\In\Http\Controllers\Admin\Procurement\ServeSupplierPaymentProofAttachmentController;
use App\Adapters\In\Http\Controllers\Admin\Procurement\StoreSupplierInvoiceController;
use App\Adapters\In\Http\Controllers\Admin\Procurement\SupplierLookupController;
use App\Adapters\In\Http\Controllers\Admin\Procurement\UpdateSupplierInvoiceController;
use App\Adapters\In\Http\Controllers\Admin\Procurement\VoidSupplierInvoiceController;
use App\Adapters\In\Http\Controllers\Admin\Procurement\UploadSupplierInvoicePaymentProofController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'admin.page'])->group(function (): void {
    Route::get('/admin/procurement/supplier-invoices/table', ProcurementInvoiceTableDataController::class)
        ->name('admin.procurement.supplier-invoices.table');

    Route::get('/admin/procurement/products/lookup', ProductLookupController::class)
        ->name('admin.procurement.products.lookup');

    Route::get('/admin/procurement/suppliers/lookup', SupplierLookupController::class)
        ->name('admin.procurement.suppliers.lookup');

    Route::post('/admin/procurement/supplier-invoices/{supplierInvoiceId}/receive', ReceiveSupplierInvoiceController::class)
        ->name('admin.procurement.supplier-invoices.receive');

    Route::post('/admin/procurement/supplier-invoices/{supplierInvoiceId}/payments', RecordSupplierPaymentController::class)
        ->name('admin.procurement.supplier-invoices.payments.store');

    Route::post('/admin/procurement/supplier-invoices/{supplierInvoiceId}/payment-proof', UploadSupplierInvoicePaymentProofController::class)
        ->name('admin.procurement.supplier-invoices.payment-proof.store');

    Route::post('/admin/procurement/supplier-receipts/{supplierReceiptId}/reverse', ReverseSupplierReceiptController::class)
        ->name('admin.procurement.supplier-receipts.reverse.store');

    Route::post('/admin/procurement/supplier-invoices/{supplierInvoiceId}/void', VoidSupplierInvoiceController::class)
        ->name('admin.procurement.supplier-invoices.void');

    Route::post('/admin/procurement/supplier-payments/{supplierPaymentId}/reverse', ReverseSupplierPaymentController::class)
        ->name('admin.procurement.supplier-payments.reverse.store');

    Route::post('/admin/procurement/supplier-payments/{supplierPaymentId}/proof', AttachSupplierPaymentProofController::class)
        ->name('admin.procurement.supplier-payments.proof.store');

    Route::get('/admin/procurement/supplier-payment-proof-attachments/{attachmentId}', ServeSupplierPaymentProofAttachmentController::class)
        ->name('admin.procurement.supplier-payment-proof-attachments.show');
});

Route::middleware(['web', 'auth', 'admin.page', 'app.shell'])->group(function (): void {
    Route::get('/admin/procurement/supplier-invoices', ProcurementInvoiceIndexPageController::class)
        ->name('admin.procurement.supplier-invoices.index');

    Route::get('/admin/procurement/supplier-invoices/create', CreateSupplierInvoicePageController::class)
        ->name('admin.procurement.supplier-invoices.create');

    Route::get('/admin/procurement/supplier-invoices/{supplierInvoiceId}/payment-proofs', ProcurementInvoicePaymentProofPageController::class)
        ->name('admin.procurement.supplier-invoices.payment-proofs.show');

    Route::get('/admin/procurement/supplier-invoices/{supplierInvoiceId}', ProcurementInvoiceDetailPageController::class)
        ->name('admin.procurement.supplier-invoices.show');

    Route::get('/admin/procurement/supplier-invoices/{supplierInvoiceId}/edit', EditSupplierInvoicePageController::class)
        ->name('admin.procurement.supplier-invoices.edit');

    Route::get('/admin/procurement/supplier-invoices/{supplierInvoiceId}/revise', ReviseSupplierInvoicePageController::class)
        ->name('admin.procurement.supplier-invoices.revise');

    Route::put('/admin/procurement/supplier-invoices/{supplierInvoiceId}', UpdateSupplierInvoiceController::class)
        ->name('admin.procurement.supplier-invoices.update');

    Route::post('/admin/procurement/supplier-invoices', StoreSupplierInvoiceController::class)
        ->middleware('transaction.entry')
        ->name('admin.procurement.supplier-invoices.store');
});
