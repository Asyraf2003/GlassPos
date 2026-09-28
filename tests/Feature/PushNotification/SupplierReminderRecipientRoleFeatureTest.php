<?php

declare(strict_types=1);

namespace Tests\Feature\PushNotification;

use App\Ports\Out\PushNotification\PushSubscriptionReaderPort;
use App\Ports\Out\PushNotification\PushNotificationSenderPort;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\Procurement\CurrentSupplierPayableFixture;
use Tests\Support\PushNotification\RecordingPushNotificationSender;
use Tests\TestCase;

final class SupplierReminderRecipientRoleFeatureTest extends TestCase
{
    use RefreshDatabase;
    use CurrentSupplierPayableFixture;

    public function test_supplier_reminder_only_sends_to_current_admin_active_subscriptions(): void
    {
        $admin = $this->loginAsAuthorizedAdmin();
        $this->seedPushSubscription((int) $admin->getAuthIdentifier(), 'admin-active');
        $this->seedPushSubscription((int) $admin->getAuthIdentifier(), 'admin-expired');
        DB::table('push_subscriptions')->where('endpoint', 'like', '%admin-expired')->update(['expired_at' => now()]);
        $cashier = $this->loginAsKasir();
        $this->seedPushSubscription((int) $cashier->getAuthIdentifier(), 'cashier');
        $this->oldInvoice();
        $sender = new RecordingPushNotificationSender();
        $this->app->instance(PushNotificationSenderPort::class, $sender);
        $this->artisan('push-notifications:send-supplier-payable-reminders', ['--today' => '2026-09-27'])
            ->expectsOutput('Push subscriptions: 1')->expectsOutput('Push sent: 1')->assertExitCode(0);
        self::assertSame([(int) $admin->getAuthIdentifier()], array_column($sender->subscriptions, 'userId'));
        self::assertStringContainsString('payment_status=outstanding&sort_by=due_date&sort_dir=asc', $sender->payloads[0]->url);
        self::assertCount(2, app(PushSubscriptionReaderPort::class)->findActive());
        self::assertSame((int) $admin->getAuthIdentifier(), app(PushSubscriptionReaderPort::class)->findActive(1, 'admin')[0]->userId);
        DB::table('actor_accesses')->where('actor_id', $admin->getAuthIdentifier())->update(['role' => 'kasir']);
        self::assertSame([], app(PushSubscriptionReaderPort::class)->findActive(500, 'admin'));
    }

    public function test_subscription_status_is_account_scoped_read_only_and_expiration_aware(): void
    {
        $admin = $this->loginAsAuthorizedAdmin();
        $endpoint = 'https://push.example.test/send/status-admin';
        $payload = ['endpoint' => $endpoint, 'keys' => ['p256dh' => 'public-key', 'auth' => 'auth-token']];
        foreach ([1, 2] as $attempt) {
            $this->postJson(route('push-notifications.subscriptions.store'), $payload)->assertOk();
        }
        $this->assertDatabaseCount('push_subscriptions', 1);
        $this->assertDatabaseHas('push_subscriptions', ['user_id' => $admin->getAuthIdentifier()]);
        $status = route('push-notifications.subscriptions.status');
        $this->postJson($status, ['endpoint' => $endpoint])->assertJsonPath('data.active', true);
        DB::table('push_subscriptions')->update(['expired_at' => now()]);
        $this->postJson($status, ['endpoint' => $endpoint])->assertJsonPath('data.active', false);
        $this->assertDatabaseMissing('push_subscriptions', ['expired_at' => null]);
        $this->postJson(route('push-notifications.subscriptions.store'), $payload)->assertOk();
        $this->loginAsKasir();
        $this->postJson($status, ['endpoint' => $endpoint])->assertJsonPath('data.active', false);
        $this->actingAs($admin)->deleteJson(route('push-notifications.subscriptions.destroy'), ['endpoint' => $endpoint])->assertOk();
        $this->postJson($status, ['endpoint' => $endpoint])->assertJsonPath('data.active', false);
        $this->assertDatabaseCount('push_subscriptions', 0);
    }
}
