<?php

declare(strict_types=1);

namespace App\Ports\Out\PushNotification;

use App\Application\PushNotification\DTO\StoredPushSubscription;

interface PushSubscriptionReaderPort
{
    /**
     * @return list<StoredPushSubscription>
     */
    public function findActive(int $limit = 500, ?string $role = null): array;
    public function isActiveForUserEndpoint(int $userId, string $endpoint): bool;
}
