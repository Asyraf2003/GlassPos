<?php

declare(strict_types=1);

namespace App\Application\PushNotification\UseCases;

use App\Ports\Out\PushNotification\PushSubscriptionReaderPort;

final readonly class GetPushSubscriptionStatusHandler
{
    public function __construct(private PushSubscriptionReaderPort $subscriptions) {}

    public function handle(int $userId, string $endpoint): bool
    {
        return $this->subscriptions->isActiveForUserEndpoint($userId, $endpoint);
    }
}
