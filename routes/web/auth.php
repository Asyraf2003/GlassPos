<?php

declare(strict_types=1);

use App\Adapters\In\Http\Controllers\Auth\AuthenticateController;
use App\Adapters\In\Http\Controllers\Auth\LoginPageController;
use App\Adapters\In\Http\Controllers\Auth\LogoutController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'app.shell'])->group(function (): void {
    Route::get('/login', LoginPageController::class)->name('login');
    Route::post('/login', AuthenticateController::class)
        ->middleware('throttle:web-login')
        ->name('login.attempt');
    Route::post('/logout', LogoutController::class)
        ->middleware('auth')
        ->name('logout');
});
