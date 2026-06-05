<?php

declare(strict_types=1);

namespace App\Providers;

use App\Adapters\Out\IdentityAccess\CachedActorAccessReaderAdapter;
use App\Adapters\Out\IdentityAccess\CachedAdminCashierAreaAccessStateAdapter;
use App\Adapters\Out\IdentityAccess\CachedAdminTransactionCapabilityStateAdapter;
use App\Application\IdentityAccess\Request\IdentityAccessRequestStore;
use App\Ports\Out\IdentityAccess\ActorAccessReaderPort;
use App\Ports\Out\IdentityAccess\AdminCashierAreaAccessStatePort;
use App\Ports\Out\IdentityAccess\AdminTransactionCapabilityStatePort;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class IdentityAccessServiceProvider extends ServiceProvider
{
    private const LOGIN_MAX_ATTEMPTS_PER_MINUTE = 5;

    public function register(): void
    {
        $this->app->scoped(
            IdentityAccessRequestStore::class,
            fn (): IdentityAccessRequestStore => new IdentityAccessRequestStore()
        );

        $this->app->scoped(ActorAccessReaderPort::class, CachedActorAccessReaderAdapter::class);
        $this->app->scoped(AdminTransactionCapabilityStatePort::class, CachedAdminTransactionCapabilityStateAdapter::class);
        $this->app->scoped(AdminCashierAreaAccessStatePort::class, CachedAdminCashierAreaAccessStateAdapter::class);
    }

    public function boot(): void
    {
        RateLimiter::for(
            'web-login',
            fn (Request $request): Limit => Limit::perMinute(self::LOGIN_MAX_ATTEMPTS_PER_MINUTE)
                ->by($this->loginRateLimiterKey($request))
        );

        RateLimiter::for(
            'mobile-login',
            fn (Request $request): Limit => Limit::perMinute(self::LOGIN_MAX_ATTEMPTS_PER_MINUTE)
                ->by($this->loginRateLimiterKey($request))
        );
    }

    private function loginRateLimiterKey(Request $request): string
    {
        $email = mb_strtolower(trim((string) $request->input('email')));

        if ($email === '') {
            $email = 'missing-email';
        }

        return $email.'|'.($request->ip() ?? 'unknown-ip');
    }
}
