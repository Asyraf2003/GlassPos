<?php

declare(strict_types=1);

namespace Tests\Feature\PushNotification;

use App\Application\PushNotification\DTO\PushNotificationPayload;
use App\Application\PushNotification\DTO\PushNotificationSendResult;
use App\Application\PushNotification\DTO\StoredPushSubscription;
use App\Ports\Out\PushNotification\PushNotificationSenderPort;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class SendDueNoteReminderPushCommandFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_sends_due_note_reminder_payload_to_active_subscriptions(): void
    {
        $user = $this->loginAsAuthorizedAdmin();
        $sender = new RecordingPushNotificationSender();
        $this->app->instance(PushNotificationSenderPort::class, $sender);

        $this->seedDueReminderNote();
        $this->seedPushSubscription((int) $user->getAuthIdentifier(), 'browser-1');
        $cashier = $this->loginAsKasir();
        $this->seedPushSubscription((int) $cashier->getAuthIdentifier(), 'browser-2');

        $this->artisan('push-notifications:send-due-note-reminders', [
            '--today' => '2026-04-25',
        ])
            ->expectsOutput('Due reminder notes: 1')
            ->expectsOutput('Push subscriptions: 2')
            ->expectsOutput('Push sent: 2')
            ->expectsOutput('Push expired: 0')
            ->expectsOutput('Push failed: 0')
            ->assertExitCode(0);

        $this->assertCount(2, $sender->payloads);
        $this->assertEqualsCanonicalizing(
            [(int) $user->getAuthIdentifier(), (int) $cashier->getAuthIdentifier()],
            array_column($sender->subscriptions, 'userId'),
        );
        $this->assertSame('Reminder Jatuh Tempo Nota', $sender->payloads[0]->title);
        $this->assertStringContainsString('Ada 1 nota jatuh tempo/perlu dicek.', $sender->payloads[0]->body);
        $this->assertStringContainsString('Rp 150.000', $sender->payloads[0]->body);
        $this->assertSame('/admin/notes', $sender->payloads[0]->url);
        $this->assertSame('due-note-reminder-2026-04-25', $sender->payloads[0]->tag);
    }

    public function test_command_marks_expired_subscriptions_and_excludes_them_next_run(): void
    {
        $user = $this->loginAsAuthorizedAdmin();
        $expiringSender = new ExpiringPushNotificationSender('browser-expired');
        $this->app->instance(PushNotificationSenderPort::class, $expiringSender);

        $this->seedDueReminderNote();
        $this->seedPushSubscription((int) $user->getAuthIdentifier(), 'browser-active');
        $this->seedPushSubscription((int) $user->getAuthIdentifier(), 'browser-expired');

        $this->artisan('push-notifications:send-due-note-reminders', [
            '--today' => '2026-04-25',
        ])
            ->expectsOutput('Due reminder notes: 1')
            ->expectsOutput('Push subscriptions: 2')
            ->expectsOutput('Push sent: 1')
            ->expectsOutput('Push expired: 1')
            ->expectsOutput('Push failed: 0')
            ->assertExitCode(0);

        $expiredEndpoint = 'https://push.example.test/send/browser-expired';
        $expiredRow = DB::table('push_subscriptions')
            ->where('endpoint_hash', hash('sha256', $expiredEndpoint))
            ->first([
                'expired_at',
                'last_failure_status',
                'last_failure_reason',
            ]);

        $this->assertNotNull($expiredRow);
        $this->assertNotNull($expiredRow->expired_at);
        $this->assertSame(410, (int) $expiredRow->last_failure_status);
        $this->assertStringContainsString(
            'unsubscribed or expired',
            (string) $expiredRow->last_failure_reason,
        );

        $recordingSender = new RecordingPushNotificationSender();
        $this->app->instance(PushNotificationSenderPort::class, $recordingSender);

        $this->artisan('push-notifications:send-due-note-reminders', [
            '--today' => '2026-04-25',
        ])
            ->expectsOutput('Due reminder notes: 1')
            ->expectsOutput('Push subscriptions: 1')
            ->expectsOutput('Push sent: 1')
            ->expectsOutput('Push expired: 0')
            ->expectsOutput('Push failed: 0')
            ->assertExitCode(0);

        $this->assertCount(1, $recordingSender->subscriptions);
        $this->assertStringContainsString('browser-active', $recordingSender->subscriptions[0]->endpoint);
    }

    public function test_command_skips_push_when_no_due_reminder_note_exists(): void
    {
        $sender = new RecordingPushNotificationSender();
        $this->app->instance(PushNotificationSenderPort::class, $sender);

        $this->artisan('push-notifications:send-due-note-reminders', [
            '--today' => '2026-04-25',
        ])
            ->expectsOutput('Due reminder notes: 0')
            ->expectsOutput('Push subscriptions: 0')
            ->expectsOutput('Push sent: 0')
            ->expectsOutput('Push expired: 0')
            ->expectsOutput('Push failed: 0')
            ->assertExitCode(0);

        $this->assertSame([], $sender->payloads);
    }

    private function seedDueReminderNote(): void
    {
        DB::table('notes')->insert([
            'id' => 'note-push-due-1',
            'customer_name' => 'Budi Push',
            'customer_phone' => '081234567890',
            'transaction_date' => '2026-03-30',
            'due_date' => '2026-04-30',
            'total_rupiah' => 200000,
            'note_state' => 'open',
        ]);

        DB::table('note_history_projection')->insert([
            'note_id' => 'note-push-due-1',
            'transaction_date' => '2026-03-30',
            'note_state' => 'open',
            'customer_name' => 'Budi Push',
            'customer_name_normalized' => 'budi push',
            'customer_phone' => '081234567890',
            'total_rupiah' => 200000,
            'allocated_rupiah' => 50000,
            'refunded_rupiah' => 0,
            'net_paid_rupiah' => 50000,
            'outstanding_rupiah' => 150000,
            'line_open_count' => 1,
            'line_close_count' => 0,
            'line_refund_count' => 0,
            'has_open_lines' => true,
            'has_close_lines' => false,
            'has_refund_lines' => false,
            'projected_at' => '2026-04-25 10:00:00',
        ]);
    }

    private function seedPushSubscription(int $userId, string $browser): void
    {
        $endpoint = 'https://push.example.test/send/'.$browser;

        DB::table('push_subscriptions')->insert([
            'user_id' => $userId,
            'endpoint' => $endpoint,
            'endpoint_hash' => hash('sha256', $endpoint),
            'public_key' => 'public-key-'.$browser,
            'auth_token' => 'auth-token-'.$browser,
            'content_encoding' => 'aes128gcm',
            'user_agent' => 'Feature Test '.$browser,
            'last_seen_at' => '2026-04-25 10:00:00',
            'expired_at' => null,
            'last_failure_status' => null,
            'last_failure_reason' => null,
            'created_at' => '2026-04-25 10:00:00',
            'updated_at' => '2026-04-25 10:00:00',
        ]);
    }
}

final class RecordingPushNotificationSender implements PushNotificationSenderPort
{
    /** @var list<StoredPushSubscription> */
    public array $subscriptions = [];

    /** @var list<PushNotificationPayload> */
    public array $payloads = [];

    public function send(
        StoredPushSubscription $subscription,
        PushNotificationPayload $payload,
    ): PushNotificationSendResult {
        $this->subscriptions[] = $subscription;
        $this->payloads[] = $payload;

        return PushNotificationSendResult::success(201, 'Created');
    }
}

final class ExpiringPushNotificationSender implements PushNotificationSenderPort
{
    public function __construct(
        private readonly string $expiredEndpointNeedle,
    ) {
    }

    public function send(
        StoredPushSubscription $subscription,
        PushNotificationPayload $payload,
    ): PushNotificationSendResult {
        if (str_contains($subscription->endpoint, $this->expiredEndpointNeedle)) {
            return PushNotificationSendResult::failed(
                subscriptionExpired: true,
                responseStatus: 410,
                responseReason: 'Gone',
                reason: 'push subscription has unsubscribed or expired.',
            );
        }

        return PushNotificationSendResult::success(201, 'Created');
    }
}
