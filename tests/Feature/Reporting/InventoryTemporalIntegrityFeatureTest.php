<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser;
use App\Application\Reporting\Exports\InventoryStockValueReportExcelWorkbookBuilder;
use App\Application\Reporting\Exports\InventoryStockValueReportPdfViewDataBuilder;
use App\Application\Reporting\UseCases\GetInventoryStockValueReportDatasetHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class InventoryTemporalIntegrityFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_period_carries_inventory_and_future_movement_does_not_change_position(): void
    {
        DB::table('products')->insert(['id' => 'p', 'kode_barang' => 'TEMP', 'nama_barang' => 'Part',
            'merek' => 'Test', 'harga_jual' => 100]);
        DB::table('product_inventory')->insert(['product_id' => 'p', 'qty_on_hand' => 2]);
        DB::table('product_inventory_costing')->insert(['product_id' => 'p', 'avg_cost_rupiah' => 100, 'inventory_value_rupiah' => 201]);
        foreach ([['july', '2026-07-31', 3, 301], ['october', '2026-10-01', -1, -100]] as [$id, $date, $qty, $value]) {
            DB::table('inventory_movements')->insert(['id' => $id, 'product_id' => 'p',
                'movement_type' => $qty > 0 ? 'stock_in' : 'stock_out', 'source_type' => 'supplier_receipt_line',
                'source_id' => $id, 'tanggal_mutasi' => $date, 'qty_delta' => $qty,
                'unit_cost_rupiah' => 100, 'total_cost_rupiah' => $value]);
        }
        $handler = app(GetInventoryStockValueReportDatasetHandler::class);
        $data = $handler->handle('2026-09-01', '2026-09-30')->data();
        self::assertSame(3, $data['summary']['total_qty_on_hand']);
        self::assertSame(301, $data['summary']['total_inventory_value_rupiah']);
        self::assertSame(1, $data['summary']['total_rounding_residual_rupiah']);
        self::assertSame(0, $data['summary']['period_net_qty_delta']);
        self::assertSame($data['summary'], $handler->handleSummaryOnly('2026-09-01', '2026-09-30')->data()['summary']);
        $filters = ['date_from' => '2026-09-01', 'date_to' => '2026-09-30', 'period_mode' => 'custom'];
        $pdf = app(InventoryStockValueReportPdfViewDataBuilder::class)->build($data, $filters);
        self::assertSame('Rp 301', $pdf['detailTables'][0]['rows'][0]['current_inventory_value_rupiah']);
        self::assertStringContainsString('Rp 301', view('admin.reporting.inventory_stock_value.export_pdf', $pdf)->render());
        $excel = app(InventoryStockValueReportExcelWorkbookBuilder::class)->build($data, $filters);
        self::assertSame(301, $excel->getSheetByName('Snapshot Stok')->getCell('H2')->getValue());
        self::assertSame('n', $excel->getSheetByName('Snapshot Stok')->getCell('H2')->getDataType());
        $admin = EloquentUser::factory()->create();
        DB::table('actor_accesses')->insert(['actor_id' => (string) $admin->getAuthIdentifier(), 'role' => 'admin']);
        $screen = $this->actingAs($admin)->get(route('admin.reports.inventory_stock_value.index', $filters));
        $screen->assertOk()->assertSee('Rp 301');

        $screenTables = $screen->viewData('detailTables');
        foreach ($pdf['detailTables'] as $index => $table) {
            self::assertSame($table['title'], $screenTables[$index]['title']);
            self::assertSame($table['columns'], $screenTables[$index]['columns']);
            self::assertSame($table['rows'], $screenTables[$index]['rows']->items());
        }

        self::assertSame(201, $handler->handle('2026-10-01', '2026-10-31')->data()['summary']['total_inventory_value_rupiah']);
    }
}
