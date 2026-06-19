<?php

use App\Adapters\In\Http\Middleware\IdentityAccess\EnsureAdminPageAccess;
use App\Adapters\In\Http\Middleware\IdentityAccess\EnsureCashierAreaAccess;
use App\Adapters\In\Http\Middleware\IdentityAccess\EnsureTransactionEntryAllowed;
use App\Adapters\In\Http\Middleware\IdentityAccess\ShareAppShellData;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
                | Request::HEADER_X_FORWARDED_PREFIX,
        );

        $middleware->alias([
            'transaction.entry' => EnsureTransactionEntryAllowed::class,
            'admin.page' => EnsureAdminPageAccess::class,
            'cashier.area' => EnsureCashierAreaAccess::class,
            'app.shell' => ShareAppShellData::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $e, $request) {
            if ($e instanceof AuthenticationException || $e instanceof ValidationException) {
                return null;
            }

            $status = match (true) {
                $e instanceof TokenMismatchException => 419,
                $e instanceof HttpExceptionInterface => $e->getStatusCode(),
                default => 500,
            };

            if ($request->expectsJson()) {
                if ($status === 419) {
                    return response()->json([
                        'message' => 'Sesi Anda sudah berakhir. Silakan muat ulang halaman.',
                        'status' => 419,
                    ], 419);
                }

                if ($e instanceof HttpExceptionInterface && $status >= 400 && $status < 500) {
                    return null;
                }

                return response()->json([
                    'message' => match ($status) {
                        503 => 'Layanan sedang tidak tersedia sementara.',
                        default => 'Terjadi gangguan pada sistem.',
                    },
                    'status' => $status,
                ], $status);
            }

            if (!in_array($status, [403, 404, 419, 429, 500, 503], true)) {
                $status = 500;
            }

            return response()->view("errors.$status", [
                'statusCode' => $status,
            ], $status);
        });
    })->create();
