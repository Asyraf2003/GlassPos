<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Core\Note\WorkItem\ServiceDetail;
use App\Core\Note\WorkItem\WorkItem;
use App\Core\Payment\PaymentComponentAllocation\PaymentComponentType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\Support\SeedsMinimalNotePaymentFixture;
use Tests\TestCase;

final class ServicePackageProfitBreakdownUiScenarioMatrixFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalNotePaymentFixture;

    public function test_page_displays_historical_multi_sparepart_package_after_debt_late_payment_refund_and_current_price_changes(): void
    {
        $this->seedExtremePackageScenario('matrix-main', 'Hutang lalu lunas', '2030-02-10');

        $this->loginAsAuthorizedAdmin();

        $response = $this->get(route('admin.reports.service_package_profit_breakdown.index', [
            'period_mode' => 'monthly',
            'reference_date' => '2030-02-28',
        ]));

        $response->assertOk();
        $response->assertSee('Laba Paket Service');
        $response->assertDontSee('Hutang lalu lunas');
        $response->assertSee('Refund Komponen Produk');
        $response->assertSee('Refund Komponen Service');

        $response->assertSee('Rp 450.000');
        $response->assertSee('Rp 250.000');
        $response->assertSee('Rp 105.000');
        $response->assertSee('Rp 145.000');
        $response->assertSee('Rp 200.000');
        $response->assertSee('Rp 15.000');
        $response->assertSee('Rp 5.000');
        $response->assertSee('Rp 345.000');

        $response->assertDontSee('matrix-main-note');
        $response->assertDontSee('matrix-main-wi');
        $response->assertDontSee('Rp 999.999');
    }

    public function test_page_custom_range_excludes_outside_date_and_canceled_package_rows(): void
    {
        $this->seedExtremePackageScenario('matrix-inside', 'Inside Customer', '2030-02-10');
        $this->seedExtremePackageScenario('matrix-outside', 'Outside Customer', '2030-02-11');
        $this->seedExtremePackageScenario('matrix-canceled', 'Canceled Customer', '2030-02-10', WorkItem::STATUS_CANCELED);

        $this->loginAsAuthorizedAdmin();

        $response = $this->get(route('admin.reports.service_package_profit_breakdown.index', [
            'period_mode' => 'custom',
            'date_from' => '2030-02-10',
            'date_to' => '2030-02-10',
        ]));

        $response->assertOk();
        $response->assertSee('Rp 450.000');
        $response->assertDontSee('Inside Customer');
        $response->assertDontSee('Outside Customer');
        $response->assertDontSee('Canceled Customer');
    }

    public function test_excel_export_matches_extreme_ui_breakdown_values_without_current_price_or_unrelated_movement_leak(): void
    {
        $this->seedExtremePackageScenario('matrix-export', 'Excel Matrix Customer', '2030-02-10');

        $this->loginAsAuthorizedAdmin();

        $response = $this->get(route('admin.reports.service_package_profit_breakdown.export_excel', [
            'period_mode' => 'monthly',
            'reference_date' => '2030-02-28',
        ]));

        $response->assertOk();

        $path = tempnam(sys_get_temp_dir(), 'service-package-profit-breakdown-ui-matrix-');
        file_put_contents($path, $response->streamedContent());

        $spreadsheet = IOFactory::load($path);
        $summary = $spreadsheet->getSheetByName('Ringkasan');
        $detail = $spreadsheet->getSheetByName('Detail Paket');

        $this->assertNotNull($summary);
        $this->assertNotNull($detail);

        $this->assertSame(1, $summary->getCell('B4')->getValue());
        $this->assertSame(450000, $summary->getCell('B5')->getValue());
        $this->assertSame(250000, $summary->getCell('B6')->getValue());
        $this->assertSame(105000, $summary->getCell('B7')->getValue());
        $this->assertSame(145000, $summary->getCell('B8')->getValue());
        $this->assertSame(200000, $summary->getCell('B9')->getValue());
        $this->assertSame(15000, $summary->getCell('B10')->getValue());
        $this->assertSame(5000, $summary->getCell('B11')->getValue());
        $this->assertSame(345000, $summary->getCell('B12')->getValue());

        $this->assertSame('matrix-export-note', $detail->getCell('B2')->getValue());
        $this->assertSame('Excel Matrix Customer', $detail->getCell('E2')->getValue());
        $this->assertSame(450000, $detail->getCell('F2')->getValue());
        $this->assertSame(250000, $detail->getCell('G2')->getValue());
        $this->assertSame(105000, $detail->getCell('H2')->getValue());
        $this->assertSame(145000, $detail->getCell('I2')->getValue());
        $this->assertSame(150000, $detail->getCell('J2')->getValue());
        $this->assertSame(100000, $detail->getCell('K2')->getValue());
        $this->assertSame(50000, $detail->getCell('L2')->getValue());
        $this->assertSame(50000, $detail->getCell('M2')->getValue());
        $this->assertSame(200000, $detail->getCell('N2')->getValue());
        $this->assertSame(15000, $detail->getCell('O2')->getValue());
        $this->assertSame(5000, $detail->getCell('P2')->getValue());
        $this->assertSame(345000, $detail->getCell('Q2')->getValue());

        unlink($path);
        $spreadsheet->disconnectWorksheets();
    }

    private function seedExtremePackageScenario(
        string $prefix,
        string $customerName,
        string $transactionDate,
        string $workItemStatus = WorkItem::STATUS_OPEN
    ): void {
        $noteId = $prefix . '-note';
        $workItemId = $prefix . '-wi';
        $productA = $prefix . '-product-a';
        $productB = $prefix . '-product-b';
        $productC = $prefix . '-product-c';
        $lineA = $prefix . '-ssl-a';
        $lineB = $prefix . '-ssl-b';
        $lineC = $prefix . '-ssl-c';

        $this->seedProductWithInventory($productA, 60000, 10, 30000);
        $this->seedProductWithInventory($productB, 80000, 10, 40000);
        $this->seedProductWithInventory($productC, 10000, 10, 7000);

        $this->seedNoteBase($noteId, $customerName, $transactionDate, 450000, 'closed');
        $this->seedWorkItemBase(
            $workItemId,
            $noteId,
            1,
            WorkItem::TYPE_SERVICE_WITH_STORE_STOCK_PART,
            $workItemStatus,
            450000
        );

        $this->seedServiceDetailBase(
            $workItemId,
            'Paket Ekstrem ' . $prefix,
            150000,
            ServiceDetail::PART_SOURCE_NONE
        );

        DB::table('work_item_service_details')
            ->where('work_item_id', $workItemId)
            ->update([
                'package_base_service_price_rupiah' => 100000,
                'package_service_extra_rupiah' => 50000,
                'package_profit_rupiah' => 50000,
            ]);

        $this->seedStoreStockLineBase($lineA, $workItemId, $productA, 2, 120000);
        $this->seedStoreStockLineBase($lineB, $workItemId, $productB, 1, 80000);
        $this->seedStoreStockLineBase($lineC, $workItemId, $productC, 5, 50000);

        $this->seedInventoryMovement($prefix . '-move-a-out', $productA, 'stock_out', 'work_item_store_stock_line', $lineA, $transactionDate, -2, 30000, -60000);
        $this->seedInventoryMovement($prefix . '-move-b-out', $productB, 'stock_out', 'work_item_store_stock_line', $lineB, $transactionDate, -1, 40000, -40000);
        $this->seedInventoryMovement($prefix . '-move-c-out', $productC, 'stock_out', 'work_item_store_stock_line', $lineC, $transactionDate, -5, 7000, -35000);
        $this->seedInventoryMovement($prefix . '-move-a-return', $productA, 'stock_in', 'work_item_store_stock_line_reversal', $lineA, $transactionDate, 1, 30000, 30000);

        // Movement tidak terkait line paket. Harus diabaikan agar tidak double count HPP.
        $this->seedInventoryMovement($prefix . '-move-unrelated', $productA, 'stock_out', 'manual_adjustment', $prefix . '-unrelated-source', $transactionDate, -9, 999999, -8999991);

        $this->seedCustomerPaymentBase($prefix . '-payment-dp', 200000, '2030-02-15 10:00:00');
        $this->seedPaymentAllocationBase($prefix . '-allocation-dp', $prefix . '-payment-dp', $noteId, 200000);

        $this->seedCustomerPaymentBase($prefix . '-payment-lunas', 250000, '2030-03-01 10:00:00');
        $this->seedPaymentAllocationBase($prefix . '-allocation-lunas', $prefix . '-payment-lunas', $noteId, 250000);

        DB::table('customer_refunds')->insert([
            'id' => $prefix . '-refund',
            'customer_payment_id' => $prefix . '-payment-lunas',
            'note_id' => $noteId,
            'amount_rupiah' => 20000,
            'refunded_at' => '2030-03-02 10:00:00',
            'reason' => 'UI matrix component refund fixture',
        ]);

        $this->seedRefundComponent($prefix . '-refund-product', $prefix . '-refund', $prefix . '-payment-lunas', $noteId, $workItemId, PaymentComponentType::SERVICE_STORE_STOCK_PART, $lineA, 15000, 1);
        $this->seedRefundComponent($prefix . '-refund-service', $prefix . '-refund', $prefix . '-payment-lunas', $noteId, $workItemId, PaymentComponentType::SERVICE_FEE, $workItemId, 5000, 2);

        // Harga jual dan costing saat ini berubah setelah transaksi. Report wajib tetap pakai snapshot line + inventory movement historis.
        DB::table('products')
            ->whereIn('id', [$productA, $productB, $productC])
            ->update(['harga_jual' => 999999]);

        DB::table('product_inventory_costing')
            ->whereIn('product_id', [$productA, $productB, $productC])
            ->update([
                'avg_cost_rupiah' => 999999,
                'inventory_value_rupiah' => 9999990,
            ]);
    }

    private function seedProductWithInventory(
        string $productId,
        int $priceRupiah,
        int $qtyOnHand,
        int $avgCostRupiah
    ): void {
        $this->seedNotePaymentProduct(
            $productId,
            strtoupper(str_replace('-', '_', $productId)),
            'Produk ' . $productId,
            'Matrix',
            100,
            $priceRupiah
        );

        DB::table('product_inventory')->updateOrInsert(
            ['product_id' => $productId],
            ['qty_on_hand' => $qtyOnHand]
        );

        DB::table('product_inventory_costing')->updateOrInsert(
            ['product_id' => $productId],
            [
                'avg_cost_rupiah' => $avgCostRupiah,
                'inventory_value_rupiah' => $avgCostRupiah * $qtyOnHand,
            ]
        );
    }

    private function seedInventoryMovement(
        string $id,
        string $productId,
        string $movementType,
        string $sourceType,
        string $sourceId,
        string $date,
        int $qtyDelta,
        int $unitCostRupiah,
        int $totalCostRupiah
    ): void {
        DB::table('inventory_movements')->insert([
            'id' => $id,
            'product_id' => $productId,
            'movement_type' => $movementType,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'tanggal_mutasi' => $date,
            'qty_delta' => $qtyDelta,
            'unit_cost_rupiah' => $unitCostRupiah,
            'total_cost_rupiah' => $totalCostRupiah,
        ]);
    }

    private function seedRefundComponent(
        string $id,
        string $refundId,
        string $paymentId,
        string $noteId,
        string $workItemId,
        string $componentType,
        string $componentRefId,
        int $refundedAmountRupiah,
        int $priority
    ): void {
        DB::table('refund_component_allocations')->insert([
            'id' => $id,
            'customer_refund_id' => $refundId,
            'customer_payment_id' => $paymentId,
            'note_id' => $noteId,
            'work_item_id' => $workItemId,
            'component_type' => $componentType,
            'component_ref_id' => $componentRefId,
            'refunded_amount_rupiah' => $refundedAmountRupiah,
            'refund_priority' => $priority,
        ]);
    }
}
