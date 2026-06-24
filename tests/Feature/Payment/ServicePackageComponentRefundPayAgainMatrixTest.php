<?php

declare(strict_types=1);

namespace Tests\Feature\Payment;

use App\Application\Payment\UseCases\RecordAndAllocateNotePaymentHandler;
use App\Core\Note\Note\Note;
use App\Core\Note\WorkItem\ServiceDetail;
use App\Core\Note\WorkItem\WorkItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class ServicePackageComponentRefundPayAgainMatrixTest extends TestCase
{
    use RefreshDatabase;

    public function test_matrix_provider_is_brutal_enough_to_cover_service_package_refund_pay_again_edges(): void
    {
        self::assertCount(48, self::servicePackageComponentRefundScenarios());
    }

    #[DataProvider('servicePackageComponentRefundScenarios')]
    public function test_refunded_service_package_stock_components_cannot_be_silently_paid_again_without_new_stock_out(array $scenario): void
    {
        $seed = $this->seedRefundedServicePackageScenario($scenario);

        $beforePaymentCount = DB::table('customer_payments')->count();
        $beforeAllocationCount = DB::table('payment_component_allocations')->count();
        $beforeStockOutCount = DB::table('inventory_movements')
            ->where('movement_type', 'stock_out')
            ->whereIn('source_id', $seed['refunded_store_stock_line_ids'])
            ->count();

        $result = app(RecordAndAllocateNotePaymentHandler::class)->handle(
            $seed['note_id'],
            $seed['refund_amount_rupiah'],
            $seed['today'],
            [],
            $scenario['pay_again_method'],
            $scenario['pay_again_method'] === 'cash' ? $seed['refund_amount_rupiah'] : null,
        );

        self::assertTrue(
            $result->isFailure(),
            'Scenario [' . $scenario['name'] . '] allowed silent pay-again after service package stock component refund. '
            . 'This re-opens cash/payment on inventory-reversed package parts without issuing new stock_out.'
        );

        self::assertSame(
            $beforePaymentCount,
            DB::table('customer_payments')->count(),
            'Scenario [' . $scenario['name'] . '] created a new customer payment after refunded stock components.'
        );

        self::assertSame(
            $beforeAllocationCount,
            DB::table('payment_component_allocations')->count(),
            'Scenario [' . $scenario['name'] . '] created new payment component allocations for refunded stock components.'
        );

        self::assertSame(
            $beforeStockOutCount,
            DB::table('inventory_movements')
                ->where('movement_type', 'stock_out')
                ->whereIn('source_id', $seed['refunded_store_stock_line_ids'])
                ->count(),
            'Scenario [' . $scenario['name'] . '] should not silently change stock_out count.'
        );

        foreach ($seed['refunded_store_stock_line_ids'] as $lineId) {
            $netQty = (int) DB::table('inventory_movements')
                ->where('source_id', $lineId)
                ->sum('qty_delta');

            self::assertSame(
                0,
                $netQty,
                'Scenario [' . $scenario['name'] . '] must keep refunded stock component net inventory at zero unless a deliberate new issue flow exists.'
            );
        }
    }

    /**
     * @return array<string, array{0: array<string, mixed>}>
     */
    public static function servicePackageComponentRefundScenarios(): array
    {
        $cases = [];
        $partCounts = [2, 3, 4, 5];
        $extraLineTypes = ['none', 'service_only', 'product_only'];
        $refundPatterns = ['first', 'last', 'alternating', 'all'];
        $paymentMethods = ['cash', 'transfer'];

        $index = 0;

        foreach ($partCounts as $partCount) {
            foreach ($extraLineTypes as $extraLineType) {
                foreach ($refundPatterns as $refundPattern) {
                    $method = $paymentMethods[$index % count($paymentMethods)];
                    $name = sprintf(
                        'parts_%d__extra_%s__refund_%s__pay_%s',
                        $partCount,
                        $extraLineType,
                        $refundPattern,
                        $method,
                    );

                    $cases[$name] = [[
                        'name' => $name,
                        'part_count' => $partCount,
                        'extra_line_type' => $extraLineType,
                        'refund_pattern' => $refundPattern,
                        'pay_again_method' => $method,
                    ]];

                    $index++;
                }
            }
        }

        return $cases;
    }

    /**
     * @param array<string, mixed> $scenario
     * @return array{
     *   note_id: string,
     *   today: string,
     *   refund_amount_rupiah: int,
     *   refunded_store_stock_line_ids: list<string>
     * }
     */
    private function seedRefundedServicePackageScenario(array $scenario): array
    {
        $today = '2026-06-24';
        $noteId = 'note-package-refund-matrix';
        $packageWorkItemId = 'wi-package';
        $paymentId = 'payment-before-refund';
        $refundId = 'refund-package-parts';
        $partCount = (int) $scenario['part_count'];

        $partPrices = [27500, 37500, 90000, 12500, 45000];
        $partCosts = [1110, 1133, 1110, 1500, 1750];
        $serviceBase = 10000;
        $packageProfit = 40000;
        $serviceComponentTotal = $serviceBase + $packageProfit;

        $parts = [];
        for ($i = 1; $i <= $partCount; $i++) {
            $parts[] = [
                'index' => $i,
                'product_id' => 'prod-package-' . $i,
                'line_id' => 'ssl-package-' . $i,
                'price' => $partPrices[$i - 1],
                'cost' => $partCosts[$i - 1],
            ];
        }

        $partsTotal = array_sum(array_column($parts, 'price'));
        $packageSubtotal = $partsTotal + $serviceComponentTotal;
        $extraTotal = $this->extraLineTotal((string) $scenario['extra_line_type']);
        $noteTotal = $packageSubtotal + $extraTotal;

        $this->seedProducts($parts);
        $this->seedNote($noteId, $today, $noteTotal);
        $this->seedPackageWorkItem($noteId, $packageWorkItemId, $packageSubtotal, $serviceBase, $packageProfit);
        $this->seedPackageStoreStockLines($packageWorkItemId, $parts);
        $this->seedExtraLine($noteId, (string) $scenario['extra_line_type']);
        $this->seedInitialPaymentAndAllocations($noteId, $paymentId, $packageWorkItemId, $parts, $serviceComponentTotal, $extraTotal, $today);

        $refundedParts = $this->selectRefundedParts($parts, (string) $scenario['refund_pattern']);
        $refundAmount = array_sum(array_column($refundedParts, 'price'));

        $this->seedRefund($refundId, $paymentId, $noteId, $refundAmount, $today);
        $this->seedRefundComponentAllocations($refundId, $paymentId, $noteId, $packageWorkItemId, $refundedParts);
        $this->seedInventoryStockOutsAndRefundReversals($parts, $refundedParts, $today);

        return [
            'note_id' => $noteId,
            'today' => $today,
            'refund_amount_rupiah' => $refundAmount,
            'refunded_store_stock_line_ids' => array_values(array_column($refundedParts, 'line_id')),
        ];
    }

    /**
     * @param list<array<string, int|string>> $parts
     */
    private function seedProducts(array $parts): void
    {
        foreach ($parts as $part) {
            DB::table('products')->updateOrInsert(
                ['id' => (string) $part['product_id']],
                [
                    'kode_barang' => strtoupper((string) $part['product_id']),
                    'nama_barang' => 'Produk Package ' . $part['index'],
                    'nama_barang_normalized' => 'produk package ' . $part['index'],
                    'merek' => 'Matrix',
                    'merek_normalized' => 'matrix',
                    'ukuran' => 100,
                    'harga_jual' => (int) $part['price'],
                    'deleted_at' => null,
                    'deleted_by_actor_id' => null,
                    'delete_reason' => null,
                ],
            );
        }
    }

    private function seedNote(string $noteId, string $today, int $noteTotal): void
    {
        DB::table('notes')->insert([
            'id' => $noteId,
            'customer_name' => 'Matrix Package Refund',
            'customer_phone' => null,
            'transaction_date' => $today,
            'operational_note' => null,
            'note_state' => Note::STATE_CLOSED,
            'closed_at' => $today . ' 09:00:00',
            'closed_by_actor_id' => 'system',
            'reopened_at' => null,
            'reopened_by_actor_id' => null,
            'total_rupiah' => $noteTotal,
            'current_revision_id' => null,
            'latest_revision_number' => 1,
            'due_date' => null,
        ]);
    }

    private function seedPackageWorkItem(
        string $noteId,
        string $workItemId,
        int $packageSubtotal,
        int $serviceBase,
        int $packageProfit
    ): void {
        DB::table('work_items')->insert([
            'id' => $workItemId,
            'note_id' => $noteId,
            'line_no' => 1,
            'transaction_type' => WorkItem::TYPE_SERVICE_WITH_STORE_STOCK_PART,
            'status' => WorkItem::STATUS_OPEN,
            'subtotal_rupiah' => $packageSubtotal,
        ]);

        DB::table('work_item_service_details')->insert([
            'work_item_id' => $workItemId,
            'service_name' => 'Service Package Matrix',
            'service_price_rupiah' => $serviceBase,
            'package_profit_rupiah' => $packageProfit,
            'package_base_service_price_rupiah' => $serviceBase,
            'package_service_extra_rupiah' => 0,
            'part_source' => ServiceDetail::PART_SOURCE_NONE,
        ]);
    }

    /**
     * @param list<array<string, int|string>> $parts
     */
    private function seedPackageStoreStockLines(string $packageWorkItemId, array $parts): void
    {
        foreach ($parts as $part) {
            DB::table('work_item_store_stock_lines')->insert([
                'id' => (string) $part['line_id'],
                'work_item_id' => $packageWorkItemId,
                'product_id' => (string) $part['product_id'],
                'qty' => 1,
                'line_total_rupiah' => (int) $part['price'],
            ]);
        }
    }

    private function seedExtraLine(string $noteId, string $extraLineType): void
    {
        if ($extraLineType === 'none') {
            return;
        }

        if ($extraLineType === 'service_only') {
            DB::table('work_items')->insert([
                'id' => 'wi-extra-service',
                'note_id' => $noteId,
                'line_no' => 2,
                'transaction_type' => WorkItem::TYPE_SERVICE_ONLY,
                'status' => WorkItem::STATUS_OPEN,
                'subtotal_rupiah' => 60000,
            ]);

            DB::table('work_item_service_details')->insert([
                'work_item_id' => 'wi-extra-service',
                'service_name' => 'Extra Service Matrix',
                'service_price_rupiah' => 60000,
                'package_profit_rupiah' => 0,
                'package_base_service_price_rupiah' => null,
                'package_service_extra_rupiah' => 0,
                'part_source' => ServiceDetail::PART_SOURCE_NONE,
            ]);

            return;
        }

        if ($extraLineType === 'product_only') {
            DB::table('work_items')->insert([
                'id' => 'wi-extra-product',
                'note_id' => $noteId,
                'line_no' => 2,
                'transaction_type' => WorkItem::TYPE_STORE_STOCK_SALE_ONLY,
                'status' => WorkItem::STATUS_OPEN,
                'subtotal_rupiah' => 27500,
            ]);

            return;
        }

        self::fail('Unknown extra line type: ' . $extraLineType);
    }

    private function extraLineTotal(string $extraLineType): int
    {
        return match ($extraLineType) {
            'none' => 0,
            'service_only' => 60000,
            'product_only' => 27500,
            default => throw new \InvalidArgumentException('Unknown extra line type: ' . $extraLineType),
        };
    }

    /**
     * @param list<array<string, int|string>> $parts
     */
    private function seedInitialPaymentAndAllocations(
        string $noteId,
        string $paymentId,
        string $packageWorkItemId,
        array $parts,
        int $serviceComponentTotal,
        int $extraTotal,
        string $today
    ): void {
        $paymentAmount = array_sum(array_column($parts, 'price')) + $serviceComponentTotal + $extraTotal;

        DB::table('customer_payments')->insert([
            'id' => $paymentId,
            'amount_rupiah' => $paymentAmount,
            'paid_at' => $today,
            'payment_method' => 'cash',
        ]);

        $priority = 1;

        foreach ($parts as $part) {
            DB::table('payment_component_allocations')->insert([
                'id' => 'pca-part-' . $part['index'],
                'customer_payment_id' => $paymentId,
                'note_id' => $noteId,
                'work_item_id' => $packageWorkItemId,
                'component_type' => 'service_store_stock_part',
                'component_ref_id' => (string) $part['line_id'],
                'component_amount_rupiah_snapshot' => (int) $part['price'],
                'allocated_amount_rupiah' => (int) $part['price'],
                'allocation_priority' => $priority++,
            ]);
        }

        DB::table('payment_component_allocations')->insert([
            'id' => 'pca-package-service',
            'customer_payment_id' => $paymentId,
            'note_id' => $noteId,
            'work_item_id' => $packageWorkItemId,
            'component_type' => 'service_fee',
            'component_ref_id' => $packageWorkItemId,
            'component_amount_rupiah_snapshot' => $serviceComponentTotal,
            'allocated_amount_rupiah' => $serviceComponentTotal,
            'allocation_priority' => $priority++,
        ]);

        if ($extraTotal === 60000) {
            DB::table('payment_component_allocations')->insert([
                'id' => 'pca-extra-service',
                'customer_payment_id' => $paymentId,
                'note_id' => $noteId,
                'work_item_id' => 'wi-extra-service',
                'component_type' => 'service_fee',
                'component_ref_id' => 'wi-extra-service',
                'component_amount_rupiah_snapshot' => 60000,
                'allocated_amount_rupiah' => 60000,
                'allocation_priority' => $priority++,
            ]);
        }

        if ($extraTotal === 27500) {
            DB::table('payment_component_allocations')->insert([
                'id' => 'pca-extra-product',
                'customer_payment_id' => $paymentId,
                'note_id' => $noteId,
                'work_item_id' => 'wi-extra-product',
                'component_type' => 'product_only_work_item',
                'component_ref_id' => 'wi-extra-product',
                'component_amount_rupiah_snapshot' => 27500,
                'allocated_amount_rupiah' => 27500,
                'allocation_priority' => $priority,
            ]);
        }
    }

    private function seedRefund(string $refundId, string $paymentId, string $noteId, int $refundAmount, string $today): void
    {
        DB::table('customer_refunds')->insert([
            'id' => $refundId,
            'customer_payment_id' => $paymentId,
            'note_id' => $noteId,
            'amount_rupiah' => $refundAmount,
            'refunded_at' => $today,
            'reason' => 'Matrix refund package stock components',
        ]);
    }

    /**
     * @param list<array<string, int|string>> $parts
     * @return list<array<string, int|string>>
     */
    private function selectRefundedParts(array $parts, string $pattern): array
    {
        return match ($pattern) {
            'first' => [$parts[0]],
            'last' => [$parts[count($parts) - 1]],
            'alternating' => array_values(array_filter(
                $parts,
                static fn (array $part): bool => ((int) $part['index']) % 2 === 1,
            )),
            'all' => $parts,
            default => throw new \InvalidArgumentException('Unknown refund pattern: ' . $pattern),
        };
    }

    /**
     * @param list<array<string, int|string>> $refundedParts
     */
    private function seedRefundComponentAllocations(
        string $refundId,
        string $paymentId,
        string $noteId,
        string $packageWorkItemId,
        array $refundedParts
    ): void {
        $priority = 1;

        foreach ($refundedParts as $part) {
            DB::table('refund_component_allocations')->insert([
                'id' => 'rca-part-' . $part['index'],
                'customer_refund_id' => $refundId,
                'customer_payment_id' => $paymentId,
                'note_id' => $noteId,
                'work_item_id' => $packageWorkItemId,
                'component_type' => 'service_store_stock_part',
                'component_ref_id' => (string) $part['line_id'],
                'refunded_amount_rupiah' => (int) $part['price'],
                'refund_priority' => $priority++,
            ]);
        }
    }

    /**
     * @param list<array<string, int|string>> $parts
     * @param list<array<string, int|string>> $refundedParts
     */
    private function seedInventoryStockOutsAndRefundReversals(array $parts, array $refundedParts, string $today): void
    {
        $refundedLineIds = array_flip(array_map(
            static fn (array $part): string => (string) $part['line_id'],
            $refundedParts,
        ));

        foreach ($parts as $part) {
            DB::table('inventory_movements')->insert([
                'id' => 'im-out-' . $part['index'],
                'product_id' => (string) $part['product_id'],
                'movement_type' => 'stock_out',
                'source_type' => 'work_item_store_stock_line',
                'source_id' => (string) $part['line_id'],
                'tanggal_mutasi' => $today,
                'qty_delta' => -1,
                'unit_cost_rupiah' => (int) $part['cost'],
                'total_cost_rupiah' => -1 * (int) $part['cost'],
                'created_at' => $today . ' 09:00:00',
                'updated_at' => $today . ' 09:00:00',
                'reversal_source_id' => null,
            ]);

            if (! array_key_exists((string) $part['line_id'], $refundedLineIds)) {
                continue;
            }

            DB::table('inventory_movements')->insert([
                'id' => 'im-reversal-' . $part['index'],
                'product_id' => (string) $part['product_id'],
                'movement_type' => 'stock_in',
                'source_type' => 'work_item_store_stock_line_reversal',
                'source_id' => (string) $part['line_id'],
                'tanggal_mutasi' => $today,
                'qty_delta' => 1,
                'unit_cost_rupiah' => (int) $part['cost'],
                'total_cost_rupiah' => (int) $part['cost'],
                'created_at' => $today . ' 10:00:00',
                'updated_at' => $today . ' 10:00:00',
                'reversal_source_id' => (string) $part['line_id'],
            ]);
        }
    }
}
