<?php

declare(strict_types=1);

use App\Adapters\In\Http\Controllers\Admin\Supplier\EditSupplierPageController;
use App\Adapters\In\Http\Controllers\Admin\Supplier\SupplierIndexPageController;
use App\Adapters\In\Http\Controllers\Admin\Supplier\SupplierTableDataController;
use App\Adapters\In\Http\Controllers\Admin\Supplier\UpdateSupplierController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'admin.page'])->group(function (): void {
    Route::get('/admin/suppliers/table', SupplierTableDataController::class)
        ->name('admin.suppliers.table');
});

Route::middleware(['web', 'auth', 'admin.page', 'app.shell'])->group(function (): void {
    Route::post('/admin/suppliers', \App\Adapters\In\Http\Controllers\Admin\Supplier\CreateSupplierController::class)
        ->name('admin.suppliers.store');

    Route::get('/admin/suppliers', SupplierIndexPageController::class)
        ->name('admin.suppliers.index');

    Route::get('/admin/suppliers/{supplierId}/edit', EditSupplierPageController::class)
        ->name('admin.suppliers.edit');

    Route::put('/admin/suppliers/{supplierId}', UpdateSupplierController::class)
        ->name('admin.suppliers.update');
});
