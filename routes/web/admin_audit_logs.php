<?php

declare(strict_types=1);

use App\Adapters\In\Http\Controllers\Admin\AuditLog\AuditLogIndexPageController;
use App\Adapters\In\Http\Controllers\Admin\AuditLog\AuditLogShowPageController;
use App\Adapters\In\Http\Controllers\Admin\AuditLog\AuditLogTableDataController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'admin.page'])
    ->prefix('admin/audit-logs')
    ->name('admin.audit-logs.')
    ->group(function (): void {
        Route::get('/table', AuditLogTableDataController::class)->name('table');
    });

Route::middleware(['web', 'auth', 'admin.page', 'app.shell'])
    ->prefix('admin/audit-logs')
    ->name('admin.audit-logs.')
    ->group(function (): void {
        Route::get('/', AuditLogIndexPageController::class)->name('index');
        Route::get('/{source}/{auditId}', AuditLogShowPageController::class)
            ->whereIn('source', ['audit_logs', 'audit_events'])
            ->name('show');
    });
