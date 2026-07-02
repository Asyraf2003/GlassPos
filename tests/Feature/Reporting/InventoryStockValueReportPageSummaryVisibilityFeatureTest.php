<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class InventoryStockValueReportPageSummaryVisibilityFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_stock_value_page_summary_counts_deleted_and_orphan_movements_without_snapshot_pollution(): void
    {
        DB::table('products')->insert([
            [
                'id' => 'page-product-active',
                'kode_barang' => 'KB-PAGE-ACT',
                'nama_barang' => 'Page Active Part',
                'merek' => 'Federal',
                'ukuran' => 100,
                'harga_jual' => 15000,
                'deleted_at' => null,
            ],
            [
                'id' => 'page-product-deleted',
                'kode_barang' => 'KB-PAGE-DEL',
                'nama_barang' => 'Page Deleted Part',
                'merek' => 'Federal',
                'ukuran' => 100,
                'harga_jual' => 15000,
                'deleted_at' => '2030-01-15 10:00:00',
            ],
        ]);

        DB::table('inventory_movements')->insert([
            [
                'id' => 'page-movement-active-in',
                'product_id' => 'page-product-active',
                'movement_type' => 'stock_in',
                'source_type' => 'supplier_receipt_line',
                'source_id' => 'page-receipt-active-line',
                'tanggal_mutasi' => '2030-01-10',
                'qty_delta' => 5,
                'unit_cost_rupiah' => 1000,
                'total_cost_rupiah' => 5000,
            ],
            [
                'id' => 'page-movement-deleted-in',
                'product_id' => 'page-product-deleted',
                'movement_type' => 'stock_in',
                'source_type' => 'supplier_receipt_line',
                'source_id' => 'page-receipt-deleted-line',
                'tanggal_mutasi' => '2030-01-11',
                'qty_delta' => 2,
                'unit_cost_rupiah' => 2000,
                'total_cost_rupiah' => 4000,
            ],
        ]);

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            DB::table('inventory_movements')->insert([
                'id' => 'page-movement-orphan-in',
                'product_id' => 'page-product-orphan',
                'movement_type' => 'stock_in',
                'source_type' => 'supplier_receipt_line',
                'source_id' => 'page-receipt-orphan-line',
                'tanggal_mutasi' => '2030-01-12',
                'qty_delta' => 3,
                'unit_cost_rupiah' => 3000,
                'total_cost_rupiah' => 9000,
            ]);
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        DB::table('product_inventory')->insert([
            ['product_id' => 'page-product-active', 'qty_on_hand' => 5],
            ['product_id' => 'page-product-deleted', 'qty_on_hand' => 2],
        ]);

        DB::table('product_inventory_costing')->insert([
            ['product_id' => 'page-product-active', 'avg_cost_rupiah' => 1000, 'inventory_value_rupiah' => 5000],
            ['product_id' => 'page-product-deleted', 'avg_cost_rupiah' => 2000, 'inventory_value_rupiah' => 4000],
        ]);

        $response = $this->actingAs($this->user('admin'))->get(
            route('admin.reports.inventory_stock_value.index', [
                'period_mode' => 'monthly',
                'reference_date' => '2030-01-31',
            ])
        );

        $response->assertOk();
        $response->assertViewIs('admin.reporting.inventory_stock_value.index');

        $response->assertViewHas('summary', function (mixed $summary): bool {
            if (! is_array($summary)) {
                return false;
            }

            return ($summary['snapshot_product_rows'] ?? null) === 1
                && ($summary['movement_product_rows'] ?? null) === 3
                && ($summary['total_qty_on_hand'] ?? null) === 5
                && ($summary['total_inventory_value_rupiah'] ?? null) === 5000
                && ($summary['period_supply_in_qty'] ?? null) === 10
                && ($summary['period_net_qty_delta'] ?? null) === 10
                && ($summary['period_net_cost_delta_rupiah'] ?? null) === 18000;
        });

        $response->assertSeeText('Produk Tercatat di Stok');
        $response->assertSeeText('Produk Bergerak');
        $response->assertSeeText('Barang Masuk dari Supplier');
        $response->assertSeeText('Perubahan Modal Stok Bersih');
        $response->assertSeeText('Rp 5.000');
        $response->assertSeeText('Rp 18.000');
    }

    private function user(string $role): User
    {
        $user = User::query()->create([
            'name' => 'Test',
            'email' => $role . '-inventory-stock-value-page-summary@example.test',
            'password' => 'password123',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => $role,
        ]);

        return $user;
    }
}
