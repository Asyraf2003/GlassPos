<?php

declare(strict_types=1);

namespace Tests\Feature\PushNotification;

use App\Ports\Out\PushNotification\PushNotificationSenderPort;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\Procurement\SupplierPayableReminderFixtures;
use Tests\Support\PushNotification\ExpiringPushNotificationSender;
use Tests\Support\PushNotification\RecordingPushNotificationSender;
use Tests\TestCase;

final class SendSupplierPayableReminderPushCommandFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SupplierPayableReminderFixtures;

    public function test_command_sends_supplier_payable_payload_and_marks_expired_subscriptions(): void
    {
        $user = $this->loginAsAuthorizedAdmin();
        $sender = new ExpiringPushNotificationSender('browser-expired');
        $this->app->instance(PushNotificationSenderPort::class, $sender);

        $invoiceId = $this->seedSupplierPayableInvoice('push-due', '2026-04-30', 200000, 'CV Push Due');
        $this->seedSupplierPayment('payment-push-due', $invoiceId, 50000);
        $this->seedPushSubscription((int) $user->getAuthIdentifier(), 'browser-active');
        $this->seedPushSubscription((int) $user->getAuthIdentifier(), 'browser-expired');

        $this->artisan('push-notifications:send-supplier-payable-reminders', ['--today' => '2026-04-25'])
            ->expectsOutput('Supplier payable reminders: 1')
            ->expectsOutput('Push subscriptions: 2')
            ->expectsOutput('Push sent: 1')
            ->expectsOutput('Push expired: 1')
            ->expectsOutput('Push failed: 0')
            ->assertExitCode(0);

        self::assertCount(2, $sender->payloads);
        self::assertSame('Reminder Jatuh Tempo Hutang Pemasok', $sender->payloads[0]->title);
        self::assertStringContainsString('Ada 1 faktur pemasok jatuh tempo/perlu dicek.', $sender->payloads[0]->body);
        self::assertStringContainsString('Rp 150.000', $sender->payloads[0]->body);
        self::assertSame(route('admin.procurement.supplier-invoices.index', ['payment_status' => 'outstanding', 'sort_by' => 'due_date', 'sort_dir' => 'asc'], false), $sender->payloads[0]->url);
        self::assertSame('supplier-payable-reminder-2026-04-25', $sender->payloads[0]->tag);

        $expiredEndpoint = 'https://push.example.test/send/browser-expired';
        $expiredRow = DB::table('push_subscriptions')
            ->where('endpoint_hash', hash('sha256', $expiredEndpoint))
            ->first(['expired_at', 'last_failure_status', 'last_failure_reason']);

        self::assertNotNull($expiredRow);
        self::assertNotNull($expiredRow->expired_at);
        self::assertSame(410, (int) $expiredRow->last_failure_status);
        self::assertStringContainsString('unsubscribed or expired', (string) $expiredRow->last_failure_reason);

        $recordingSender = new RecordingPushNotificationSender();
        $this->app->instance(PushNotificationSenderPort::class, $recordingSender);

        $this->artisan('push-notifications:send-supplier-payable-reminders', ['--today' => '2026-04-25'])
            ->expectsOutput('Supplier payable reminders: 1')
            ->expectsOutput('Push subscriptions: 1')
            ->expectsOutput('Push sent: 1')
            ->expectsOutput('Push expired: 0')
            ->expectsOutput('Push failed: 0')
            ->assertExitCode(0);

        self::assertCount(1, $recordingSender->subscriptions);
        self::assertStringContainsString('browser-active', $recordingSender->subscriptions[0]->endpoint);
    }

    public function test_command_skips_push_when_no_supplier_payable_reminder_exists(): void
    {
        $sender = new RecordingPushNotificationSender();
        $this->app->instance(PushNotificationSenderPort::class, $sender);

        $this->artisan('push-notifications:send-supplier-payable-reminders', ['--today' => '2026-04-25'])
            ->expectsOutput('Supplier payable reminders: 0')
            ->expectsOutput('Push subscriptions: 0')
            ->expectsOutput('Push sent: 0')
            ->expectsOutput('Push expired: 0')
            ->expectsOutput('Push failed: 0')
            ->assertExitCode(0);

        self::assertSame([], $sender->payloads);
    }
}
