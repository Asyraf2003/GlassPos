<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Adapters\Out\Reporting\InventoryCurrentSnapshotDatabaseQuery;
use App\Adapters\Out\Reporting\Queries\OperationalProfitMetricsQuery;
use App\Adapters\Out\Reporting\Queries\TransactionCashLedgerReportingQuery;
use App\Adapters\Out\Reporting\Queries\TransactionSummaryReportingQuery;
use App\Application\Payment\UseCases\RecordCustomerRefundHandler;
use App\Core\Note\WorkItem\ServiceDetail;
use App\Core\Note\WorkItem\WorkItem;
use App\Core\Payment\PaymentComponentAllocation\PaymentComponentType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsMinimalNotePaymentFixture;
use Tests\TestCase;

final class RefundReportingOwnerDecisionV2CharacterizationTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalNotePaymentFixture;

    public function test_current_behavior_product_store_stock_refund_reverses_inventory_using_original_unit_cost_not_current_avg_and_records_component_allocation(): void
    {
        $this->seedClosedProductOnlyNote(
            noteId: 'note-batch3-product',
            workItemId: 'wi-batch3-product',
            productId: 'product-batch3-product',
            lineId: 'ssl-batch3-product',
            paymentId: 'payment-batch3-product',
            lineTotalRupiah: 50000,
            qty: 2,
            originalUnitCostRupiah: 15000,
            currentAvgCostRupiah: 90000,
        );

        self::assertSame(
            90000,
            (int) DB::table('product_inventory_costing')
                ->where('product_id', 'product-batch3-product')
                ->value('avg_cost_rupiah')
        );

        $refundId = $this->recordRefund(
            'payment-batch3-product',
            'note-batch3-product',
            50000,
            '2026-05-03',
            ['wi-batch3-product'],
        );

        $this->assertDatabaseHas('refund_component_allocations', [
            'customer_refund_id' => $refundId,
            'customer_payment_id' => 'payment-batch3-product',
            'note_id' => 'note-batch3-product',
            'work_item_id' => 'wi-batch3-product',
            'component_type' => PaymentComponentType::PRODUCT_ONLY_WORK_ITEM,
            'component_ref_id' => 'wi-batch3-product',
            'refunded_amount_rupiah' => 50000,
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => 'product-batch3-product',
            'movement_type' => 'stock_in',
            'source_type' => 'work_item_store_stock_line_reversal',
            'source_id' => 'ssl-batch3-product',
            'qty_delta' => 2,
            'unit_cost_rupiah' => 15000,
            'total_cost_rupiah' => 30000,
        ]);

        $this->assertDatabaseMissing('inventory_movements', [
            'product_id' => 'product-batch3-product',
            'movement_type' => 'stock_in',
            'source_type' => 'work_item_store_stock_line_reversal',
            'source_id' => 'ssl-batch3-product',
            'unit_cost_rupiah' => 90000,
        ]);
    }

    public function test_phase5_service_only_refund_uses_selected_refund_without_inventory(): void
    {
        $this->seedClosedServiceOnlyNote(
            noteId: 'note-batch3-service',
            workItemId: 'wi-batch3-service',
            paymentId: 'payment-batch3-service',
            servicePriceRupiah: 50000,
        );

        $result = app(RecordCustomerRefundHandler::class)->handle(
            'payment-batch3-service',
            'note-batch3-service',
            50000,
            '2026-05-04',
            'ADR-0042 selected service refund.',
            'actor-phase5-refund',
            ['wi-batch3-service'],
        );

        self::assertTrue($result->isSuccess(), $result->message());

        $this->assertDatabaseCount('customer_refunds', 1);
        $this->assertDatabaseCount('refund_component_allocations', 1);
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_phase5_external_purchase_refund_is_default_blocked_without_manual_exception_path(): void
    {
        $this->seedClosedExternalPurchaseNote(
            noteId: 'note-batch3-external',
            workItemId: 'wi-batch3-external',
            externalLineId: 'ext-batch3-external',
            paymentId: 'payment-batch3-external',
            servicePriceRupiah: 9000,
            externalCostRupiah: 2000,
        );

        $result = app(RecordCustomerRefundHandler::class)->handle(
            'payment-batch3-external',
            'note-batch3-external',
            11000,
            '2026-05-05',
            'Phase 5 default external block.',
            'actor-phase5-refund',
            ['wi-batch3-external'],
        );

        self::assertTrue($result->isFailure());
        self::assertSame('Tidak ada komponen payment yang bisa direfund.', $result->message());

        $this->assertDatabaseCount('customer_refunds', 0);
        $this->assertDatabaseCount('refund_component_allocations', 0);
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_phase5_package_refund_maps_to_raw_product_component_without_refunding_service_fee(): void
    {
        $this->seedClosedPackageNote(
            noteId: 'note-batch3-package-product-only',
            workItemId: 'wi-batch3-package-product-only',
            productId: 'product-batch3-package-product-only',
            lineId: 'ssl-batch3-package-product-only',
            paymentId: 'payment-batch3-package-product-only',
            subtotalRupiah: 100000,
            servicePriceRupiah: 70000,
            partTotalRupiah: 30000,
            originalUnitCostRupiah: 12000,
            currentAvgCostRupiah: 50000,
            productPriority: 2,
            servicePriority: 1,
        );

        $productOnlyRefundId = $this->recordRefund(
            'payment-batch3-package-product-only',
            'note-batch3-package-product-only',
            30000,
            '2026-05-06',
            ['wi-batch3-package-product-only'],
        );

        $this->assertDatabaseHas('refund_component_allocations', [
            'customer_refund_id' => $productOnlyRefundId,
            'component_type' => PaymentComponentType::SERVICE_STORE_STOCK_PART,
            'component_ref_id' => 'ssl-batch3-package-product-only',
            'refunded_amount_rupiah' => 30000,
        ]);

        $this->assertDatabaseMissing('refund_component_allocations', [
            'customer_refund_id' => $productOnlyRefundId,
            'component_type' => PaymentComponentType::SERVICE_FEE,
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'source_type' => 'work_item_store_stock_line_reversal',
            'source_id' => 'ssl-batch3-package-product-only',
            'unit_cost_rupiah' => 12000,
            'total_cost_rupiah' => 12000,
        ]);

        $this->seedClosedPackageNote(
            noteId: 'note-batch3-package-full',
            workItemId: 'wi-batch3-package-full',
            productId: 'product-batch3-package-full',
            lineId: 'ssl-batch3-package-full',
            paymentId: 'payment-batch3-package-full',
            subtotalRupiah: 100000,
            servicePriceRupiah: 70000,
            partTotalRupiah: 30000,
            originalUnitCostRupiah: 14000,
            currentAvgCostRupiah: 60000,
            productPriority: 2,
            servicePriority: 1,
        );

        $serviceRefundResult = app(RecordCustomerRefundHandler::class)->handle(
            'payment-batch3-package-full',
            'note-batch3-package-full',
            70000,
            '2026-05-07',
            'ADR-0042 selected package service refund.',
            'actor-phase5-refund',
            ['wi-batch3-package-full::service_fee::wi-batch3-package-full'],
        );

        self::assertTrue($serviceRefundResult->isSuccess());

        $productComponentRefundId = $this->recordRefund(
            'payment-batch3-package-full',
            'note-batch3-package-full',
            30000,
            '2026-05-07',
            ['wi-batch3-package-full::service_store_stock_part::ssl-batch3-package-full'],
        );

        $this->assertDatabaseHas('refund_component_allocations', [
            'customer_refund_id' => $productComponentRefundId,
            'component_type' => PaymentComponentType::SERVICE_STORE_STOCK_PART,
            'component_ref_id' => 'ssl-batch3-package-full',
            'refunded_amount_rupiah' => 30000,
        ]);

        self::assertSame(
            0,
            DB::table('refund_component_allocations')
                ->where('customer_refund_id', $productComponentRefundId)
                ->whereIn('component_type', [
                    PaymentComponentType::SERVICE_FEE,
                    'package',
                ])
                ->count()
        );
    }

    public function test_current_behavior_package_refund_after_replacement_targets_current_components_not_stale_old_components(): void
    {
        $this->seedProductWithInventory('product-batch3-stale-old', 30000, 10, 11000);
        $this->seedProductWithInventory('product-batch3-stale-current', 40000, 10, 15000);

        $this->seedNoteBase('note-batch3-stale', 'Batch 3 Stale Replacement', '2026-05-08', 130000, 'closed');

        $this->seedWorkItemBase(
            'wi-batch3-stale-old',
            'note-batch3-stale',
            1,
            WorkItem::TYPE_SERVICE_WITH_STORE_STOCK_PART,
            WorkItem::STATUS_CANCELED,
            100000
        );
        $this->seedServiceDetailBase('wi-batch3-stale-old', 'Old Package', 70000, ServiceDetail::PART_SOURCE_NONE);
        $this->seedStoreStockLineBase('ssl-batch3-stale-old', 'wi-batch3-stale-old', 'product-batch3-stale-old', 1, 30000);
        $this->seedInventoryStockOut('move-batch3-stale-old', 'product-batch3-stale-old', 'ssl-batch3-stale-old', '2026-05-08', 1, 11000);

        $this->seedWorkItemBase(
            'wi-batch3-stale-current',
            'note-batch3-stale',
            2,
            WorkItem::TYPE_SERVICE_WITH_STORE_STOCK_PART,
            WorkItem::STATUS_OPEN,
            130000
        );
        $this->seedServiceDetailBase('wi-batch3-stale-current', 'Current Package', 90000, ServiceDetail::PART_SOURCE_NONE);
        $this->seedStoreStockLineBase('ssl-batch3-stale-current', 'wi-batch3-stale-current', 'product-batch3-stale-current', 1, 40000);
        $this->seedInventoryStockOut('move-batch3-stale-current', 'product-batch3-stale-current', 'ssl-batch3-stale-current', '2026-05-08', 1, 15000);

        $this->seedCustomerPaymentBase('payment-batch3-stale', 130000, '2026-05-08 09:00:00');
        $this->seedPaymentComponent(
            'pca-batch3-stale-current-product',
            'payment-batch3-stale',
            'note-batch3-stale',
            'wi-batch3-stale-current',
            PaymentComponentType::SERVICE_STORE_STOCK_PART,
            'ssl-batch3-stale-current',
            40000,
            40000,
            2,
        );
        $this->seedPaymentComponent(
            'pca-batch3-stale-current-service',
            'payment-batch3-stale',
            'note-batch3-stale',
            'wi-batch3-stale-current',
            PaymentComponentType::SERVICE_FEE,
            'wi-batch3-stale-current',
            90000,
            90000,
            1,
        );

        $refundId = $this->recordRefund(
            'payment-batch3-stale',
            'note-batch3-stale',
            40000,
            '2026-05-09',
            ['wi-batch3-stale-current'],
        );

        $this->assertDatabaseHas('refund_component_allocations', [
            'customer_refund_id' => $refundId,
            'work_item_id' => 'wi-batch3-stale-current',
            'component_type' => PaymentComponentType::SERVICE_STORE_STOCK_PART,
            'component_ref_id' => 'ssl-batch3-stale-current',
            'refunded_amount_rupiah' => 40000,
        ]);

        $this->assertDatabaseMissing('refund_component_allocations', [
            'customer_refund_id' => $refundId,
            'work_item_id' => 'wi-batch3-stale-old',
        ]);

        $this->assertDatabaseMissing('refund_component_allocations', [
            'customer_refund_id' => $refundId,
            'component_ref_id' => 'ssl-batch3-stale-old',
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'source_type' => 'work_item_store_stock_line_reversal',
            'source_id' => 'ssl-batch3-stale-current',
            'unit_cost_rupiah' => 15000,
            'total_cost_rupiah' => 15000,
        ]);

        $this->assertDatabaseMissing('inventory_movements', [
            'source_type' => 'work_item_store_stock_line_reversal',
            'source_id' => 'ssl-batch3-stale-old',
        ]);
    }

    public function test_current_behavior_reporting_uses_transaction_payment_refund_and_movement_date_bases_without_changing_operational_profit_or_inventory_snapshot_source(): void
    {
        $this->seedProductWithInventory('product-batch3-report', 40000, 5, 88888);
        $this->seedNoteBase('note-batch3-report', 'Batch 3 Report Basis', '2026-05-10', 100000, 'closed');
        $this->seedWorkItemBase(
            'wi-batch3-report',
            'note-batch3-report',
            1,
            WorkItem::TYPE_SERVICE_WITH_STORE_STOCK_PART,
            WorkItem::STATUS_OPEN,
            100000
        );
        $this->seedServiceDetailBase('wi-batch3-report', 'Report Basis Package', 60000, ServiceDetail::PART_SOURCE_NONE);
        DB::table('work_item_service_details')
            ->where('work_item_id', 'wi-batch3-report')
            ->update([
                'package_profit_rupiah' => 15000,
                'package_base_service_price_rupiah' => 50000,
                'package_service_extra_rupiah' => 10000,
            ]);
        $this->seedStoreStockLineBase('ssl-batch3-report', 'wi-batch3-report', 'product-batch3-report', 1, 40000);
        $this->seedInventoryStockOut('move-batch3-report-out', 'product-batch3-report', 'ssl-batch3-report', '2026-05-10', 1, 25000);
        $this->seedCustomerPaymentBase('payment-batch3-report', 100000, '2026-05-20 09:00:00');

        $this->seedPaymentComponent(
            'pca-batch3-report-product',
            'payment-batch3-report',
            'note-batch3-report',
            'wi-batch3-report',
            PaymentComponentType::SERVICE_STORE_STOCK_PART,
            'ssl-batch3-report',
            40000,
            40000,
            2,
        );
        $this->seedPaymentComponent(
            'pca-batch3-report-service',
            'payment-batch3-report',
            'note-batch3-report',
            'wi-batch3-report',
            PaymentComponentType::SERVICE_FEE,
            'wi-batch3-report',
            60000,
            60000,
            1,
        );

        DB::table('customer_refunds')->insert([
            'id' => 'refund-batch3-report',
            'customer_payment_id' => 'payment-batch3-report',
            'note_id' => 'note-batch3-report',
            'amount_rupiah' => 40000,
            'refunded_at' => '2026-05-21 10:00:00',
            'reason' => 'Batch 3 reporting basis refund.',
        ]);

        DB::table('refund_component_allocations')->insert([
            'id' => 'rca-batch3-report-product',
            'customer_refund_id' => 'refund-batch3-report',
            'customer_payment_id' => 'payment-batch3-report',
            'note_id' => 'note-batch3-report',
            'work_item_id' => 'wi-batch3-report',
            'component_type' => PaymentComponentType::SERVICE_STORE_STOCK_PART,
            'component_ref_id' => 'ssl-batch3-report',
            'refunded_amount_rupiah' => 40000,
            'refund_priority' => 1,
        ]);

        $summaryRowsOnTransactionDate = app(TransactionSummaryReportingQuery::class)
            ->rows('2026-05-10', '2026-05-10');

        $summaryRowsOnPaymentAndRefundDates = app(TransactionSummaryReportingQuery::class)
            ->rows('2026-05-20', '2026-05-21');

        self::assertCount(1, $summaryRowsOnTransactionDate);
        self::assertSame('2026-05-10', $summaryRowsOnTransactionDate[0]['transaction_date']);
        self::assertSame(100000, $summaryRowsOnTransactionDate[0]['gross_transaction_rupiah']);
        self::assertSame(100000, $summaryRowsOnTransactionDate[0]['allocated_payment_rupiah']);
        self::assertSame(40000, $summaryRowsOnTransactionDate[0]['refunded_rupiah']);
        self::assertCount(0, $summaryRowsOnPaymentAndRefundDates);

        $cashRowsOnPaymentDate = app(TransactionCashLedgerReportingQuery::class)
            ->rows('2026-05-20 00:00:00', '2026-05-20 23:59:59');

        $cashRowsOnRefundDate = app(TransactionCashLedgerReportingQuery::class)
            ->rows('2026-05-21 00:00:00', '2026-05-21 23:59:59');

        self::assertCount(1, $cashRowsOnPaymentDate);
        self::assertSame('payment_allocation', $cashRowsOnPaymentDate[0]['event_type']);
        self::assertSame('in', $cashRowsOnPaymentDate[0]['direction']);
        self::assertSame(100000, $cashRowsOnPaymentDate[0]['event_amount_rupiah']);
        self::assertSame('payment_component_allocations', $cashRowsOnPaymentDate[0]['source_table']);

        self::assertCount(1, $cashRowsOnRefundDate);
        self::assertSame('refund', $cashRowsOnRefundDate[0]['event_type']);
        self::assertSame('out', $cashRowsOnRefundDate[0]['direction']);
        self::assertSame(40000, $cashRowsOnRefundDate[0]['event_amount_rupiah']);
        self::assertSame('customer_refunds', $cashRowsOnRefundDate[0]['source_table']);

        $movementDateProfit = app(OperationalProfitMetricsQuery::class)
            ->summary('2026-05-10', '2026-05-10');

        self::assertSame(0, $movementDateProfit['cash_in_rupiah']);
        self::assertSame(0, $movementDateProfit['refunded_rupiah']);
        self::assertSame(25000, $movementDateProfit['store_stock_cogs_rupiah']);
        self::assertSame(-25000, $movementDateProfit['cash_operational_profit_rupiah']);

        $moneyDateProfit = app(OperationalProfitMetricsQuery::class)
            ->summary('2026-05-20', '2026-05-21');

        self::assertSame(100000, $moneyDateProfit['cash_in_rupiah']);
        self::assertSame(40000, $moneyDateProfit['refunded_rupiah']);
        self::assertSame(0, $moneyDateProfit['store_stock_cogs_rupiah']);
        self::assertSame(60000, $moneyDateProfit['cash_operational_profit_rupiah']);
        self::assertArrayNotHasKey('package_profit_rupiah', $moneyDateProfit);
        self::assertArrayNotHasKey('total_package_gross_profit_rupiah', $moneyDateProfit);

        $snapshot = collect(InventoryCurrentSnapshotDatabaseQuery::get())
            ->firstWhere('product_id', 'product-batch3-report');

        self::assertNotNull($snapshot);
        self::assertSame(5, $snapshot['current_qty_on_hand']);
        self::assertSame(88888, $snapshot['current_avg_cost_rupiah']);
        self::assertSame(444440, $snapshot['current_inventory_value_rupiah']);
    }

    /**
     * @param  list<string>  $selectedRowIds
     */
    private function recordRefund(
        string $paymentId,
        string $noteId,
        int $amountRupiah,
        string $refundedAt,
        array $selectedRowIds
    ): string {
        $result = app(RecordCustomerRefundHandler::class)->handle(
            $paymentId,
            $noteId,
            $amountRupiah,
            $refundedAt,
            'Phase 1 Batch 3 characterization refund.',
            'actor-phase1-batch3',
            $selectedRowIds,
        );

        self::assertTrue($result->isSuccess(), $result->message() ?? 'Refund failed.');

        $refundId = (string) $result->data()['refund']['id'];

        self::assertNotSame('', $refundId);

        return $refundId;
    }

    private function seedClosedServiceOnlyNote(
        string $noteId,
        string $workItemId,
        string $paymentId,
        int $servicePriceRupiah
    ): void {
        $this->seedNoteBase($noteId, 'Batch 3 Service Refund', '2026-05-01', $servicePriceRupiah, 'closed');
        $this->seedWorkItemBase($workItemId, $noteId, 1, WorkItem::TYPE_SERVICE_ONLY, WorkItem::STATUS_OPEN, $servicePriceRupiah);
        $this->seedServiceDetailBase($workItemId, 'Batch 3 Service Only', $servicePriceRupiah, ServiceDetail::PART_SOURCE_NONE);
        $this->seedCustomerPaymentBase($paymentId, $servicePriceRupiah, '2026-05-02 09:00:00');
        $this->seedPaymentComponent(
            'pca-'.$paymentId.'-service',
            $paymentId,
            $noteId,
            $workItemId,
            PaymentComponentType::SERVICE_FEE,
            $workItemId,
            $servicePriceRupiah,
            $servicePriceRupiah,
            1,
        );
    }

    private function seedClosedExternalPurchaseNote(
        string $noteId,
        string $workItemId,
        string $externalLineId,
        string $paymentId,
        int $servicePriceRupiah,
        int $externalCostRupiah
    ): void {
        $subtotal = $servicePriceRupiah + $externalCostRupiah;

        $this->seedNoteBase($noteId, 'Batch 3 External Refund', '2026-05-01', $subtotal, 'closed');
        $this->seedWorkItemBase($workItemId, $noteId, 1, WorkItem::TYPE_SERVICE_WITH_EXTERNAL_PURCHASE, WorkItem::STATUS_OPEN, $subtotal);
        $this->seedServiceDetailBase($workItemId, 'Batch 3 External Service', $servicePriceRupiah, ServiceDetail::PART_SOURCE_NONE);
        $this->seedExternalLine($externalLineId, $workItemId, 'Batch 3 External Part', $externalCostRupiah);
        $this->seedCustomerPaymentBase($paymentId, $subtotal, '2026-05-02 09:00:00');

        $this->seedPaymentComponent(
            'pca-'.$paymentId.'-external',
            $paymentId,
            $noteId,
            $workItemId,
            PaymentComponentType::SERVICE_EXTERNAL_PURCHASE_PART,
            $externalLineId,
            $externalCostRupiah,
            $externalCostRupiah,
            2,
        );

        $this->seedPaymentComponent(
            'pca-'.$paymentId.'-service',
            $paymentId,
            $noteId,
            $workItemId,
            PaymentComponentType::SERVICE_FEE,
            $workItemId,
            $servicePriceRupiah,
            $servicePriceRupiah,
            1,
        );
    }

    private function seedClosedProductOnlyNote(
        string $noteId,
        string $workItemId,
        string $productId,
        string $lineId,
        string $paymentId,
        int $lineTotalRupiah,
        int $qty,
        int $originalUnitCostRupiah,
        int $currentAvgCostRupiah
    ): void {
        $this->seedProductWithInventory($productId, $lineTotalRupiah / max($qty, 1), 3, $currentAvgCostRupiah);
        $this->seedNoteBase($noteId, 'Batch 3 Product Refund', '2026-05-01', $lineTotalRupiah, 'closed');
        $this->seedWorkItemBase($workItemId, $noteId, 1, WorkItem::TYPE_STORE_STOCK_SALE_ONLY, WorkItem::STATUS_OPEN, $lineTotalRupiah);
        $this->seedStoreStockLineBase($lineId, $workItemId, $productId, $qty, $lineTotalRupiah);
        $this->seedInventoryStockOut('move-'.$lineId, $productId, $lineId, '2026-05-01', $qty, $originalUnitCostRupiah);
        $this->seedCustomerPaymentBase($paymentId, $lineTotalRupiah, '2026-05-02 09:00:00');

        $this->seedPaymentComponent(
            'pca-'.$paymentId.'-product',
            $paymentId,
            $noteId,
            $workItemId,
            PaymentComponentType::PRODUCT_ONLY_WORK_ITEM,
            $workItemId,
            $lineTotalRupiah,
            $lineTotalRupiah,
            1,
        );
    }

    private function seedClosedPackageNote(
        string $noteId,
        string $workItemId,
        string $productId,
        string $lineId,
        string $paymentId,
        int $subtotalRupiah,
        int $servicePriceRupiah,
        int $partTotalRupiah,
        int $originalUnitCostRupiah,
        int $currentAvgCostRupiah,
        int $productPriority,
        int $servicePriority
    ): void {
        $this->seedProductWithInventory($productId, $partTotalRupiah, 10, $currentAvgCostRupiah);
        $this->seedNoteBase($noteId, 'Batch 3 Package Refund', '2026-05-01', $subtotalRupiah, 'closed');
        $this->seedWorkItemBase($workItemId, $noteId, 1, WorkItem::TYPE_SERVICE_WITH_STORE_STOCK_PART, WorkItem::STATUS_OPEN, $subtotalRupiah);
        $this->seedServiceDetailBase($workItemId, 'Batch 3 Package Service', $servicePriceRupiah, ServiceDetail::PART_SOURCE_NONE);
        DB::table('work_item_service_details')
            ->where('work_item_id', $workItemId)
            ->update([
                'package_profit_rupiah' => max($servicePriceRupiah - 50000, 0),
                'package_base_service_price_rupiah' => min($servicePriceRupiah, 50000),
                'package_service_extra_rupiah' => max($servicePriceRupiah - 50000, 0),
            ]);
        $this->seedStoreStockLineBase($lineId, $workItemId, $productId, 1, $partTotalRupiah);
        $this->seedInventoryStockOut('move-'.$lineId, $productId, $lineId, '2026-05-01', 1, $originalUnitCostRupiah);
        $this->seedCustomerPaymentBase($paymentId, $subtotalRupiah, '2026-05-02 09:00:00');

        $this->seedPaymentComponent(
            'pca-'.$paymentId.'-product',
            $paymentId,
            $noteId,
            $workItemId,
            PaymentComponentType::SERVICE_STORE_STOCK_PART,
            $lineId,
            $partTotalRupiah,
            $partTotalRupiah,
            $productPriority,
        );

        $this->seedPaymentComponent(
            'pca-'.$paymentId.'-service',
            $paymentId,
            $noteId,
            $workItemId,
            PaymentComponentType::SERVICE_FEE,
            $workItemId,
            $servicePriceRupiah,
            $servicePriceRupiah,
            $servicePriority,
        );
    }

    private function seedProductWithInventory(string $productId, int|float $priceRupiah, int $qtyOnHand, int $avgCostRupiah): void
    {
        $this->seedNotePaymentProduct(
            $productId,
            strtoupper(str_replace('-', '_', $productId)),
            'Produk '.$productId,
            'Phase 1 Batch 3',
            100,
            (int) $priceRupiah,
        );

        DB::table('product_inventory')->updateOrInsert(
            ['product_id' => $productId],
            ['qty_on_hand' => $qtyOnHand],
        );

        DB::table('product_inventory_costing')->updateOrInsert(
            ['product_id' => $productId],
            [
                'avg_cost_rupiah' => $avgCostRupiah,
                'inventory_value_rupiah' => $avgCostRupiah * $qtyOnHand,
            ],
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

    private function seedExternalLine(string $id, string $workItemId, string $label, int $lineTotalRupiah): void
    {
        DB::table('work_item_external_purchase_lines')->insert([
            'id' => $id,
            'work_item_id' => $workItemId,
            'cost_description' => $label,
            'unit_cost_rupiah' => $lineTotalRupiah,
            'qty' => 1,
            'line_total_rupiah' => $lineTotalRupiah,
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
}
