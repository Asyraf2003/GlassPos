<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Application\Note\Services\NoteDetailPageDataBuilder;
use App\Application\Note\Services\NoteHistoryProjectionService;
use App\Application\Note\Services\NoteOperationalStatusResolver;
use App\Adapters\Out\Reporting\Queries\TransactionSummaryReportingQuery;
use App\Application\Reporting\Services\TransactionSummaryPerNoteBuilder;
use App\Core\Note\WorkItem\ServiceDetail;
use App\Core\Note\WorkItem\WorkItem;
use App\Core\Payment\PaymentComponentAllocation\PaymentComponentType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use App\Ports\Out\Note\NoteReaderPort;
use Tests\Support\SeedsMinimalNotePaymentFixture;
use Tests\TestCase;

final class ManualFullRefundEditLifecycleMismatchFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalNotePaymentFixture;

    public function test_detail_billing_projection_uses_current_revision_after_refunded_package_component_is_replaced(): void
    {
        $this->seedOwnerReportedRefundThenEditPackageLifecycle();

        $data = app(NoteDetailPageDataBuilder::class)->build('note-owner-0045');
        self::assertIsArray($data);

        $note = $data['note'];
        $billingRows = $note['billing_rows'];

        self::assertSame(112500, $note['grand_total_rupiah']);
        self::assertSame(112500, $note['net_paid_rupiah']);
        self::assertSame(0, $note['outstanding_rupiah']);
        self::assertFalse($note['can_show_payment_form']);
        self::assertFalse($note['can_show_settle_payment_action']);

        self::assertSame(
            [],
            array_values(array_filter(
                $billingRows,
                static fn (array $row): bool => str_starts_with((string) $row['work_item_id'], 'wi-owner-old-package')
            )),
            'Detail billing must not expose historical refunded package components as current outstanding rows.',
        );

        self::assertSame(3, count($billingRows));
        self::assertSame(
            0,
            array_sum(array_map(static fn (array $row): int => (int) $row['outstanding_rupiah'], $billingRows)),
            'Current revision package is already fully allocated; billing outstanding must be zero.',
        );
    }

    public function test_note_history_projection_does_not_turn_historical_refunded_component_into_collectible_debt(): void
    {
        $this->seedOwnerReportedRefundThenEditPackageLifecycle();

        app(NoteHistoryProjectionService::class)->syncNote('note-owner-0045');

        $projection = DB::table('note_history_projection')
            ->where('note_id', 'note-owner-0045')
            ->first();

        self::assertNotNull($projection);
        self::assertSame(112500, (int) $projection->total_rupiah);
        self::assertSame(112500, (int) $projection->allocated_rupiah);
        self::assertSame(37500, (int) $projection->refunded_rupiah);
        self::assertSame(112500, (int) $projection->net_paid_rupiah);
        self::assertSame(0, (int) $projection->outstanding_rupiah);
        self::assertSame(0, (int) $projection->line_open_count);
        self::assertSame(1, (int) $projection->line_close_count);
        self::assertSame(0, (int) $projection->line_refund_count);

        $rawReportRows = app(TransactionSummaryReportingQuery::class)->rows('2026-06-01', '2026-06-30');
        self::assertCount(1, $rawReportRows);
        self::assertSame(150000, $rawReportRows[0]['allocated_payment_rupiah']);
        self::assertSame(37500, $rawReportRows[0]['refunded_rupiah']);
        self::assertSame(0, $rawReportRows[0]['outstanding_rupiah']);

        $reportRows = app(TransactionSummaryPerNoteBuilder::class)->build($rawReportRows);
        self::assertSame(0, $reportRows[0]->toArray()['outstanding_rupiah']);
        self::assertSame(112500, $reportRows[0]->toArray()['net_cash_collected_rupiah']);
    }

    public function test_refund_action_for_current_closed_revision_is_accepted_after_historical_package_refund(): void
    {
        $admin = $this->loginAsAuthorizedAdmin();

        $this->seedOwnerReportedRefundThenEditPackageLifecycle();
        $note = app(NoteReaderPort::class)->getById('note-owner-0045');
        self::assertNotNull($note);

        $status = app(NoteOperationalStatusResolver::class)->resolve($note);
        self::assertTrue($status['is_close'], json_encode($status, JSON_THROW_ON_ERROR));

        $this->actingAs($admin)
            ->from(route('admin.notes.show', ['noteId' => 'note-owner-0045']))
            ->post(route('admin.notes.refunds.store', ['noteId' => 'note-owner-0045']), [
                'selected_row_ids' => ['wi-owner-new-package'],
                'refunded_at' => '2026-06-26',
                'reason' => 'Refund current package after historical package refund.',
            ])
            ->assertRedirect(route('admin.notes.index'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $currentRefunded = (int) DB::table('refund_component_allocations')
            ->where('note_id', 'note-owner-0045')
            ->where('work_item_id', 'wi-owner-new-package')
            ->sum('refunded_amount_rupiah');

        self::assertSame(112500, $currentRefunded);
    }

    private function seedOwnerReportedRefundThenEditPackageLifecycle(): void
    {
        $noteId = 'note-owner-0045';
        $oldPackageId = 'wi-owner-old-package';
        $newPackageId = 'wi-owner-new-package';

        $this->seedNotePaymentProduct('prod-owner-1', 'OWNER-1', 'Produk Owner 1', 'Owner', 100, 17500);
        $this->seedNotePaymentProduct('prod-owner-2', 'OWNER-2', 'Produk Owner 2', 'Owner', 100, 20000);

        $this->seedInventory('prod-owner-1', 10);
        $this->seedInventory('prod-owner-2', 10);

        $this->seedNoteBase($noteId, 'Pelanggan Owner 0045', '2026-06-26', 112500, 'closed');

        $this->seedPackageWorkItem($noteId, $oldPackageId, 2, [
            ['id' => 'ssl-owner-old-1', 'product_id' => 'prod-owner-1', 'price' => 17500],
            ['id' => 'ssl-owner-old-2', 'product_id' => 'prod-owner-2', 'price' => 20000],
        ]);

        $this->seedPackageWorkItem($noteId, $newPackageId, 3, [
            ['id' => 'ssl-owner-new-1', 'product_id' => 'prod-owner-1', 'price' => 17500],
            ['id' => 'ssl-owner-new-2', 'product_id' => 'prod-owner-2', 'price' => 20000],
        ]);

        $this->seedCurrentRevision(
            $noteId,
            $noteId . '-r005',
            'Pelanggan Owner 0045',
            null,
            '2026-06-26',
            112500,
            [[
                'id' => $noteId . '-r005-line-03',
                'work_item_root_id' => $newPackageId,
                'line_no' => 3,
                'transaction_type' => WorkItem::TYPE_SERVICE_WITH_STORE_STOCK_PART,
                'status' => WorkItem::STATUS_OPEN,
                'service_label' => 'Bosklep Ex (Besar)',
                'service_price_rupiah' => 15000,
                'subtotal_rupiah' => 112500,
                'payload' => [
                    'work_item_root_id' => $newPackageId,
                    'transaction_type' => WorkItem::TYPE_SERVICE_WITH_STORE_STOCK_PART,
                    'status' => WorkItem::STATUS_OPEN,
                    'external_purchase_lines' => [],
                    'store_stock_lines' => [
                        ['id' => 'ssl-owner-new-1', 'product_id' => 'prod-owner-1', 'qty' => 1, 'line_total_rupiah' => 17500],
                        ['id' => 'ssl-owner-new-2', 'product_id' => 'prod-owner-2', 'qty' => 1, 'line_total_rupiah' => 20000],
                    ],
                    'service' => [
                        'service_name' => 'Bosklep Ex (Besar)',
                        'service_price_rupiah' => 15000,
                        'part_source' => ServiceDetail::PART_SOURCE_NONE,
                    ],
                    'pricing_mode' => 'package_auto_split',
                    'package_total_rupiah' => 112500,
                    'parts_total_rupiah' => 37500,
                    'service_price_rupiah' => 15000,
                    'package_base_service_price_rupiah' => 15000,
                    'package_service_extra_rupiah' => 0,
                    'package_profit_rupiah' => 60000,
                    'total_service_component_rupiah' => 75000,
                ],
            ]],
        );

        $this->seedPaymentAndCurrentAllocations($noteId, $newPackageId);
        $this->seedOldPackageRefund($noteId, $oldPackageId);
        $this->seedInventoryMovements();
    }

    /**
     * @param list<array{id:string,product_id:string,price:int}> $parts
     */
    private function seedPackageWorkItem(string $noteId, string $workItemId, int $lineNo, array $parts): void
    {
        $this->seedWorkItemBase(
            $workItemId,
            $noteId,
            $lineNo,
            WorkItem::TYPE_SERVICE_WITH_STORE_STOCK_PART,
            WorkItem::STATUS_OPEN,
            112500,
        );

        DB::table('work_item_service_details')->insert([
            'work_item_id' => $workItemId,
            'service_name' => 'Bosklep Ex (Besar)',
            'service_price_rupiah' => 15000,
            'package_profit_rupiah' => 60000,
            'package_base_service_price_rupiah' => 15000,
            'package_service_extra_rupiah' => 0,
            'part_source' => ServiceDetail::PART_SOURCE_NONE,
        ]);

        foreach ($parts as $part) {
            $this->seedStoreStockLineBase($part['id'], $workItemId, $part['product_id'], 1, $part['price']);
        }
    }

    private function seedPaymentAndCurrentAllocations(string $noteId, string $workItemId): void
    {
        $this->seedCustomerPaymentBase('payment-owner-current', 112500, '2026-06-26');

        DB::table('payment_component_allocations')->insert([
            [
                'id' => 'pca-owner-current-part-1',
                'customer_payment_id' => 'payment-owner-current',
                'note_id' => $noteId,
                'work_item_id' => $workItemId,
                'component_type' => PaymentComponentType::SERVICE_STORE_STOCK_PART,
                'component_ref_id' => 'ssl-owner-new-1',
                'component_amount_rupiah_snapshot' => 17500,
                'allocated_amount_rupiah' => 17500,
                'allocation_priority' => 1,
            ],
            [
                'id' => 'pca-owner-current-part-2',
                'customer_payment_id' => 'payment-owner-current',
                'note_id' => $noteId,
                'work_item_id' => $workItemId,
                'component_type' => PaymentComponentType::SERVICE_STORE_STOCK_PART,
                'component_ref_id' => 'ssl-owner-new-2',
                'component_amount_rupiah_snapshot' => 20000,
                'allocated_amount_rupiah' => 20000,
                'allocation_priority' => 2,
            ],
            [
                'id' => 'pca-owner-current-service',
                'customer_payment_id' => 'payment-owner-current',
                'note_id' => $noteId,
                'work_item_id' => $workItemId,
                'component_type' => PaymentComponentType::SERVICE_FEE,
                'component_ref_id' => $workItemId,
                'component_amount_rupiah_snapshot' => 75000,
                'allocated_amount_rupiah' => 75000,
                'allocation_priority' => 3,
            ],
        ]);
    }

    private function seedOldPackageRefund(string $noteId, string $oldPackageId): void
    {
        $this->seedCustomerPaymentBase('payment-owner-old', 37500, '2026-06-25');

        DB::table('customer_refunds')->insert([
            'id' => 'refund-owner-old-parts',
            'customer_payment_id' => 'payment-owner-old',
            'note_id' => $noteId,
            'amount_rupiah' => 37500,
            'refunded_at' => '2026-06-25',
            'reason' => 'Owner reported package product refund.',
        ]);

        DB::table('refund_component_allocations')->insert([
            [
                'id' => 'rca-owner-old-part-1',
                'customer_refund_id' => 'refund-owner-old-parts',
                'customer_payment_id' => 'payment-owner-old',
                'note_id' => $noteId,
                'work_item_id' => $oldPackageId,
                'component_type' => PaymentComponentType::SERVICE_STORE_STOCK_PART,
                'component_ref_id' => 'ssl-owner-old-1',
                'refunded_amount_rupiah' => 17500,
                'refund_priority' => 1,
            ],
            [
                'id' => 'rca-owner-old-part-2',
                'customer_refund_id' => 'refund-owner-old-parts',
                'customer_payment_id' => 'payment-owner-old',
                'note_id' => $noteId,
                'work_item_id' => $oldPackageId,
                'component_type' => PaymentComponentType::SERVICE_STORE_STOCK_PART,
                'component_ref_id' => 'ssl-owner-old-2',
                'refunded_amount_rupiah' => 20000,
                'refund_priority' => 2,
            ],
        ]);
    }

    private function seedInventory(string $productId, int $qty): void
    {
        DB::table('product_inventory')->insert(['product_id' => $productId, 'qty_on_hand' => $qty]);
        DB::table('product_inventory_costing')->insert([
            'product_id' => $productId,
            'avg_cost_rupiah' => 1000,
            'inventory_value_rupiah' => $qty * 1000,
        ]);
    }

    private function seedInventoryMovements(): void
    {
        foreach ([
            ['id' => 'old-1', 'product_id' => 'prod-owner-1', 'source_id' => 'ssl-owner-old-1', 'out' => '2026-06-25 09:00:00', 'reversal' => true],
            ['id' => 'old-2', 'product_id' => 'prod-owner-2', 'source_id' => 'ssl-owner-old-2', 'out' => '2026-06-25 09:00:00', 'reversal' => true],
            ['id' => 'new-1', 'product_id' => 'prod-owner-1', 'source_id' => 'ssl-owner-new-1', 'out' => '2026-06-26 09:00:00', 'reversal' => false],
            ['id' => 'new-2', 'product_id' => 'prod-owner-2', 'source_id' => 'ssl-owner-new-2', 'out' => '2026-06-26 09:00:00', 'reversal' => false],
        ] as $row) {
            DB::table('inventory_movements')->insert([
                'id' => 'im-out-' . $row['id'],
                'product_id' => $row['product_id'],
                'movement_type' => 'stock_out',
                'source_type' => 'work_item_store_stock_line',
                'source_id' => $row['source_id'],
                'tanggal_mutasi' => substr($row['out'], 0, 10),
                'qty_delta' => -1,
                'unit_cost_rupiah' => 1000,
                'total_cost_rupiah' => -1000,
                'created_at' => $row['out'],
                'updated_at' => $row['out'],
            ]);

            if (! $row['reversal']) {
                continue;
            }

            DB::table('inventory_movements')->insert([
                'id' => 'im-reversal-' . $row['id'],
                'product_id' => $row['product_id'],
                'movement_type' => 'stock_in',
                'source_type' => 'work_item_store_stock_line_reversal',
                'source_id' => $row['source_id'],
                'tanggal_mutasi' => '2026-06-25',
                'qty_delta' => 1,
                'unit_cost_rupiah' => 1000,
                'total_cost_rupiah' => 1000,
                'created_at' => '2026-06-25 10:00:00',
                'updated_at' => '2026-06-25 10:00:00',
            ]);
        }
    }
}
