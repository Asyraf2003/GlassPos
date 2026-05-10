<?php

declare(strict_types=1);

use App\Adapters\In\Http\Controllers\IdentityAccess\DisableAdminTransactionCapabilityController;
use App\Adapters\In\Http\Controllers\IdentityAccess\EnableAdminTransactionCapabilityController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'admin.page'])->group(function (): void {
    Route::post('/identity-access/admin-transaction-capability/enable', EnableAdminTransactionCapabilityController::class)
        ->name('identity-access.admin-transaction-capability.enable');

    Route::post('/identity-access/admin-transaction-capability/disable', DisableAdminTransactionCapabilityController::class)
        ->name('identity-access.admin-transaction-capability.disable');
});