<?php

declare(strict_types=1);

namespace App\Providers;

use App\Adapters\Out\MobileApi\DatabaseMobileApiTokenStoreAdapter;
use App\Adapters\Out\MobileApi\LaravelMobileApiUserIdentityAdapter;
use App\Application\MobileApi\Auth\Services\MobileApiActorResolver;
use App\Application\MobileApi\Auth\Services\MobileApiTokenHasher;
use App\Application\MobileApi\Auth\Services\MobileApiTokenIssuer;
use App\Application\MobileApi\Auth\Services\MobileApiTokenVerifier;
use App\Application\MobileApi\Auth\UseCases\LoginMobileApiUserHandler;
use App\Application\MobileApi\Auth\UseCases\LogoutMobileApiTokenHandler;
use App\Ports\Out\MobileApi\MobileApiCredentialVerifierPort;
use App\Ports\Out\MobileApi\MobileApiTokenStorePort;
use App\Ports\Out\MobileApi\MobileApiUserReaderPort;
use Illuminate\Support\ServiceProvider;

class MobileApiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(MobileApiCredentialVerifierPort::class, LaravelMobileApiUserIdentityAdapter::class);
        $this->app->singleton(MobileApiUserReaderPort::class, LaravelMobileApiUserIdentityAdapter::class);
        $this->app->singleton(MobileApiTokenStorePort::class, DatabaseMobileApiTokenStoreAdapter::class);
        $this->app->singleton(MobileApiTokenHasher::class);
        $this->app->singleton(MobileApiTokenIssuer::class);
        $this->app->singleton(MobileApiTokenVerifier::class);
        $this->app->singleton(MobileApiActorResolver::class);
        $this->app->singleton(LoginMobileApiUserHandler::class);
        $this->app->singleton(LogoutMobileApiTokenHandler::class);
    }
}
