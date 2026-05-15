<?php

declare(strict_types=1);

namespace App\Providers;

use App\Adapters\Out\PushNotification\DatabasePushSubscriptionReaderAdapter;
use App\Adapters\Out\PushNotification\DatabasePushSubscriptionWriterAdapter;
use App\Adapters\Out\PushNotification\WebPushNotificationSenderAdapter;
use App\Ports\Out\PushNotification\PushNotificationSenderPort;
use App\Ports\Out\PushNotification\PushSubscriptionReaderPort;
use App\Ports\Out\PushNotification\PushSubscriptionWriterPort;
use Illuminate\Support\ServiceProvider;

class PushNotificationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PushSubscriptionWriterPort::class, DatabasePushSubscriptionWriterAdapter::class);
        $this->app->singleton(PushSubscriptionReaderPort::class, DatabasePushSubscriptionReaderAdapter::class);
        $this->app->singleton(PushNotificationSenderPort::class, WebPushNotificationSenderAdapter::class);
    }
}
