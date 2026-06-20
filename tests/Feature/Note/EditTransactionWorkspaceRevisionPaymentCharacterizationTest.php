<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use App\Application\Note\Services\NoteRevisionLinePayloadMapper;
use App\Application\Note\UseCases\CorrectPaidServiceWithStoreStockPartServiceFeeOnlyHandler;
use App\Core\Note\WorkItem\ServiceDetail;
use App\Core\Note\WorkItem\StoreStockLine;
use App\Core\Note\WorkItem\WorkItem;
use App\Core\Payment\PaymentComponentAllocation\PaymentComponentType;
use App\Core\ProductCatalog\Product\Product;
use App\Core\Shared\ValueObjects\Money;
use App\Ports\Out\ProductCatalog\ProductReaderPort;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Tests\Support\SeedsMinimalNotePaymentFixture;
use Tests\TestCase;

final class EditTransactionWorkspaceRevisionPaymentCharacterizationTest extends TestCase
{
    use RefreshDatabase;
    use SeedsMinimalNotePaymentFixture;

    public function test_current_behavior_edit_up_from_create_dp_replays_payment_to_replacement_components_and_records_underpaid(): void
    {
        $cashier = $this->seedActor('Kasir Batch 2 Edit Up', 'kasir-batch2-edit-up@example.test', 'kasir');
        $admin = $this->seedActor('Admin Batch 2 Edit Up', 'admin-batch2-edit-up@example.test', 'admin');
        $this->seedProduct('batch2-edit-up-product', 50000, 30000, 20);

        $this->postWorkspace($cashier, 'batch2-edit-up-create-dp', [[
            'entry_mode' => 'service',
            'part_source' => 'store_stock',
            'pricing_mode' => 'package_auto_split',
            'package_total_rupiah' => 250000,
            'pay_now' => 1,
            'service' => [
                'name' => 'Batch 2 Package DP Original',
                'price_rupiah' => 0,
                'notes' => '',
            ],
            'product_lines' => [[
                'product_id' => 'batch2-edit-up-product',
                'qty' => 1,
                'unit_price_rupiah' => 50000,
            ]],
            'external_purchase_lines' => [[
                'label' => '',
                'qty' => '',
                'unit_cost_rupiah' => '',
            ]],
        ]], 'Batch 2 Edit Up DP', [
            'decision' => 'pay_partial',
            'payment_method' => 'cash',
            'paid_at' => '2026-06-10',
            'amount_paid_rupiah' => 100000,
            'amount_received_rupiah' => 100000,
        ])->assertRedirect(route('cashier.notes.index'));

        $noteId = (string) DB::table('notes')->where('customer_name', 'Batch 2 Edit Up DP')->value('id');
        $oldWorkItemId = (string) DB::table('work_items')->where('note_id', $noteId)->value('id');
        $oldLineId = (string) DB::table('work_item_store_stock_lines')->where('work_item_id', $oldWorkItemId)->value('id');
        $paymentId = (string) DB::table('customer_payments')->where('amount_rupiah', 100000)->value('id');

        self::assertNotSame('', $noteId);
        self::assertNotSame('', $oldWorkItemId);
        self::assertNotSame('', $oldLineId);
        self::assertNotSame('', $paymentId);
        $this->seedSingleStoreStockCurrentRevisionFromActiveRows($noteId, $noteId . '-r001', 'Batch 2 Edit Up DP', '2026-06-10');

        $this->assertDatabaseHas('notes', [
            'id' => $noteId,
            'total_rupiah' => 250000,
        ]);
        $this->assertDatabaseHas('work_items', [
            'id' => $oldWorkItemId,
            'subtotal_rupiah' => 250000,
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'source_id' => $oldLineId,
            'movement_type' => 'stock_out',
            'qty_delta' => -1,
            'unit_cost_rupiah' => 30000,
            'total_cost_rupiah' => -30000,
        ]);

        $this->actingAs($admin)->patch(route('admin.notes.workspace.update', ['noteId' => $noteId]), [
            'reason' => 'Batch 2 upward package revision.',
            'note' => [
                'customer_name' => 'Batch 2 Edit Up DP Revised',
                'customer_phone' => '08123',
                'transaction_date' => '2026-06-11',
            ],
            'items' => [[
                'entry_mode' => 'service',
                'description' => null,
                'part_source' => 'store_stock',
                'pricing_mode' => 'package_auto_split',
                'package_total_rupiah' => 300000,
                'service' => [
                    'name' => 'Batch 2 Package DP Revised',
                    'price_rupiah' => 0,
                    'notes' => null,
                ],
                'product_lines' => [[
                    'product_id' => 'batch2-edit-up-product',
                    'qty' => 2,
                    'unit_price_rupiah' => 50000,
                    'price_basis' => 'revision_snapshot',
                ]],
                'external_purchase_lines' => [],
            ]],
        ])->assertRedirect(route('admin.notes.show', ['noteId' => $noteId]));

        $newWorkItemId = (string) DB::table('work_items')->where('note_id', $noteId)->value('id');
        $newLineId = (string) DB::table('work_item_store_stock_lines')->where('work_item_id', $newWorkItemId)->value('id');

        self::assertNotSame('', $newWorkItemId);
        self::assertNotSame($oldWorkItemId, $newWorkItemId);
        self::assertNotSame('', $newLineId);
        self::assertNotSame($oldLineId, $newLineId);

        $this->assertDatabaseHas('notes', [
            'id' => $noteId,
            'total_rupiah' => 300000,
        ]);
        $this->assertDatabaseHas('work_items', [
            'id' => $newWorkItemId,
            'subtotal_rupiah' => 300000,
        ]);
        $this->assertDatabaseHas('work_item_service_details', [
            'work_item_id' => $newWorkItemId,
            'service_price_rupiah' => 200000,
        ]);
        $this->assertDatabaseMissing('payment_component_allocations', [
            'note_id' => $noteId,
            'work_item_id' => $oldWorkItemId,
        ]);
        $this->assertDatabaseHas('payment_component_allocations', [
            'customer_payment_id' => $paymentId,
            'note_id' => $noteId,
            'work_item_id' => $newWorkItemId,
            'component_type' => PaymentComponentType::SERVICE_STORE_STOCK_PART,
            'component_ref_id' => $newLineId,
            'component_amount_rupiah_snapshot' => 100000,
            'allocated_amount_rupiah' => 100000,
        ]);
        self::assertSame(100000, (int) DB::table('payment_component_allocations')->where('note_id', $noteId)->sum('allocated_amount_rupiah'));

        $this->assertDatabaseHas('inventory_movements', [
            'source_type' => 'transaction_workspace_updated',
            'source_id' => $oldLineId,
            'movement_type' => 'stock_in',
            'qty_delta' => 1,
            'unit_cost_rupiah' => 30000,
            'total_cost_rupiah' => 30000,
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'source_type' => 'work_item_store_stock_line',
            'source_id' => $newLineId,
            'movement_type' => 'stock_out',
            'qty_delta' => -2,
            'unit_cost_rupiah' => 30000,
            'total_cost_rupiah' => -60000,
        ]);
        $this->assertDatabaseHas('note_revision_settlements', [
            'note_root_id' => $noteId,
            'gross_total_rupiah' => 300000,
            'carry_forward_paid_rupiah' => 100000,
            'outstanding_rupiah' => 200000,
            'surplus_rupiah' => 0,
            'settlement_status' => 'underpaid',
        ]);
    }

    public function test_current_behavior_edit_down_from_create_payment_caps_replay_and_records_overpaid_pending(): void
    {
        $cashier = $this->seedActor('Kasir Batch 2 Edit Down', 'kasir-batch2-edit-down@example.test', 'kasir');
        $admin = $this->seedActor('Admin Batch 2 Edit Down', 'admin-batch2-edit-down@example.test', 'admin');
        $this->seedProduct('batch2-edit-down-product', 50000, 30000, 20);

        $this->postWorkspace($cashier, 'batch2-edit-down-create-paid', [[
            'entry_mode' => 'service',
            'part_source' => 'store_stock',
            'pricing_mode' => 'package_auto_split',
            'package_total_rupiah' => 250000,
            'pay_now' => 1,
            'service' => [
                'name' => 'Batch 2 Package Paid Original',
                'price_rupiah' => 0,
                'notes' => '',
            ],
            'product_lines' => [[
                'product_id' => 'batch2-edit-down-product',
                'qty' => 1,
                'unit_price_rupiah' => 50000,
            ]],
            'external_purchase_lines' => [[
                'label' => '',
                'qty' => '',
                'unit_cost_rupiah' => '',
            ]],
        ]], 'Batch 2 Edit Down Paid', [
            'decision' => 'pay_full',
            'payment_method' => 'transfer',
            'paid_at' => '2026-06-10',
        ])->assertRedirect(route('cashier.notes.index'));

        $noteId = (string) DB::table('notes')->where('customer_name', 'Batch 2 Edit Down Paid')->value('id');
        $oldWorkItemId = (string) DB::table('work_items')->where('note_id', $noteId)->value('id');
        $paymentId = (string) DB::table('customer_payments')->where('amount_rupiah', 250000)->value('id');

        self::assertNotSame('', $noteId);
        self::assertNotSame('', $oldWorkItemId);
        self::assertNotSame('', $paymentId);
        $this->seedSingleStoreStockCurrentRevisionFromActiveRows($noteId, $noteId . '-r001', 'Batch 2 Edit Down Paid', '2026-06-10');

        $this->actingAs($admin)->patch(route('admin.notes.workspace.update', ['noteId' => $noteId]), [
            'reason' => 'Batch 2 downward package revision.',
            'note' => [
                'customer_name' => 'Batch 2 Edit Down Paid Revised',
                'customer_phone' => '08123',
                'transaction_date' => '2026-06-11',
            ],
            'items' => [[
                'entry_mode' => 'service',
                'description' => null,
                'part_source' => 'store_stock',
                'pricing_mode' => 'package_auto_split',
                'package_total_rupiah' => 200000,
                'service' => [
                    'name' => 'Batch 2 Package Paid Revised',
                    'price_rupiah' => 0,
                    'notes' => null,
                ],
                'product_lines' => [[
                    'product_id' => 'batch2-edit-down-product',
                    'qty' => 1,
                    'unit_price_rupiah' => 50000,
                    'price_basis' => 'revision_snapshot',
                ]],
                'external_purchase_lines' => [],
            ]],
        ])->assertRedirect(route('admin.notes.show', ['noteId' => $noteId]));

        $newWorkItemId = (string) DB::table('work_items')->where('note_id', $noteId)->value('id');
        $newLineId = (string) DB::table('work_item_store_stock_lines')->where('work_item_id', $newWorkItemId)->value('id');

        self::assertNotSame('', $newWorkItemId);
        self::assertNotSame($oldWorkItemId, $newWorkItemId);
        self::assertNotSame('', $newLineId);

        $this->assertDatabaseHas('notes', [
            'id' => $noteId,
            'total_rupiah' => 200000,
        ]);
        $this->assertDatabaseHas('customer_payments', [
            'id' => $paymentId,
            'amount_rupiah' => 250000,
        ]);
        $this->assertDatabaseMissing('payment_component_allocations', [
            'note_id' => $noteId,
            'work_item_id' => $oldWorkItemId,
        ]);
        $this->assertDatabaseHas('payment_component_allocations', [
            'customer_payment_id' => $paymentId,
            'note_id' => $noteId,
            'work_item_id' => $newWorkItemId,
            'component_type' => PaymentComponentType::SERVICE_STORE_STOCK_PART,
            'component_ref_id' => $newLineId,
            'component_amount_rupiah_snapshot' => 50000,
            'allocated_amount_rupiah' => 50000,
        ]);
        $this->assertDatabaseHas('payment_component_allocations', [
            'customer_payment_id' => $paymentId,
            'note_id' => $noteId,
            'work_item_id' => $newWorkItemId,
            'component_type' => PaymentComponentType::SERVICE_FEE,
            'component_ref_id' => $newWorkItemId,
            'component_amount_rupiah_snapshot' => 150000,
            'allocated_amount_rupiah' => 150000,
        ]);
        self::assertSame(200000, (int) DB::table('payment_component_allocations')->where('note_id', $noteId)->sum('allocated_amount_rupiah'));
        $this->assertDatabaseHas('note_revision_settlements', [
            'note_root_id' => $noteId,
            'gross_total_rupiah' => 200000,
            'carry_forward_paid_rupiah' => 250000,
            'outstanding_rupiah' => 0,
            'surplus_rupiah' => 50000,
            'settlement_status' => 'overpaid_pending',
        ]);
    }

    public function test_current_gap_owner_decision_v2_target_revision_payload_is_not_full_package_financial_fingerprint(): void
    {
        $product = Product::create(
            'batch2-payload-product',
            'B2-PAYLOAD',
            'Batch 2 Payload Product',
            'Federal',
            null,
            Money::fromInt(50000),
            null,
            null,
        );
        $mapper = new NoteRevisionLinePayloadMapper($this->products([$product]));

        $item = WorkItem::createServiceWithStoreStockPart(
            'wi-batch2-payload',
            'note-batch2-payload',
            1,
            ServiceDetail::create(
                'Batch 2 Payload Package',
                Money::fromInt(130000),
                ServiceDetail::PART_SOURCE_NONE,
                Money::fromInt(40000),
                Money::fromInt(100000),
                Money::fromInt(30000),
            ),
            [
                StoreStockLine::create(
                    'ssl-batch2-payload',
                    'batch2-payload-product',
                    1,
                    Money::fromInt(50000),
                ),
            ],
        );

        $payload = $mapper->map($item);

        self::assertSame('package_auto_split', $payload['pricing_mode'] ?? null);
        self::assertSame(220000, $payload['package_total_rupiah'] ?? null);
        self::assertSame(50000, $payload['parts_total_rupiah'] ?? null);
        self::assertSame(130000, $payload['service_price_rupiah'] ?? null);
        self::assertSame(130000, $payload['service']['service_price_rupiah'] ?? null);
        self::assertSame('Batch 2 Payload Product', $payload['store_stock_lines'][0]['product_name_snapshot'] ?? null);

        foreach ([
            'package_profit_rupiah',
            'package_base_service_price_rupiah',
            'package_service_extra_rupiah',
            'total_service_component_rupiah',
        ] as $missingFinancialFingerprintField) {
            self::assertArrayNotHasKey($missingFinancialFingerprintField, $payload);
            self::assertArrayNotHasKey($missingFinancialFingerprintField, $payload['service']);
        }
    }

    public function test_phase2_package_correction_rejects_service_price_below_package_base_floor_and_keeps_rows_unchanged(): void
    {
        $this->seedNotePaymentProduct('batch2-correction-product', 'B2-CORR', 'Batch 2 Correction Product', 'Federal', 100, 50000);

        $this->seedNoteBase('note-batch2-correction', 'Budi Batch 2 Correction', '2026-06-12', 150000);
        $this->seedWorkItemBase('wi-batch2-correction', 'note-batch2-correction', 1, WorkItem::TYPE_SERVICE_WITH_STORE_STOCK_PART, WorkItem::STATUS_OPEN, 150000);
        $this->seedServiceDetailBase('wi-batch2-correction', 'Batch 2 Package Correction Original', 100000, ServiceDetail::PART_SOURCE_NONE);
        DB::table('work_item_service_details')->where('work_item_id', 'wi-batch2-correction')->update([
            'package_profit_rupiah' => 30000,
            'package_base_service_price_rupiah' => 80000,
            'package_service_extra_rupiah' => 20000,
        ]);
        $this->seedStoreStockLineBase('ssl-batch2-correction', 'wi-batch2-correction', 'batch2-correction-product', 1, 50000);
        $this->seedCustomerPaymentBase('payment-batch2-correction', 150000, '2026-06-12');

        DB::table('payment_component_allocations')->insert([
            [
                'id' => 'pca-batch2-correction-product',
                'customer_payment_id' => 'payment-batch2-correction',
                'note_id' => 'note-batch2-correction',
                'work_item_id' => 'wi-batch2-correction',
                'component_type' => PaymentComponentType::SERVICE_STORE_STOCK_PART,
                'component_ref_id' => 'ssl-batch2-correction',
                'component_amount_rupiah_snapshot' => 50000,
                'allocated_amount_rupiah' => 50000,
                'allocation_priority' => 1,
            ],
            [
                'id' => 'pca-batch2-correction-service',
                'customer_payment_id' => 'payment-batch2-correction',
                'note_id' => 'note-batch2-correction',
                'work_item_id' => 'wi-batch2-correction',
                'component_type' => PaymentComponentType::SERVICE_FEE,
                'component_ref_id' => 'wi-batch2-correction',
                'component_amount_rupiah_snapshot' => 100000,
                'allocated_amount_rupiah' => 100000,
                'allocation_priority' => 2,
            ],
        ]);

        $result = app(CorrectPaidServiceWithStoreStockPartServiceFeeOnlyHandler::class)->handle(
            'note-batch2-correction',
            1,
            'Batch 2 Package Correction Below Base',
            60000,
            ServiceDetail::PART_SOURCE_NONE,
            'Batch 2 phase 2 floor guard correction below package base.',
            'actor-batch2-correction',
        );

        self::assertFalse($result->isSuccess(), $result->message());

        $this->assertDatabaseHas('notes', [
            'id' => 'note-batch2-correction',
            'total_rupiah' => 150000,
        ]);
        $this->assertDatabaseHas('work_items', [
            'id' => 'wi-batch2-correction',
            'subtotal_rupiah' => 150000,
        ]);
        $this->assertDatabaseHas('work_item_service_details', [
            'work_item_id' => 'wi-batch2-correction',
            'service_name' => 'Batch 2 Package Correction Original',
            'service_price_rupiah' => 100000,
            'package_profit_rupiah' => 30000,
            'package_base_service_price_rupiah' => 80000,
            'package_service_extra_rupiah' => 20000,
        ]);
        $this->assertDatabaseHas('work_item_store_stock_lines', [
            'id' => 'ssl-batch2-correction',
            'work_item_id' => 'wi-batch2-correction',
            'product_id' => 'batch2-correction-product',
            'qty' => 1,
            'line_total_rupiah' => 50000,
        ]);
    }

    public function test_current_behavior_dp_and_followup_payment_do_not_change_subtotal_or_inventory_cogs(): void
    {
        $cashier = $this->seedActor('Kasir Batch 2 Followup Payment', 'kasir-batch2-followup@example.test', 'kasir');
        $admin = $this->seedActor('Admin Batch 2 Followup Payment', 'admin-batch2-followup@example.test', 'admin');
        $this->seedProduct('batch2-followup-product', 50000, 30000, 20);

        $this->postWorkspace($cashier, 'batch2-followup-create-dp', [[
            'entry_mode' => 'service',
            'part_source' => 'store_stock',
            'pricing_mode' => 'package_auto_split',
            'package_total_rupiah' => 150000,
            'pay_now' => 1,
            'service' => [
                'name' => 'Batch 2 Followup Package',
                'price_rupiah' => 0,
                'notes' => '',
            ],
            'product_lines' => [[
                'product_id' => 'batch2-followup-product',
                'qty' => 1,
                'unit_price_rupiah' => 50000,
            ]],
            'external_purchase_lines' => [[
                'label' => '',
                'qty' => '',
                'unit_cost_rupiah' => '',
            ]],
        ]], 'Batch 2 Followup Payment', [
            'decision' => 'pay_partial',
            'payment_method' => 'cash',
            'paid_at' => '2026-06-13',
            'amount_paid_rupiah' => 50000,
            'amount_received_rupiah' => 50000,
        ])->assertRedirect(route('cashier.notes.index'));

        $noteId = (string) DB::table('notes')->where('customer_name', 'Batch 2 Followup Payment')->value('id');
        $workItemId = (string) DB::table('work_items')->where('note_id', $noteId)->value('id');

        self::assertNotSame('', $noteId);
        self::assertNotSame('', $workItemId);
        $this->seedSingleStoreStockCurrentRevisionFromActiveRows($noteId, $noteId . '-r001', 'Batch 2 Followup Payment', '2026-06-10');

        $subtotalBefore = (int) DB::table('work_items')->where('id', $workItemId)->value('subtotal_rupiah');
        $noteTotalBefore = (int) DB::table('notes')->where('id', $noteId)->value('total_rupiah');
        $inventoryMovementCountBefore = DB::table('inventory_movements')->count();
        $inventoryCogsBefore = (int) DB::table('inventory_movements')->sum('total_cost_rupiah');

        $this->actingAs($admin)
            ->post(route('admin.notes.payments.store', ['noteId' => $noteId]), [
                'selected_row_ids' => [$workItemId],
                'payment_method' => 'cash',
                'paid_at' => '2026-06-14',
                'amount_received' => 100000,
            ])
            ->assertRedirect(route('admin.notes.show', ['noteId' => $noteId]))
            ->assertSessionHas('success');

        self::assertSame($noteTotalBefore, (int) DB::table('notes')->where('id', $noteId)->value('total_rupiah'));
        self::assertSame($subtotalBefore, (int) DB::table('work_items')->where('id', $workItemId)->value('subtotal_rupiah'));
        self::assertSame($inventoryMovementCountBefore, DB::table('inventory_movements')->count());
        self::assertSame($inventoryCogsBefore, (int) DB::table('inventory_movements')->sum('total_cost_rupiah'));
        self::assertSame(2, DB::table('customer_payments')->whereIn('amount_rupiah', [50000, 100000])->count());
        self::assertSame(150000, (int) DB::table('payment_component_allocations')->where('note_id', $noteId)->sum('allocated_amount_rupiah'));
        $this->assertDatabaseHas('note_history_projection', [
            'note_id' => $noteId,
            'total_rupiah' => 150000,
            'allocated_rupiah' => 150000,
            'outstanding_rupiah' => 0,
        ]);
    }

    /**
     * @param list<array<string, mixed>> $items
     * @param array<string, mixed> $inlinePayment
     */
    private function postWorkspace(User $user, string $idempotencyKey, array $items, string $customerName, array $inlinePayment): TestResponse
    {
        return $this->actingAs($user)->post(route('notes.workspace.store'), [
            'idempotency_key' => $idempotencyKey,
            'note' => [
                'customer_name' => $customerName,
                'customer_phone' => '08123',
                'transaction_date' => '2026-06-10',
            ],
            'items' => $items,
            'inline_payment' => $inlinePayment,
        ]);
    }

    private function seedActor(string $name, string $email, string $role): User
    {
        $user = User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => $role,
        ]);

        if ($role === 'admin') {
            DB::table('admin_transaction_capability_states')->updateOrInsert(
                ['actor_id' => (string) $user->getAuthIdentifier()],
                ['active' => true],
            );
        }

        return $user;
    }

    private function seedProduct(string $id, int $priceRupiah, int $avgCostRupiah, int $qtyOnHand): void
    {
        DB::table('products')->insert([
            'id' => $id,
            'kode_barang' => strtoupper(str_replace('-', '_', $id)),
            'nama_barang' => 'Produk ' . $id,
            'merek' => 'Phase 1 Batch 2',
            'ukuran' => null,
            'harga_jual' => $priceRupiah,
        ]);

        DB::table('product_inventory')->insert([
            'product_id' => $id,
            'qty_on_hand' => $qtyOnHand,
        ]);

        DB::table('product_inventory_costing')->insert([
            'product_id' => $id,
            'avg_cost_rupiah' => $avgCostRupiah,
            'inventory_value_rupiah' => $avgCostRupiah * $qtyOnHand,
        ]);
    }

    private function seedSingleStoreStockCurrentRevisionFromActiveRows(
        string $noteId,
        string $revisionId,
        string $customerName,
        string $transactionDate
    ): void {
        $workItem = DB::table('work_items')->where('note_id', $noteId)->first();
        self::assertNotNull($workItem);

        $serviceDetail = DB::table('work_item_service_details')->where('work_item_id', (string) $workItem->id)->first();
        self::assertNotNull($serviceDetail);

        $storeStockLine = DB::table('work_item_store_stock_lines')->where('work_item_id', (string) $workItem->id)->first();
        self::assertNotNull($storeStockLine);

        $this->seedServiceWithStoreStockCurrentRevision(
            $noteId,
            $revisionId,
            (string) $workItem->id,
            $customerName,
            $transactionDate,
            (int) $workItem->subtotal_rupiah,
            (string) $serviceDetail->service_name,
            (int) $serviceDetail->service_price_rupiah,
            (string) $storeStockLine->id,
            (string) $storeStockLine->product_id,
            (int) $storeStockLine->qty,
            (int) $storeStockLine->line_total_rupiah,
        );
    }

    /**
     * @param list<Product> $products
     */
    private function products(array $products): ProductReaderPort
    {
        return new class ($products) implements ProductReaderPort {
            /**
             * @param list<Product> $products
             */
            public function __construct(private readonly array $products)
            {
            }

            public function getById(string $productId): ?Product
            {
                foreach ($this->products as $product) {
                    if ($product->id() === $productId) {
                        return $product;
                    }
                }

                return null;
            }

            public function findAll(): array
            {
                return $this->products;
            }

            public function search(string $query): array
            {
                return $this->products;
            }
        };
    }
}
