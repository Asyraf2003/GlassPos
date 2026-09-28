<?php

declare(strict_types=1);

use App\Adapters\In\Http\Controllers\PushNotification\DeletePushSubscriptionController;
use App\Adapters\In\Http\Controllers\PushNotification\StorePushSubscriptionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])
    ->prefix('push-notifications')
    ->name('push-notifications.')
    ->group(function (): void {
        Route::post('/subscriptions', StorePushSubscriptionController::class)
            ->name('subscriptions.store');

        Route::post('/subscriptions/status', \App\Adapters\In\Http\Controllers\PushNotification\PushSubscriptionStatusController::class)
            ->name('subscriptions.status');

        Route::delete('/subscriptions', DeletePushSubscriptionController::class)
            ->name('subscriptions.destroy');
    });
