<?php

declare(strict_types=1);

use App\Adapters\In\Http\Controllers\Admin\ServiceProductTemplate\CreateServiceProductTemplatePageController;
use App\Adapters\In\Http\Controllers\Admin\ServiceProductTemplate\DeactivateServiceProductTemplateController;
use App\Adapters\In\Http\Controllers\Admin\ServiceProductTemplate\EditServiceProductTemplatePageController;
use App\Adapters\In\Http\Controllers\Admin\ServiceProductTemplate\ReactivateServiceProductTemplateController;
use App\Adapters\In\Http\Controllers\Admin\ServiceProductTemplate\ServiceProductTemplateIndexPageController;
use App\Adapters\In\Http\Controllers\Admin\ServiceProductTemplate\ServiceProductTemplateTableDataController;
use App\Adapters\In\Http\Controllers\Admin\ServiceProductTemplate\ShowServiceProductTemplatePageController;
use App\Adapters\In\Http\Controllers\Admin\ServiceProductTemplate\StoreServiceProductTemplateController;
use App\Adapters\In\Http\Controllers\Admin\ServiceProductTemplate\UpdateServiceProductTemplateController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'admin.page'])->group(function (): void {
    Route::get('/admin/service-product-templates/table', ServiceProductTemplateTableDataController::class)
        ->name('admin.service-product-templates.table');

    Route::post('/admin/service-product-templates', StoreServiceProductTemplateController::class)
        ->name('admin.service-product-templates.store');

    Route::put('/admin/service-product-templates/{templateId}', UpdateServiceProductTemplateController::class)
        ->name('admin.service-product-templates.update');

    Route::patch('/admin/service-product-templates/{templateId}/deactivate', DeactivateServiceProductTemplateController::class)
        ->name('admin.service-product-templates.deactivate');

    Route::patch('/admin/service-product-templates/{templateId}/reactivate', ReactivateServiceProductTemplateController::class)
        ->name('admin.service-product-templates.reactivate');
});

Route::middleware(['web', 'auth', 'admin.page', 'app.shell'])->group(function (): void {
    Route::get('/admin/service-product-templates', ServiceProductTemplateIndexPageController::class)
        ->name('admin.service-product-templates.index');

    Route::get('/admin/service-product-templates/create', CreateServiceProductTemplatePageController::class)
        ->name('admin.service-product-templates.create');

    Route::get('/admin/service-product-templates/{templateId}', ShowServiceProductTemplatePageController::class)
        ->name('admin.service-product-templates.show');

    Route::get('/admin/service-product-templates/{templateId}/edit', EditServiceProductTemplatePageController::class)
        ->name('admin.service-product-templates.edit');
});
