<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Application\Reporting\UseCases\GetAdminDashboardOverviewHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class DashboardCurrentReceivableFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_old_receivable_is_current_position_without_becoming_new_month_revenue(): void
    {
        DB::table('notes')->insert(['id' => 'old', 'customer_name' => 'A', 'transaction_date' => '2026-07-01', 'total_rupiah' => 1000001]);
        $handler = app(GetAdminDashboardOverviewHandler::class);
        $payload = $handler->handle('2026-09');
        self::assertSame(1000001, $payload['position']['transaction_outstanding_rupiah']);
        self::assertSame(0, $payload['hero']['monthly_gross_transaction_rupiah']);
        self::assertSame(0, $payload['finance']['monthly_cash_in_rupiah']);
        DB::table('customer_payments')->insert(['id' => 'payment', 'amount_rupiah' => 1000000, 'paid_at' => '2026-09-01']);
        DB::table('payment_allocations')->insert(['id' => 'allocation', 'customer_payment_id' => 'payment', 'note_id' => 'old', 'amount_rupiah' => 1000000]);
        self::assertSame(1, $handler->handle('2026-09')['position']['transaction_outstanding_rupiah']);
        self::assertSame(1, $handler->handle('2026-08')['position']['transaction_outstanding_rupiah']);
    }
}
