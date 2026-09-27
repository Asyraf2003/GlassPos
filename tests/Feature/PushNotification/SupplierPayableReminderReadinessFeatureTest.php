<?php

declare(strict_types=1);

namespace Tests\Feature\PushNotification;

use App\Application\PushNotification\Services\SupplierPayableReminderPushPayloadFactory;
use App\Ports\Out\Procurement\SupplierPayableReminderReaderPort;
use App\Ports\Out\PushNotification\PushNotificationSenderPort;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\Procurement\CurrentSupplierPayableFixture;
use Tests\Support\PushNotification\RecordingPushNotificationSender;
use Tests\TestCase;

final class SupplierPayableReminderReadinessFeatureTest extends TestCase
{
    use RefreshDatabase;
    use CurrentSupplierPayableFixture;

    public function test_push_url_opens_operational_outstanding_endpoint_without_date_cohort(): void
    {
        $this->loginAsAuthorizedAdmin();
        $id = $this->oldInvoice();
        $this->seedSupplierPayment('partial-1', $id, 1000000);
        $this->seedSupplierPayment('partial-2', $id, 1000000);
        $future = $this->seedSupplierPayableInvoice('future', '2026-12-01', 1000000, 'Future Supplier');
        foreach ([$id, $future] as $invoice) {
            $this->syncSupplierInvoiceProjectionForTest($invoice);
        }
        $reminders = app(SupplierPayableReminderReaderPort::class)->findDueReminders('2026-09-27');
        self::assertCount(1, $reminders);
        self::assertSame(8000000, $reminders[0]->outstandingRupiah);
        $payload = app(SupplierPayableReminderPushPayloadFactory::class)->make('2026-09-27', $reminders);
        parse_str((string) parse_url($payload->url, PHP_URL_QUERY), $query);
        self::assertSame(['payment_status' => 'outstanding', 'sort_by' => 'due_date', 'sort_dir' => 'asc'], $query);
        $this->get($payload->url)->assertOk()->assertViewIs('admin.procurement.supplier_invoices.index')
            ->assertSee('Masih Punya Tagihan');
        $this->getJson(route('admin.procurement.supplier-invoices.table', $query))->assertOk()
            ->assertSeeInOrder(['NF-august', 'NF-future'])->assertSee('8000000');
    }

    public function test_command_has_no_session_dependency_finance_mutation_or_duplicate_within_invocation(): void
    {
        $user = $this->loginAsAuthorizedAdmin();
        $this->seedPushSubscription((int) $user->getAuthIdentifier(), 'single');
        $this->app['auth']->forgetGuards();
        self::assertNull(auth()->guard()->user());
        $this->travelTo(\Carbon\CarbonImmutable::parse('2026-09-27'));
        $id = $this->oldInvoice();
        $this->seedSupplierPayment('one', $id, 1000000);
        $this->seedSupplierPayment('two', $id, 1000000);
        $sender = new RecordingPushNotificationSender();
        $this->app->instance(PushNotificationSenderPort::class, $sender);
        $before = $this->financeSnapshot();
        foreach ([1, 2] as $invocation) {
            $options = $invocation === 1 ? ['--today' => '2026-09-27'] : [];
            $this->artisan('push-notifications:send-supplier-payable-reminders', $options + ['--no-interaction' => true])
                ->expectsOutput('Supplier payable reminders: 1')->expectsOutput('Push sent: 1')->assertExitCode(0);
            self::assertCount($invocation, $sender->payloads);
        }
        self::assertSame($before, $this->financeSnapshot());
        self::assertSame($sender->payloads[0]->tag, $sender->payloads[1]->tag);
    }

    public function test_command_rejects_invalid_date_and_reports_delivery_failure(): void
    {
        $this->artisan('push-notifications:send-supplier-payable-reminders', ['--today' => '2026-02-30'])
            ->assertExitCode(1);
        $user = $this->loginAsAuthorizedAdmin();
        $this->seedPushSubscription((int) $user->getAuthIdentifier(), 'failed');
        $this->oldInvoice();
        $sender = \Mockery::mock(PushNotificationSenderPort::class);
        $sender->shouldReceive('send')->once()->andReturn(
            \App\Application\PushNotification\DTO\PushNotificationSendResult::failed(false, 503, 'Unavailable', 'Delivery failed')
        );
        $this->app->instance(PushNotificationSenderPort::class, $sender);
        $this->artisan('push-notifications:send-supplier-payable-reminders', ['--today' => '2026-09-27'])
            ->expectsOutput('Push failed: 1')->assertExitCode(1);
    }

    public function test_existing_invoice_and_subscription_limits_truncate_one_invocation(): void
    {
        $user = $this->loginAsAuthorizedAdmin();
        for ($i = 0; $i < 101; $i++) {
            $this->oldInvoice('limit-'.$i, 1);
        }
        for ($i = 0; $i < 501; $i++) {
            $this->seedPushSubscription((int) $user->getAuthIdentifier(), 'limit-'.$i);
        }
        $sender = new RecordingPushNotificationSender();
        $this->app->instance(PushNotificationSenderPort::class, $sender);
        $this->artisan('push-notifications:send-supplier-payable-reminders', ['--today' => '2026-09-27'])
            ->expectsOutput('Supplier payable reminders: 100')->expectsOutput('Push subscriptions: 500')
            ->expectsOutput('Push sent: 500')->assertExitCode(0);
        self::assertCount(500, array_unique(array_column($sender->subscriptions, 'endpoint')));
        self::assertCount(101, app(SupplierPayableReminderReaderPort::class)->findDueReminders('2026-09-27', 101));
    }

    private function financeSnapshot(): string
    {
        return json_encode([
            DB::table('supplier_invoices')->orderBy('id')->get()->all(),
            DB::table('supplier_payments')->orderBy('id')->get()->all(),
            DB::table('supplier_payment_reversals')->orderBy('id')->get()->all(),
        ], JSON_THROW_ON_ERROR);
    }
}
