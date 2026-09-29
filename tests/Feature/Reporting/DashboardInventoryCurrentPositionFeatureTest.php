<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Application\Reporting\UseCases\GetAdminDashboardAnalyticsHandler;
use App\Application\Reporting\UseCases\GetAdminDashboardOverviewHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class DashboardInventoryCurrentPositionFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_current_stock_refreshes_across_cached_months_and_chart_uses_today(): void
    {
        $this->travelTo(now()->setDate(2026, 9, 29));
        DB::table('products')->insert(['id' => 'current-stock', 'nama_barang' => 'Current', 'merek' => 'Test', 'harga_jual' => 1]);
        DB::table('product_inventory')->insert(['product_id' => 'current-stock', 'qty_on_hand' => 2]);
        DB::table('product_inventory_costing')->insert(['product_id' => 'current-stock', 'avg_cost_rupiah' => 1, 'inventory_value_rupiah' => 2]);
        $overview = app(GetAdminDashboardOverviewHandler::class);
        self::assertSame(2, $overview->handle('2026-07')['stats']['total_qty_on_hand']);
        DB::table('product_inventory')->where('product_id', 'current-stock')->update(['qty_on_hand' => 1]);
        DB::table('product_inventory_costing')->where('product_id', 'current-stock')->update(['inventory_value_rupiah' => 1]);
        self::assertSame(1, $overview->handle('2026-07')['stats']['total_qty_on_hand']);
        self::assertSame(1, $overview->handle('2026-08')['position']['inventory_value_rupiah']);
        $chart = app(GetAdminDashboardAnalyticsHandler::class)->handle('2026-07')['charts']['stock_status_donut'];
        self::assertSame('2026-09-29', $chart['snapshot_date']);
    }
}
