<?php

declare(strict_types=1);

namespace App\Application\PushNotification\UseCases;

use App\Application\Procurement\UseCases\GetSupplierPayableRemindersHandler;
use App\Application\PushNotification\DTO\SupplierPayableReminderPushSendSummary;
use App\Application\PushNotification\Services\SupplierPayableReminderPushPayloadFactory;
use App\Ports\Out\PushNotification\PushNotificationSenderPort;
use App\Ports\Out\PushNotification\PushSubscriptionReaderPort;
use App\Ports\Out\PushNotification\PushSubscriptionWriterPort;

final class SendSupplierPayableReminderPushHandler
{
    public function __construct(
        private readonly GetSupplierPayableRemindersHandler $reminders,
        private readonly PushSubscriptionReaderPort $subscriptions,
        private readonly PushSubscriptionWriterPort $subscriptionWriter,
        private readonly PushNotificationSenderPort $sender,
        private readonly SupplierPayableReminderPushPayloadFactory $payloads,
    ) {
    }

    public function handle(
        string $today,
        int $invoiceLimit = 100,
        int $subscriptionLimit = 500,
    ): SupplierPayableReminderPushSendSummary {
        $reminders = $this->reminders->handle($today, $invoiceLimit);

        if ($reminders === []) {
            return new SupplierPayableReminderPushSendSummary(0, 0, 0, 0, 0);
        }

        $subscriptions = $this->subscriptions->findActive($subscriptionLimit, 'admin');
        $payload = $this->payloads->make($today, $reminders);
        $sent = 0;
        $failed = 0;
        $expired = 0;

        foreach ($subscriptions as $subscription) {
            $result = $this->sender->send($subscription, $payload);

            if ($result->success) {
                $sent++;

                continue;
            }

            if ($result->subscriptionExpired) {
                $expired++;
                $this->subscriptionWriter->markExpiredByEndpoint(
                    $subscription->endpoint,
                    $result->responseStatus,
                    $result->reason,
                );

                continue;
            }

            $failed++;
        }

        return new SupplierPayableReminderPushSendSummary(
            supplierPayableReminderCount: count($reminders),
            subscriptionCount: count($subscriptions),
            sentCount: $sent,
            failedCount: $failed,
            expiredCount: $expired,
        );
    }
}
