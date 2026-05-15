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
use Illuminate\Support\ServiceProvider;

class IdentityAccessServiceProvider extends ServiceProvider
{
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
}
