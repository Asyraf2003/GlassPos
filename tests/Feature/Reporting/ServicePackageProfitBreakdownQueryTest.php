<?php

declare(strict_types=1);

namespace Tests\Feature\Reporting;

use App\Adapters\Out\Reporting\Queries\ServicePackageProfitBreakdownQuery;
use App\Core\Note\WorkItem\ServiceDetail;
use App\Core\Note\WorkItem\WorkItem;
use App\Core\Payment\PaymentComponentAllocation\PaymentComponentType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsMinimalNotePaymentFixture;
use Tests\TestCase;

final class ServicePackageProfitBreakdownQueryTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalNotePaymentFixture;

    public function test_package_profit_breakdown_uses_historical_sources_and_component_aware_refunds_without_operational_profit_leak(): void
    {
        $this->seedProductWithInventory('product-phase6-a', 50000, 10, 999999);
        $this->seedProductWithInventory('product-phase6-b', 30000, 10, 888888);

        $this->seedNoteBase(
            'note-phase6-package',
            'Phase 6 Package Customer',
            '2026-06-01',
            200000,
            'closed'
        );

        $this->seedWorkItemBase(
            'wi-phase6-package',
            'note-phase6-package',
            1,
            WorkItem::TYPE_SERVICE_WITH_STORE_STOCK_PART,
            WorkItem::STATUS_OPEN,
            200000
        );

        $this->seedServiceDetailBase(
            'wi-phase6-package',
            'Phase 6 Package Service',
            120000,
            ServiceDetail::PART_SOURCE_NONE
        );

        DB::table('work_item_service_details')
            ->where('work_item_id', 'wi-phase6-package')
            ->update([
                'package_base_service_price_rupiah' => 70000,
                'package_service_extra_rupiah' => 20000,
                'package_profit_rupiah' => 30000,
            ]);

        $this->seedStoreStockLineBase(
            'ssl-phase6-package-a',
            'wi-phase6-package',
            'product-phase6-a',
            2,
            50000
        );

        $this->seedStoreStockLineBase(
            'ssl-phase6-package-b',
            'wi-phase6-package',
            'product-phase6-b',
            1,
            30000
        );

        $this->seedInventoryStockOut(
            'move-phase6-package-a-out',
            'product-phase6-a',
            'ssl-phase6-package-a',
            '2026-06-02',
            2,
            30000
        );

        $this->seedInventoryStockOut(
            'move-phase6-package-b-out',
            'product-phase6-b',
            'ssl-phase6-package-b',
            '2026-06-02',
            1,
            10000
        );

        $this->seedInventoryStockInReversal(
            'move-phase6-package-a-reversal',
            'product-phase6-a',
            'ssl-phase6-package-a',
            '2026-06-04',
            1,
            30000
        );

        $this->seedCustomerPaymentBase(
            'payment-phase6-package',
            200000,
            '2026-06-03 09:00:00'
        );

        $this->seedPaymentComponent(
            'pca-phase6-package-a',
            'payment-phase6-package',
            'note-phase6-package',
            'wi-phase6-package',
            PaymentComponentType::SERVICE_STORE_STOCK_PART,
            'ssl-phase6-package-a',
            50000,
            50000,
            1
        );

        $this->seedPaymentComponent(
            'pca-phase6-package-b',
            'payment-phase6-package',
            'note-phase6-package',
            'wi-phase6-package',
            PaymentComponentType::SERVICE_STORE_STOCK_PART,
            'ssl-phase6-package-b',
            30000,
            30000,
            2
        );

        $this->seedPaymentComponent(
            'pca-phase6-package-service',
            'payment-phase6-package',
            'note-phase6-package',
            'wi-phase6-package',
            PaymentComponentType::SERVICE_FEE,
            'wi-phase6-package',
            120000,
            120000,
            3
        );

        DB::table('customer_refunds')->insert([
            'id' => 'refund-phase6-package',
            'customer_payment_id' => 'payment-phase6-package',
            'note_id' => 'note-phase6-package',
            'amount_rupiah' => 40000,
            'refunded_at' => '2026-06-04 10:00:00',
            'reason' => 'Phase 6 component-aware refund fixture.',
        ]);

        $this->seedRefundComponent(
            'rca-phase6-package-product',
            'refund-phase6-package',
            'payment-phase6-package',
            'note-phase6-package',
            'wi-phase6-package',
            PaymentComponentType::SERVICE_STORE_STOCK_PART,
            'ssl-phase6-package-a',
            30000,
            1
        );

        $this->seedRefundComponent(
            'rca-phase6-package-service-manual-exception',
            'refund-phase6-package',
            'payment-phase6-package',
            'note-phase6-package',
            'wi-phase6-package',
            PaymentComponentType::SERVICE_FEE,
            'wi-phase6-package',
            10000,
            2
        );

        DB::table('products')
            ->whereIn('id', ['product-phase6-a', 'product-phase6-b'])
            ->update(['harga_jual' => 999999]);

        DB::table('product_inventory_costing')
            ->whereIn('product_id', ['product-phase6-a', 'product-phase6-b'])
            ->update([
                'avg_cost_rupiah' => 999999,
                'inventory_value_rupiah' => 9999990,
            ]);

        $rows = app(ServicePackageProfitBreakdownQuery::class)
            ->rows('2026-06-01', '2026-06-30');

        self::assertCount(1, $rows);

        $row = $rows[0];

        self::assertSame('note-phase6-package', $row['note_id']);
        self::assertSame('wi-phase6-package', $row['work_item_id']);
        self::assertSame('2026-06-01', $row['transaction_date']);

        self::assertSame(200000, $row['package_sold_amount_rupiah']);
        self::assertSame(80000, $row['parts_total_rupiah']);
        self::assertSame(120000, $row['service_price_rupiah']);
        self::assertSame(70000, $row['package_base_service_price_rupiah']);
        self::assertSame(20000, $row['package_service_extra_rupiah']);
        self::assertSame(30000, $row['package_profit_rupiah']);
        self::assertSame(150000, $row['total_service_component_rupiah']);

        self::assertSame(30000, $row['refunded_product_component_rupiah']);
        self::assertSame(10000, $row['refunded_service_component_rupiah']);

        self::assertSame(40000, $row['sparepart_cogs_rupiah']);
        self::assertSame(40000, $row['sparepart_margin_rupiah']);
        self::assertSame(70000, $row['total_package_gross_profit_rupiah']);

        self::assertArrayNotHasKey('cash_operational_profit_rupiah', $row);
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
            'Phase 6',
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

    private function seedInventoryStockOut(
        string $id,
        string $productId,
        string $lineId,
        string $date,
        int $qty,
        int $unitCostRupiah
    ): void {
        DB::table('inventory_movements')->insert([
            'id' => $id,
            'product_id' => $productId,
            'movement_type' => 'stock_out',
            'source_type' => 'work_item_store_stock_line',
            'source_id' => $lineId,
            'tanggal_mutasi' => $date,
            'qty_delta' => -$qty,
            'unit_cost_rupiah' => $unitCostRupiah,
            'total_cost_rupiah' => -($qty * $unitCostRupiah),
        ]);
    }

    private function seedInventoryStockInReversal(
        string $id,
        string $productId,
        string $lineId,
        string $date,
        int $qty,
        int $unitCostRupiah
    ): void {
        DB::table('inventory_movements')->insert([
            'id' => $id,
            'product_id' => $productId,
            'movement_type' => 'stock_in',
            'source_type' => 'work_item_store_stock_line_reversal',
            'source_id' => $lineId,
            'tanggal_mutasi' => $date,
            'qty_delta' => $qty,
            'unit_cost_rupiah' => $unitCostRupiah,
            'total_cost_rupiah' => $qty * $unitCostRupiah,
        ]);
    }

    private function seedPaymentComponent(
        string $id,
        string $paymentId,
        string $noteId,
        string $workItemId,
        string $componentType,
        string $componentRefId,
        int $componentAmountRupiah,
        int $allocatedAmountRupiah,
        int $priority
    ): void {
        DB::table('payment_component_allocations')->insert([
            'id' => $id,
            'customer_payment_id' => $paymentId,
            'note_id' => $noteId,
            'work_item_id' => $workItemId,
            'component_type' => $componentType,
            'component_ref_id' => $componentRefId,
            'component_amount_rupiah_snapshot' => $componentAmountRupiah,
            'allocated_amount_rupiah' => $allocatedAmountRupiah,
            'allocation_priority' => $priority,
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
