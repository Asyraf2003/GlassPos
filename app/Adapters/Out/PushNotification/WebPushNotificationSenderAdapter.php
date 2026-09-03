<?php

declare(strict_types=1);

namespace App\Adapters\Out\PushNotification;

use App\Application\PushNotification\DTO\PushNotificationPayload;
use App\Application\PushNotification\DTO\PushNotificationSendResult;
use App\Application\PushNotification\DTO\StoredPushSubscription;
use App\Ports\Out\PushNotification\PushNotificationSenderPort;
use InvalidArgumentException;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use RuntimeException;

final class WebPushNotificationSenderAdapter implements PushNotificationSenderPort
{
    public function send(
        StoredPushSubscription $subscription,
        PushNotificationPayload $payload,
    ): PushNotificationSendResult {
        $webPush = new WebPush([
            'VAPID' => [
                'subject' => $this->requiredConfig('services.webpush.vapid_subject'),
                'publicKey' => $this->requiredConfig('services.webpush.vapid_public_key'),
                'privateKey' => $this->requiredConfig('services.webpush.vapid_private_key'),
            ],
        ]);

        $webSubscription = Subscription::create([
            'endpoint' => $subscription->endpoint,
            'keys' => [
                'p256dh' => $subscription->publicKey,
                'auth' => $subscription->authToken,
            ],
            'contentEncoding' => $subscription->contentEncoding,
        ]);

        $payloadData = $payload->toArray();
        $payloadData['icon'] = $this->resolvePublicAssetUrl($payloadData['icon']);
        $payloadData['badge'] = $this->resolvePublicAssetUrl($payloadData['badge']);

        $encodedPayload = json_encode($payloadData, JSON_THROW_ON_ERROR);

        if (! is_string($encodedPayload)) {
            throw new RuntimeException('Payload push notification gagal diencode.');
        }

        $report = $webPush->sendOneNotification($webSubscription, $encodedPayload);
        $response = $report->getResponse();
        $status = $response === null ? null : $response->getStatusCode();
        $responseReason = $response === null ? null : $response->getReasonPhrase();

        if ($report->isSuccess()) {
            return PushNotificationSendResult::success($status, $responseReason);
        }

        return PushNotificationSendResult::failed(
            subscriptionExpired: $report->isSubscriptionExpired(),
            responseStatus: $status,
            responseReason: $responseReason,
            reason: $report->getReason(),
        );
    }

    private function resolvePublicAssetUrl(string $value): string
    {
        if (! str_starts_with($value, '/assets/')) {
            return $value;
        }

        $assetBase = rtrim(trim((string) config('app.asset_url', '')), '/');

        if ($assetBase === '') {
            return $value;
        }

        return $assetBase.$value;
    }

    private function requiredConfig(string $key): string
    {
        $value = trim((string) config($key, ''));

        if ($value === '') {
            throw new InvalidArgumentException("Konfigurasi {$key} wajib diisi.");
        }

        return $value;
    }
}
