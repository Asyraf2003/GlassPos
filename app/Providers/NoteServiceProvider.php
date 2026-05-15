<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class NoteServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(NoteApplicationServiceProvider::class);
        $this->app->register(NotePersistenceServiceProvider::class);
        $this->app->register(NoteRevisionSettlementServiceProvider::class);
    }
}
