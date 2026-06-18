<?php

declare(strict_types=1);

use App\Adapters\In\Http\Controllers\Admin\ServiceCatalog\ActivateServiceCatalogItemController;
use App\Adapters\In\Http\Controllers\Admin\ServiceCatalog\CreateServiceCatalogItemPageController;
use App\Adapters\In\Http\Controllers\Admin\ServiceCatalog\DeactivateServiceCatalogItemController;
use App\Adapters\In\Http\Controllers\Admin\ServiceCatalog\EditServiceCatalogItemPageController;
use App\Adapters\In\Http\Controllers\Admin\ServiceCatalog\ServiceCatalogIndexPageController;
use App\Adapters\In\Http\Controllers\Admin\ServiceCatalog\StoreServiceCatalogItemController;
use App\Adapters\In\Http\Controllers\Admin\ServiceCatalog\UpdateServiceCatalogItemController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'admin.page'])->group(function (): void {
    Route::post('/admin/services', StoreServiceCatalogItemController::class)
        ->name('admin.services.store');

    Route::put('/admin/services/{serviceId}', UpdateServiceCatalogItemController::class)
        ->name('admin.services.update');

    Route::patch('/admin/services/{serviceId}/activate', ActivateServiceCatalogItemController::class)
        ->name('admin.services.activate');

    Route::patch('/admin/services/{serviceId}/deactivate', DeactivateServiceCatalogItemController::class)
        ->name('admin.services.deactivate');
});

Route::middleware(['web', 'auth', 'admin.page', 'app.shell'])->group(function (): void {
    Route::get('/admin/services', ServiceCatalogIndexPageController::class)
        ->name('admin.services.index');

    Route::get('/admin/services/create', CreateServiceCatalogItemPageController::class)
        ->name('admin.services.create');

    Route::get('/admin/services/{serviceId}/edit', EditServiceCatalogItemPageController::class)
        ->name('admin.services.edit');
});
