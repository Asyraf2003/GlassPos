<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Core\Note\WorkItem\ServiceDetail;
use App\Core\Note\WorkItem\WorkItem;
use App\Core\Payment\PaymentComponentAllocation\PaymentComponentType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsMinimalNotePaymentFixture;
use Tests\TestCase;

final class CashierNoteLevelCashPaymentContractFeatureTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalNotePaymentFixture;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_partial_cash_uses_actual_money_instead_of_component_suggestion(): void
    {
        $user = $this->loginAsKasir();
        $this->seedMixedFiveHundredThousandNote('note-partial-suggestion');

        $this->actingAs($user)
            ->post($this->paymentRoute('note-partial-suggestion'), $this->cashPayload(
                noteId: 'note-partial-suggestion',
                suggestedAmount: 200000,
                receivedAmount: 149000,
                idempotencyKey: 'partial-suggestion-149',
            ))
            ->assertRedirect(route('cashier.notes.show', ['noteId' => 'note-partial-suggestion']))
            ->assertSessionHasNoErrors();

        $paymentId = (string) DB::table('customer_payments')->value('id');
        self::assertNotSame('', $paymentId);
        $this->assertDatabaseHas('customer_payments', ['id' => $paymentId, 'amount_rupiah' => 149000]);
        $this->assertDatabaseHas('customer_payment_cash_details', [
            'customer_payment_id' => $paymentId,
            'amount_paid_rupiah' => 149000,
            'amount_received_rupiah' => 149000,
            'change_rupiah' => 0,
        ]);
        $this->assertAllocation($paymentId, PaymentComponentType::SERVICE_EXTERNAL_PURCHASE_PART, 120000, 1);
        $this->assertAllocation($paymentId, PaymentComponentType::PRODUCT_ONLY_WORK_ITEM, 29000, 2);
        $this->assertNoteOutstanding('note-partial-suggestion', 351000);
    }

    public function test_partial_cash_below_outstanding_never_creates_change_from_suggestion(): void
    {
        $user = $this->loginAsKasir();
        $this->seedServiceNote('note-partial-no-change', 265000);

        $this->actingAs($user)
            ->post($this->paymentRoute('note-partial-no-change'), $this->serviceCashPayload(
                noteId: 'note-partial-no-change',
                suggestedAmount: 65000,
                receivedAmount: 100000,
                idempotencyKey: 'partial-no-change-100',
            ))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('customer_payments', ['amount_rupiah' => 100000]);
        $this->assertDatabaseHas('customer_payment_cash_details', [
            'amount_paid_rupiah' => 100000,
            'amount_received_rupiah' => 100000,
            'change_rupiah' => 0,
        ]);
        $this->assertNoteOutstanding('note-partial-no-change', 165000);
    }

    public function test_cash_above_outstanding_settles_only_outstanding_and_records_real_change(): void
    {
        $user = $this->loginAsKasir();
        $this->seedServiceNote('note-cash-settlement', 165000);

        $this->actingAs($user)
            ->post($this->paymentRoute('note-cash-settlement'), $this->serviceCashPayload(
                noteId: 'note-cash-settlement',
                suggestedAmount: 65000,
                receivedAmount: 200000,
                idempotencyKey: 'cash-settlement-200',
            ))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('customer_payments', ['amount_rupiah' => 165000]);
        $this->assertDatabaseHas('customer_payment_cash_details', [
            'amount_paid_rupiah' => 165000,
            'amount_received_rupiah' => 200000,
            'change_rupiah' => 35000,
        ]);
        $this->assertNoteOutstanding('note-cash-settlement', 0);
        $this->assertDatabaseHas('notes', ['id' => 'note-cash-settlement', 'note_state' => 'closed']);
    }

    public function test_same_note_cash_chain_keeps_three_events_and_reconciles_each_component(): void
    {
        $user = $this->loginAsKasir();
        $noteId = 'note-cash-chain';
        $this->seedMixedFiveHundredThousandNote($noteId);
        $expected = [
            ['time' => '10:00:00', 'amount' => 149000, 'outstanding' => 351000],
            ['time' => '12:00:00', 'amount' => 101000, 'outstanding' => 250000],
            ['time' => '14:00:00', 'amount' => 250000, 'outstanding' => 0],
        ];

        foreach ($expected as $index => $event) {
            Carbon::setTestNow(date('Y-m-d').' '.$event['time']);
            $selectedRows = $index === 0
                ? $this->mixedSelectionIds($noteId)
                : $this->mixedSelectionIdsAfterExternalSettlement($noteId);
            $this->actingAs($user)
                ->post($this->paymentRoute($noteId), $this->cashPayload(
                    noteId: $noteId,
                    suggestedAmount: $event['amount'],
                    receivedAmount: $event['amount'],
                    idempotencyKey: 'cash-chain-'.($index + 1),
                    selectedRowIds: $selectedRows,
                ))
                ->assertSessionHasNoErrors();

            $this->assertNoteOutstanding($noteId, $event['outstanding']);
            self::assertSame(
                500000 - $event['outstanding'],
                (int) DB::table('payment_component_allocations')->where('note_id', $noteId)->sum('allocated_amount_rupiah'),
            );
        }

        self::assertSame(3, DB::table('customer_payments')->count());
        self::assertSame(500000, (int) DB::table('customer_payments')->sum('amount_rupiah'));
        self::assertSame(120000, $this->componentAllocated($noteId, PaymentComponentType::SERVICE_EXTERNAL_PURCHASE_PART));
        self::assertSame(180000, $this->componentAllocated($noteId, PaymentComponentType::PRODUCT_ONLY_WORK_ITEM));
        self::assertSame(200000, $this->componentAllocated($noteId, PaymentComponentType::SERVICE_FEE));

        $firstPaymentId = (string) DB::table('customer_payments')->orderBy('recorded_at')->value('id');
        $this->assertAllocation($firstPaymentId, PaymentComponentType::SERVICE_EXTERNAL_PURCHASE_PART, 120000, 1);
        $this->assertAllocation($firstPaymentId, PaymentComponentType::PRODUCT_ONLY_WORK_ITEM, 29000, 2);
    }

    /** @return array<string, mixed> */
    private function cashPayload(
        string $noteId,
        int $suggestedAmount,
        int $receivedAmount,
        string $idempotencyKey,
        ?array $selectedRowIds = null,
    ): array {
        return [
            'selected_row_ids' => $selectedRowIds ?? $this->mixedSelectionIds($noteId),
            'payment_scope' => 'partial',
            'payment_method' => 'cash',
            'amount_paid' => $suggestedAmount,
            'amount_received' => $receivedAmount,
            'paid_at' => date('Y-m-d'),
            'idempotency_key' => $idempotencyKey,
        ];
    }

    /** @return array<string, mixed> */
    private function serviceCashPayload(string $noteId, int $suggestedAmount, int $receivedAmount, string $idempotencyKey): array
    {
        return [
            'selected_row_ids' => [$noteId.'-work::service_fee::'.$noteId.'-work'],
            'payment_scope' => 'partial',
            'payment_method' => 'cash',
            'amount_paid' => $suggestedAmount,
            'amount_received' => $receivedAmount,
            'paid_at' => date('Y-m-d'),
            'idempotency_key' => $idempotencyKey,
        ];
    }

    private function paymentRoute(string $noteId): string
    {
        return route('cashier.notes.payments.store', ['noteId' => $noteId]);
    }

    private function seedServiceNote(string $noteId, int $total): void
    {
        $today = date('Y-m-d');
        $workId = $noteId.'-work';
        $this->seedNoteBase($noteId, 'Cash Customer', $today, $total);
        $this->seedWorkItemBase($workId, $noteId, 1, WorkItem::TYPE_SERVICE_ONLY, WorkItem::STATUS_OPEN, $total);
        $this->seedServiceDetailBase($workId, 'Servis Cash', $total, ServiceDetail::PART_SOURCE_NONE);
        $this->seedServiceOnlyCurrentRevision(
            $noteId,
            $noteId.'-r001',
            $workId,
            'Cash Customer',
            $today,
            $total,
            'Servis Cash',
            $total,
        );
    }

    private function seedMixedFiveHundredThousandNote(string $noteId): void
    {
        $today = date('Y-m-d');
        $externalWorkId = $noteId.'-external-work';
        $productWorkId = $noteId.'-product-work';
        $externalLineId = $noteId.'-external-line';
        $productLineId = $noteId.'-product-line';
        $productId = $noteId.'-product';

        $this->seedNotePaymentProduct($productId, hargaJual: 180000);
        $this->seedNoteBase($noteId, 'Mixed Cash Customer', $today, 500000);
        $this->seedWorkItemBase($externalWorkId, $noteId, 1, WorkItem::TYPE_SERVICE_WITH_EXTERNAL_PURCHASE, WorkItem::STATUS_OPEN, 320000);
        $this->seedServiceDetailBase($externalWorkId, 'Servis Mixed', 200000, ServiceDetail::PART_SOURCE_NONE);
        DB::table('work_item_external_purchase_lines')->insert([
            'id' => $externalLineId,
            'work_item_id' => $externalWorkId,
            'cost_description' => 'Part luar',
            'unit_cost_rupiah' => 120000,
            'qty' => 1,
            'line_total_rupiah' => 120000,
        ]);
        $this->seedWorkItemBase($productWorkId, $noteId, 2, WorkItem::TYPE_STORE_STOCK_SALE_ONLY, WorkItem::STATUS_OPEN, 180000);
        $this->seedStoreStockLineBase($productLineId, $productWorkId, $productId, 1, 180000);

        $revisionId = $noteId.'-r001';
        $this->seedCurrentRevision($noteId, $revisionId, 'Mixed Cash Customer', null, $today, 500000, [
            [
                'id' => $revisionId.'-l001',
                'work_item_root_id' => $externalWorkId,
                'line_no' => 1,
                'transaction_type' => WorkItem::TYPE_SERVICE_WITH_EXTERNAL_PURCHASE,
                'status' => WorkItem::STATUS_OPEN,
                'service_label' => 'Servis Mixed',
                'service_price_rupiah' => 200000,
                'subtotal_rupiah' => 320000,
                'payload' => [
                    'work_item_root_id' => $externalWorkId,
                    'transaction_type' => WorkItem::TYPE_SERVICE_WITH_EXTERNAL_PURCHASE,
                    'status' => WorkItem::STATUS_OPEN,
                    'store_stock_lines' => [],
                    'external_purchase_lines' => [[
                        'id' => $externalLineId,
                        'cost_description' => 'Part luar',
                        'unit_cost_rupiah' => 120000,
                        'qty' => 1,
                        'line_total_rupiah' => 120000,
                    ]],
                    'service' => [
                        'service_name' => 'Servis Mixed',
                        'service_price_rupiah' => 200000,
                        'part_source' => ServiceDetail::PART_SOURCE_NONE,
                    ],
                ],
            ],
            [
                'id' => $revisionId.'-l002',
                'work_item_root_id' => $productWorkId,
                'line_no' => 2,
                'transaction_type' => WorkItem::TYPE_STORE_STOCK_SALE_ONLY,
                'status' => WorkItem::STATUS_OPEN,
                'service_label' => null,
                'service_price_rupiah' => null,
                'subtotal_rupiah' => 180000,
                'payload' => [
                    'work_item_root_id' => $productWorkId,
                    'transaction_type' => WorkItem::TYPE_STORE_STOCK_SALE_ONLY,
                    'status' => WorkItem::STATUS_OPEN,
                    'store_stock_lines' => [[
                        'id' => $productLineId,
                        'product_id' => $productId,
                        'qty' => 1,
                        'line_total_rupiah' => 180000,
                    ]],
                    'external_purchase_lines' => [],
                    'service' => null,
                ],
            ],
        ]);
    }

    /** @return list<string> */
    private function mixedSelectionIds(string $noteId): array
    {
        return [
            $noteId.'-external-work::service_external_purchase_part::'.$noteId.'-external-line',
            $noteId.'-external-work::service_fee::'.$noteId.'-external-work',
            $noteId.'-product-work::product_only_work_item::'.$noteId.'-product-work',
        ];
    }

    /** @return list<string> */
    private function mixedSelectionIdsAfterExternalSettlement(string $noteId): array
    {
        return [
            $noteId.'-external-work::service_fee::'.$noteId.'-external-work',
            $noteId.'-product-work::product_only_work_item::'.$noteId.'-product-work',
        ];
    }

    private function assertAllocation(string $paymentId, string $componentType, int $amount, int $priority): void
    {
        $this->assertDatabaseHas('payment_component_allocations', [
            'customer_payment_id' => $paymentId,
            'component_type' => $componentType,
            'allocated_amount_rupiah' => $amount,
            'allocation_priority' => $priority,
        ]);
    }

    private function assertNoteOutstanding(string $noteId, int $expected): void
    {
        self::assertSame(
            $expected,
            (int) DB::table('note_history_projection')->where('note_id', $noteId)->value('outstanding_rupiah'),
        );
    }

    private function componentAllocated(string $noteId, string $componentType): int
    {
        return (int) DB::table('payment_component_allocations')
            ->where('note_id', $noteId)
            ->where('component_type', $componentType)
            ->sum('allocated_amount_rupiah');
    }
}
