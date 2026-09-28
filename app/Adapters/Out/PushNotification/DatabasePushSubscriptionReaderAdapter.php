<?php

declare(strict_types=1);

namespace App\Adapters\Out\PushNotification;

use App\Application\PushNotification\DTO\StoredPushSubscription;
use App\Ports\Out\PushNotification\PushSubscriptionReaderPort;
use Illuminate\Support\Facades\DB;

final class DatabasePushSubscriptionReaderAdapter implements PushSubscriptionReaderPort
{
    public function isActiveForUserEndpoint(int $userId, string $endpoint): bool
    {
        return DB::table('push_subscriptions')->where('user_id', $userId)
            ->where('endpoint_hash', hash('sha256', $endpoint))->whereNull('expired_at')->exists();
    }

    public function findActive(int $limit = 500, ?string $role = null): array
    {
        $rows = DB::table('push_subscriptions')
            ->whereNull('expired_at')
            ->when($role !== null, fn ($query) => $query->whereExists(function ($access) use ($role): void {
                $access->selectRaw('1')->from('actor_accesses')
                    ->whereColumn('actor_accesses.actor_id', 'push_subscriptions.user_id')
                    ->where('actor_accesses.role', $role);
            }))
            ->orderByDesc('last_seen_at')
            ->orderBy('id')
            ->limit(max(1, $limit))
            ->get([
                'id',
                'user_id',
                'endpoint',
                'public_key',
                'auth_token',
                'content_encoding',
                'user_agent',
            ])
            ->all();

        return array_map(
            fn (object $row): StoredPushSubscription => new StoredPushSubscription(
                id: (int) $row->id,
                userId: (int) $row->user_id,
                endpoint: (string) $row->endpoint,
                publicKey: (string) $row->public_key,
                authToken: (string) $row->auth_token,
                contentEncoding: (string) $row->content_encoding,
                userAgent: $row->user_agent === null ? null : (string) $row->user_agent,
            ),
            $rows,
        );
    }
}
