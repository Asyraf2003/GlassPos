<?php

declare(strict_types=1);

namespace App\Providers;

use App\Application\IdentityAccess\Request\IdentityAccessRequestStore;
use Illuminate\Support\ServiceProvider;

class IdentityAccessServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(
            IdentityAccessRequestStore::class,
            fn (): IdentityAccessRequestStore => new IdentityAccessRequestStore()
        );
    }
}
