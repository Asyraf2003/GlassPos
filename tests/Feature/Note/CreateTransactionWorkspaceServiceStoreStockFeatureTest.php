<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class CreateTransactionWorkspaceServiceStoreStockFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_can_store_workspace_service_with_store_stock_payload_and_redirect_to_history(): void
    {
        $this->loginAsKasir();

        $user = User::query()->create([
            'name' => 'Kasir Service Store Stock',
            'email' => 'service-store-stock@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        DB::table('products')->insert([
            'id' => 'product-1',
            'kode_barang' => 'KB-001',
            'nama_barang' => 'Oli Mesin',
            'merek' => 'Federal',
            'ukuran' => null,
            'harga_jual' => 15000,
        ]);

        DB::table('product_inventory')->insert([
            'product_id' => 'product-1',
            'qty_on_hand' => 10,
        ]);

        DB::table('product_inventory_costing')->insert([
            'product_id' => 'product-1',
            'avg_cost_rupiah' => 10000,
            'inventory_value_rupiah' => 100000,
        ]);

        $response = $this->actingAs($user)->post(route('notes.workspace.store'), [
            'idempotency_key' => 'create-workspace-service-store-stock-idem-001',
            'note' => [
                'customer_name' => 'Budi',
                'customer_phone' => '08123',
                'transaction_date' => '2026-03-15',
            ],
            'items' => [[
                'entry_mode' => 'service',
                'part_source' => 'none',
                'pay_now' => 0,
                'service' => [
                    'name' => 'Servis Tune Up',
                    'price_rupiah' => 70000,
                    'notes' => '',
                ],
                'product_lines' => [[
                    'product_id' => 'product-1',
                    'qty' => 2,
                    'unit_price_rupiah' => 20000,
                ]],
                'external_purchase_lines' => [[
                    'label' => '',
                    'qty' => '',
                    'unit_cost_rupiah' => '',
                ]],
            ]],
            'inline_payment' => [
                'decision' => 'skip',
                'payment_method' => null,
                'paid_at' => '2026-03-15',
            ],
        ]);

        $response->assertRedirect(route('cashier.notes.index'));

        $noteId = (string) DB::table('notes')->value('id');
        $workItemId = (string) DB::table('work_items')->value('id');
        $storeStockLineId = (string) DB::table('work_item_store_stock_lines')->value('id');

        $this->assertDatabaseHas('notes', [
            'id' => $noteId,
            'customer_name' => 'Budi',
            'total_rupiah' => 110000,
        ]);

        $this->assertDatabaseHas('work_items', [
            'id' => $workItemId,
            'note_id' => $noteId,
            'transaction_type' => 'service_with_store_stock_part',
            'subtotal_rupiah' => 110000,
        ]);

        $this->assertDatabaseHas('work_item_service_details', [
            'work_item_id' => $workItemId,
            'service_name' => 'Servis Tune Up',
            'service_price_rupiah' => 70000,
            'part_source' => 'none',
        ]);

        $this->assertDatabaseHas('work_item_store_stock_lines', [
            'id' => $storeStockLineId,
            'work_item_id' => $workItemId,
            'product_id' => 'product-1',
            'qty' => 2,
            'line_total_rupiah' => 40000,
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => 'product-1',
            'movement_type' => 'stock_out',
            'source_type' => 'work_item_store_stock_line',
            'source_id' => $storeStockLineId,
            'tanggal_mutasi' => '2026-03-15',
            'qty_delta' => -2,
            'unit_cost_rupiah' => 10000,
            'total_cost_rupiah' => -20000,
        ]);
    }
    public function test_cashier_can_store_workspace_service_store_stock_with_package_total_auto_split(): void
    {
        $this->loginAsKasir();

        $user = User::query()->create([
            'name' => 'Kasir Package Store Stock',
            'email' => 'service-store-stock-package@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        DB::table('products')->insert([
            'id' => 'product-package-1',
            'kode_barang' => 'KB-PKG-001',
            'nama_barang' => 'Kampas Rem',
            'merek' => 'Federal',
            'ukuran' => null,
            'harga_jual' => 40000,
        ]);

        DB::table('product_inventory')->insert([
            'product_id' => 'product-package-1',
            'qty_on_hand' => 10,
        ]);

        DB::table('product_inventory_costing')->insert([
            'product_id' => 'product-package-1',
            'avg_cost_rupiah' => 25000,
            'inventory_value_rupiah' => 250000,
        ]);

        $response = $this->actingAs($user)->post(route('notes.workspace.store'), [
            'idempotency_key' => 'create-workspace-service-store-stock-package-idem-001',
            'note' => [
                'customer_name' => 'Budi',
                'customer_phone' => '08123',
                'transaction_date' => '2026-03-15',
            ],
            'items' => [[
                'entry_mode' => 'service',
                'part_source' => 'none',
                'pricing_mode' => 'package_auto_split',
                'package_total_rupiah' => 150000,
                'pay_now' => 0,
                'service' => [
                    'name' => 'Servis Rem',
                    'price_rupiah' => 0,
                    'notes' => '',
                ],
                'product_lines' => [[
                    'product_id' => 'product-package-1',
                    'qty' => 1,
                    'unit_price_rupiah' => 40000,
                ]],
                'external_purchase_lines' => [[
                    'label' => '',
                    'qty' => '',
                    'unit_cost_rupiah' => '',
                ]],
            ]],
            'inline_payment' => [
                'decision' => 'skip',
                'payment_method' => null,
                'paid_at' => '2026-03-15',
            ],
        ]);

        $response->assertRedirect(route('cashier.notes.index'));

        $noteId = (string) DB::table('notes')->where('customer_name', 'Budi')->value('id');
        $workItemId = (string) DB::table('work_items')->where('note_id', $noteId)->value('id');

        $this->assertDatabaseHas('notes', [
            'id' => $noteId,
            'total_rupiah' => 150000,
        ]);

        $this->assertDatabaseHas('work_items', [
            'id' => $workItemId,
            'note_id' => $noteId,
            'transaction_type' => 'service_with_store_stock_part',
            'subtotal_rupiah' => 150000,
        ]);

        $this->assertDatabaseHas('work_item_service_details', [
            'work_item_id' => $workItemId,
            'service_name' => 'Servis Rem',
            'service_price_rupiah' => 110000,
            'part_source' => 'none',
        ]);

        $this->assertDatabaseHas('work_item_store_stock_lines', [
            'work_item_id' => $workItemId,
            'product_id' => 'product-package-1',
            'qty' => 1,
            'line_total_rupiah' => 40000,
        ]);
    }

    public function test_cashier_can_store_workspace_service_store_stock_package_auto_split_with_two_different_products(): void
    {
        $this->loginAsKasir();

        $user = User::query()->create([
            'name' => 'Kasir Package Multi Store Stock',
            'email' => 'service-store-stock-package-multi@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        DB::table('products')->insert([
            [
                'id' => 'product-package-multi-a',
                'kode_barang' => 'KB-PKG-MULTI-001',
                'nama_barang' => 'Oli Paket Multi',
                'merek' => 'Federal',
                'ukuran' => null,
                'harga_jual' => 50000,
            ],
            [
                'id' => 'product-package-multi-b',
                'kode_barang' => 'KB-PKG-MULTI-002',
                'nama_barang' => 'Busi Paket Multi',
                'merek' => 'NGK',
                'ukuran' => null,
                'harga_jual' => 30000,
            ],
        ]);

        DB::table('product_inventory')->insert([
            [
                'product_id' => 'product-package-multi-a',
                'qty_on_hand' => 10,
            ],
            [
                'product_id' => 'product-package-multi-b',
                'qty_on_hand' => 10,
            ],
        ]);

        DB::table('product_inventory_costing')->insert([
            [
                'product_id' => 'product-package-multi-a',
                'avg_cost_rupiah' => 35000,
                'inventory_value_rupiah' => 350000,
            ],
            [
                'product_id' => 'product-package-multi-b',
                'avg_cost_rupiah' => 20000,
                'inventory_value_rupiah' => 200000,
            ],
        ]);

        $response = $this->actingAs($user)->post(route('notes.workspace.store'), [
            'idempotency_key' => 'create-workspace-service-store-stock-multi-package-idem-001',
            'note' => [
                'customer_name' => 'Budi Multi Package',
                'customer_phone' => '08123',
                'transaction_date' => '2026-03-15',
            ],
            'items' => [[
                'entry_mode' => 'service',
                'part_source' => 'none',
                'pricing_mode' => 'package_auto_split',
                'package_total_rupiah' => 250000,
                'pay_now' => 0,
                'service' => [
                    'name' => 'Servis Paket Multi Part',
                    'price_rupiah' => 0,
                    'notes' => '',
                ],
                'product_lines' => [
                    [
                        'product_id' => 'product-package-multi-a',
                        'qty' => 2,
                        'unit_price_rupiah' => 50000,
                    ],
                    [
                        'product_id' => 'product-package-multi-b',
                        'qty' => 1,
                        'unit_price_rupiah' => 30000,
                    ],
                ],
                'external_purchase_lines' => [[
                    'label' => '',
                    'qty' => '',
                    'unit_cost_rupiah' => '',
                ]],
            ]],
            'inline_payment' => [
                'decision' => 'skip',
                'payment_method' => null,
                'paid_at' => '2026-03-15',
            ],
        ]);

        $response->assertRedirect(route('cashier.notes.index'));

        $noteId = (string) DB::table('notes')->where('customer_name', 'Budi Multi Package')->value('id');
        $workItemId = (string) DB::table('work_items')->where('note_id', $noteId)->value('id');

        $this->assertDatabaseHas('notes', [
            'id' => $noteId,
            'total_rupiah' => 250000,
        ]);

        $this->assertDatabaseCount('work_items', 1);
        $this->assertDatabaseHas('work_items', [
            'id' => $workItemId,
            'note_id' => $noteId,
            'transaction_type' => 'service_with_store_stock_part',
            'subtotal_rupiah' => 250000,
        ]);

        $this->assertDatabaseHas('work_item_service_details', [
            'work_item_id' => $workItemId,
            'service_name' => 'Servis Paket Multi Part',
            'service_price_rupiah' => 120000,
            'part_source' => 'none',
        ]);

        $this->assertDatabaseCount('work_item_store_stock_lines', 2);
        $this->assertDatabaseHas('work_item_store_stock_lines', [
            'work_item_id' => $workItemId,
            'product_id' => 'product-package-multi-a',
            'qty' => 2,
            'line_total_rupiah' => 100000,
        ]);
        $this->assertDatabaseHas('work_item_store_stock_lines', [
            'work_item_id' => $workItemId,
            'product_id' => 'product-package-multi-b',
            'qty' => 1,
            'line_total_rupiah' => 30000,
        ]);

        $storeStockLineIds = DB::table('work_item_store_stock_lines')
            ->where('work_item_id', $workItemId)
            ->pluck('id', 'product_id');

        $this->assertDatabaseCount('inventory_movements', 2);
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => 'product-package-multi-a',
            'movement_type' => 'stock_out',
            'source_type' => 'work_item_store_stock_line',
            'source_id' => (string) $storeStockLineIds['product-package-multi-a'],
            'tanggal_mutasi' => '2026-03-15',
            'qty_delta' => -2,
            'unit_cost_rupiah' => 35000,
            'total_cost_rupiah' => -70000,
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => 'product-package-multi-b',
            'movement_type' => 'stock_out',
            'source_type' => 'work_item_store_stock_line',
            'source_id' => (string) $storeStockLineIds['product-package-multi-b'],
            'tanggal_mutasi' => '2026-03-15',
            'qty_delta' => -1,
            'unit_cost_rupiah' => 20000,
            'total_cost_rupiah' => -20000,
        ]);

        $audit = DB::table('audit_logs')
            ->where('event', 'transaction_workspace_created')
            ->latest('id')
            ->first();

        $this->assertNotNull($audit);

        $context = json_decode((string) $audit->context, true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame($noteId, $context['note_id'] ?? null);
        $this->assertSame([
            [
                'work_item_id' => $workItemId,
                'store_stock_line_id' => (string) $storeStockLineIds['product-package-multi-a'],
                'pricing_mode' => 'package_auto_split',
                'package_total_rupiah' => 250000,
                'sparepart_total_rupiah' => 100000,
                'service_price_rupiah' => 120000,
                'product_id' => 'product-package-multi-a',
                'qty' => 2,
                'product_unit_price_rupiah' => 50000,
            ],
            [
                'work_item_id' => $workItemId,
                'store_stock_line_id' => (string) $storeStockLineIds['product-package-multi-b'],
                'pricing_mode' => 'package_auto_split',
                'package_total_rupiah' => 250000,
                'sparepart_total_rupiah' => 30000,
                'service_price_rupiah' => 120000,
                'product_id' => 'product-package-multi-b',
                'qty' => 1,
                'product_unit_price_rupiah' => 30000,
            ],
        ], $context['package_allocations'] ?? null);
    }

    public function test_cashier_cannot_store_workspace_service_store_stock_package_auto_split_with_duplicate_product_id(): void
    {
        $this->loginAsKasir();

        $user = User::query()->create([
            'name' => 'Kasir Package Duplicate Product',
            'email' => 'service-store-stock-package-duplicate@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        DB::table('products')->insert([
            'id' => 'product-package-duplicate-1',
            'kode_barang' => 'KB-PKG-DUP-001',
            'nama_barang' => 'Oli Paket Duplicate',
            'merek' => 'Federal',
            'ukuran' => null,
            'harga_jual' => 50000,
        ]);

        DB::table('product_inventory')->insert([
            'product_id' => 'product-package-duplicate-1',
            'qty_on_hand' => 10,
        ]);

        DB::table('product_inventory_costing')->insert([
            'product_id' => 'product-package-duplicate-1',
            'avg_cost_rupiah' => 35000,
            'inventory_value_rupiah' => 350000,
        ]);

        $response = $this->actingAs($user)
            ->from(route('cashier.notes.workspace.create'))
            ->post(route('notes.workspace.store'), [
                'idempotency_key' => 'create-workspace-service-store-stock-duplicate-idem-001',
                'note' => [
                    'customer_name' => 'Budi Duplicate Package',
                    'customer_phone' => '08123',
                    'transaction_date' => '2026-03-15',
                ],
                'items' => [[
                    'entry_mode' => 'service',
                    'part_source' => 'none',
                    'pricing_mode' => 'package_auto_split',
                    'package_total_rupiah' => 250000,
                    'pay_now' => 0,
                    'service' => [
                        'name' => 'Servis Paket Duplicate Product',
                        'price_rupiah' => 0,
                        'notes' => '',
                    ],
                    'product_lines' => [
                        [
                            'product_id' => 'product-package-duplicate-1',
                            'qty' => 1,
                            'unit_price_rupiah' => 50000,
                        ],
                        [
                            'product_id' => 'product-package-duplicate-1',
                            'qty' => 2,
                            'unit_price_rupiah' => 50000,
                        ],
                    ],
                    'external_purchase_lines' => [[
                        'label' => '',
                        'qty' => '',
                        'unit_cost_rupiah' => '',
                    ]],
                ]],
                'inline_payment' => [
                    'decision' => 'skip',
                    'payment_method' => null,
                    'paid_at' => '2026-03-15',
                ],
            ]);

        $response->assertRedirect(route('cashier.notes.workspace.create'));
        $response->assertSessionHasErrors([
            'workspace' => 'Produk yang sama tidak boleh diinput dua kali dalam satu baris servis. Aturan ini mencegah alokasi paket dan stok tercatat ganda. Naikkan qty pada baris produk yang sudah ada.',
        ]);

        $this->assertDatabaseCount('notes', 0);
        $this->assertDatabaseCount('work_items', 0);
        $this->assertDatabaseCount('work_item_service_details', 0);
        $this->assertDatabaseCount('work_item_store_stock_lines', 0);
        $this->assertDatabaseCount('inventory_movements', 0);
        $this->assertDatabaseCount('customer_payments', 0);
        $this->assertDatabaseCount('payment_component_allocations', 0);
    }

    public function test_cashier_can_store_workspace_service_store_stock_package_total_equal_sparepart_minimum(): void
    {
        $this->loginAsKasir();

        $user = User::query()->create([
            'name' => 'Kasir Package Minimum',
            'email' => 'service-store-stock-package-min@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        DB::table('products')->insert([
            'id' => 'product-package-min-1',
            'kode_barang' => 'KB-PKG-MIN-001',
            'nama_barang' => 'Busi',
            'merek' => 'NGK',
            'ukuran' => null,
            'harga_jual' => 40000,
        ]);

        DB::table('product_inventory')->insert([
            'product_id' => 'product-package-min-1',
            'qty_on_hand' => 10,
        ]);

        DB::table('product_inventory_costing')->insert([
            'product_id' => 'product-package-min-1',
            'avg_cost_rupiah' => 25000,
            'inventory_value_rupiah' => 250000,
        ]);

        $response = $this->actingAs($user)->post(route('notes.workspace.store'), [
            'idempotency_key' => 'create-workspace-service-store-stock-equal-package-idem-001',
            'note' => [
                'customer_name' => 'Budi',
                'customer_phone' => '08123',
                'transaction_date' => '2026-03-15',
            ],
            'items' => [[
                'entry_mode' => 'service',
                'part_source' => 'none',
                'pricing_mode' => 'package_auto_split',
                'package_total_rupiah' => 40000,
                'pay_now' => 0,
                'service' => [
                    'name' => 'Servis Busi',
                    'price_rupiah' => 0,
                    'notes' => '',
                ],
                'product_lines' => [[
                    'product_id' => 'product-package-min-1',
                    'qty' => 1,
                    'unit_price_rupiah' => 40000,
                ]],
                'external_purchase_lines' => [[
                    'label' => '',
                    'qty' => '',
                    'unit_cost_rupiah' => '',
                ]],
            ]],
            'inline_payment' => [
                'decision' => 'skip',
                'payment_method' => null,
                'paid_at' => '2026-03-15',
            ],
        ]);

        $response->assertRedirect(route('cashier.notes.index'));

        $noteId = (string) DB::table('notes')->where('customer_name', 'Budi')->value('id');
        $workItemId = (string) DB::table('work_items')->where('note_id', $noteId)->value('id');

        $this->assertDatabaseHas('notes', [
            'id' => $noteId,
            'total_rupiah' => 40000,
        ]);

        $this->assertDatabaseHas('work_items', [
            'id' => $workItemId,
            'note_id' => $noteId,
            'transaction_type' => 'service_with_store_stock_part',
            'subtotal_rupiah' => 40000,
        ]);

        $this->assertDatabaseHas('work_item_service_details', [
            'work_item_id' => $workItemId,
            'service_name' => 'Servis Busi',
            'service_price_rupiah' => 0,
            'part_source' => 'none',
        ]);

        $this->assertDatabaseHas('work_item_store_stock_lines', [
            'work_item_id' => $workItemId,
            'product_id' => 'product-package-min-1',
            'qty' => 1,
            'line_total_rupiah' => 40000,
        ]);
    }

    public function test_cashier_cannot_store_workspace_service_store_stock_package_total_below_sparepart_minimum(): void
    {
        $this->loginAsKasir();

        $user = User::query()->create([
            'name' => 'Kasir Package Below Minimum',
            'email' => 'service-store-stock-package-below-min@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        DB::table('products')->insert([
            'id' => 'product-package-below-min-1',
            'kode_barang' => 'KB-PKG-BELOW-MIN-001',
            'nama_barang' => 'Busi Racing',
            'merek' => 'NGK',
            'ukuran' => null,
            'harga_jual' => 40000,
        ]);

        DB::table('product_inventory')->insert([
            'product_id' => 'product-package-below-min-1',
            'qty_on_hand' => 10,
        ]);

        DB::table('product_inventory_costing')->insert([
            'product_id' => 'product-package-below-min-1',
            'avg_cost_rupiah' => 25000,
            'inventory_value_rupiah' => 250000,
        ]);

        $response = $this->actingAs($user)
            ->from(route('cashier.notes.workspace.create'))
            ->post(route('notes.workspace.store'), [
                'idempotency_key' => 'create-workspace-service-store-stock-too-low-idem-001',
                'note' => [
                    'customer_name' => 'Budi',
                    'customer_phone' => '08123',
                    'transaction_date' => '2026-03-15',
                ],
                'items' => [[
                    'entry_mode' => 'service',
                    'part_source' => 'none',
                    'pricing_mode' => 'package_auto_split',
                    'package_total_rupiah' => 30000,
                    'pay_now' => 0,
                    'service' => [
                        'name' => 'Servis Busi Racing',
                        'price_rupiah' => 0,
                        'notes' => '',
                    ],
                    'product_lines' => [[
                        'product_id' => 'product-package-below-min-1',
                        'qty' => 1,
                        'unit_price_rupiah' => 40000,
                    ]],
                    'external_purchase_lines' => [[
                        'label' => '',
                        'qty' => '',
                        'unit_cost_rupiah' => '',
                    ]],
                ]],
                'inline_payment' => [
                    'decision' => 'skip',
                    'payment_method' => null,
                    'paid_at' => '2026-03-15',
                ],
            ]);

        $response->assertRedirect(route('cashier.notes.workspace.create'));
        $response->assertSessionHasErrors([
            'workspace' => 'Harga paket tidak boleh lebih kecil dari total harga sparepart.',
        ]);

        $this->assertDatabaseCount('notes', 0);
        $this->assertDatabaseCount('work_items', 0);
        $this->assertDatabaseCount('work_item_service_details', 0);
        $this->assertDatabaseCount('work_item_store_stock_lines', 0);
        $this->assertDatabaseCount('inventory_movements', 0);
        $this->assertDatabaseCount('customer_payments', 0);
        $this->assertDatabaseCount('payment_component_allocations', 0);
    }


    public function test_workspace_service_store_stock_package_auto_split_accepts_browser_form_strings_and_partial_payment(): void
    {
        $this->loginAsKasir();

        $user = User::query()->create([
            'name' => 'Kasir Browser Contract Package',
            'email' => 'service-store-stock-browser-contract@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        DB::table('products')->insert([
            'id' => 'product-browser-contract-1',
            'kode_barang' => 'KB-BROWSER-001',
            'nama_barang' => 'Kampas Rem Browser Contract',
            'merek' => 'Federal',
            'ukuran' => null,
            'harga_jual' => 40000,
        ]);

        DB::table('product_inventory')->insert([
            'product_id' => 'product-browser-contract-1',
            'qty_on_hand' => 10,
        ]);

        DB::table('product_inventory_costing')->insert([
            'product_id' => 'product-browser-contract-1',
            'avg_cost_rupiah' => 25000,
            'inventory_value_rupiah' => 250000,
        ]);

        $response = $this->actingAs($user)
            ->from(route('cashier.notes.workspace.create'))
            ->post(route('notes.workspace.store'), [
                'idempotency_key' => 'create-workspace-service-store-stock-browser-idem-001',
                'note' => [
                    'customer_name' => 'Budi Browser Contract',
                    'customer_phone' => '08123',
                    'transaction_date' => '2026-03-15',
                ],
                'items' => [[
                    'entry_mode' => 'service',
                    'part_source' => 'store_stock',
                    'pricing_mode' => 'package_auto_split',
                    'package_total_rupiah' => '250000',
                    'pay_now' => '0',
                    'service' => [
                        'name' => 'Servis Rem Browser Contract',
                        'price_rupiah' => '0',
                        'notes' => '',
                    ],
                    'product_lines' => [[
                        'product_id' => 'product-browser-contract-1',
                        'qty' => '1',
                        'unit_price_rupiah' => '40000',
                        'price_basis' => 'current_catalog',
                    ]],
                    'external_purchase_lines' => [[
                        'label' => '',
                        'qty' => '',
                        'unit_cost_rupiah' => '',
                    ]],
                ]],
                'inline_payment' => [
                    'decision' => 'pay_partial',
                    'payment_method' => 'cash',
                    'paid_at' => '2026-03-15',
                    'amount_paid_rupiah' => '100000',
                    'amount_received_rupiah' => '100000',
                ],
            ]);

        $response->assertRedirect(route('cashier.notes.index'));

        $note = DB::table('notes')
            ->where('customer_name', 'Budi Browser Contract')
            ->first();

        $this->assertNotNull($note);
        $this->assertSame(250000, (int) $note->total_rupiah);
        $this->assertSame('open', (string) $note->note_state);

        $workItem = DB::table('work_items')
            ->where('note_id', (string) $note->id)
            ->first();

        $this->assertNotNull($workItem);
        $this->assertSame('service_with_store_stock_part', (string) $workItem->transaction_type);
        $this->assertSame(250000, (int) $workItem->subtotal_rupiah);

        $this->assertDatabaseHas('work_item_service_details', [
            'work_item_id' => (string) $workItem->id,
            'service_name' => 'Servis Rem Browser Contract',
            'service_price_rupiah' => 210000,
            'part_source' => 'none',
        ]);

        $storeStockLine = DB::table('work_item_store_stock_lines')
            ->where('work_item_id', (string) $workItem->id)
            ->first();

        $this->assertNotNull($storeStockLine);
        $this->assertSame('product-browser-contract-1', (string) $storeStockLine->product_id);
        $this->assertSame(1, (int) $storeStockLine->qty);
        $this->assertSame(40000, (int) $storeStockLine->line_total_rupiah);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => 'product-browser-contract-1',
            'movement_type' => 'stock_out',
            'source_type' => 'work_item_store_stock_line',
            'source_id' => (string) $storeStockLine->id,
            'tanggal_mutasi' => '2026-03-15',
            'qty_delta' => -1,
            'unit_cost_rupiah' => 25000,
            'total_cost_rupiah' => -25000,
        ]);

        $payment = DB::table('customer_payments')->first();

        $this->assertNotNull($payment);
        $this->assertSame(100000, (int) $payment->amount_rupiah);
        $this->assertSame('cash', (string) $payment->payment_method);
        $this->assertSame('2026-03-15', (string) $payment->paid_at);

        $this->assertDatabaseHas('customer_payment_cash_details', [
            'customer_payment_id' => (string) $payment->id,
            'amount_paid_rupiah' => 100000,
            'amount_received_rupiah' => 100000,
            'change_rupiah' => 0,
        ]);

        $this->assertDatabaseCount('payment_component_allocations', 2);

        $this->assertDatabaseHas('payment_component_allocations', [
            'customer_payment_id' => (string) $payment->id,
            'note_id' => (string) $note->id,
            'work_item_id' => (string) $workItem->id,
            'component_type' => 'service_store_stock_part',
            'component_ref_id' => (string) $storeStockLine->id,
            'component_amount_rupiah_snapshot' => 40000,
            'allocated_amount_rupiah' => 40000,
            'allocation_priority' => 1,
        ]);

        $this->assertDatabaseHas('payment_component_allocations', [
            'customer_payment_id' => (string) $payment->id,
            'note_id' => (string) $note->id,
            'work_item_id' => (string) $workItem->id,
            'component_type' => 'service_fee',
            'component_ref_id' => (string) $workItem->id,
            'component_amount_rupiah_snapshot' => 210000,
            'allocated_amount_rupiah' => 60000,
            'allocation_priority' => 2,
        ]);

        $this->assertDatabaseCount('payment_allocations', 0);

        $this->assertDatabaseHas('note_history_projection', [
            'note_id' => (string) $note->id,
            'note_state' => 'open',
            'customer_name' => 'Budi Browser Contract',
            'customer_name_normalized' => 'budi browser contract',
            'customer_phone' => '08123',
            'total_rupiah' => 250000,
            'allocated_rupiah' => 100000,
            'refunded_rupiah' => 0,
            'net_paid_rupiah' => 100000,
            'outstanding_rupiah' => 150000,
        ]);
    }

}
