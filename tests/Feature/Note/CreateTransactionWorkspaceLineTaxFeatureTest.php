<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class CreateTransactionWorkspaceLineTaxFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_can_store_product_line_with_percent_tax(): void
    {
        $this->loginAsKasir();

        $user = User::query()->create([
            'name' => 'Kasir Line Tax',
            'email' => 'line-tax@example.test',
            'password' => 'password',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => 'kasir',
        ]);

        DB::table('products')->insert([
            'id' => 'product-tax-1',
            'kode_barang' => 'KB-TAX-001',
            'nama_barang' => 'Oli Pajak',
            'merek' => 'Federal',
            'ukuran' => null,
            'harga_jual' => 20000,
        ]);

        DB::table('product_inventory')->insert([
            'product_id' => 'product-tax-1',
            'qty_on_hand' => 10,
        ]);

        DB::table('product_inventory_costing')->insert([
            'product_id' => 'product-tax-1',
            'avg_cost_rupiah' => 10000,
            'inventory_value_rupiah' => 100000,
        ]);

        $response = $this->actingAs($user)->post(route('notes.workspace.store'), [
            'idempotency_key' => 'create-workspace-line-tax-idem-001',
            'note' => [
                'customer_name' => 'Budi Pajak',
                'customer_phone' => '08123',
                'transaction_date' => '2026-03-15',
            ],
            'items' => [[
                'entry_mode' => 'product',
                'description' => null,
                'part_source' => 'store_stock',
                'service' => [
                    'name' => null,
                    'price_rupiah' => null,
                    'notes' => null,
                ],
                'product_lines' => [[
                    'product_id' => 'product-tax-1',
                    'qty' => 2,
                    'unit_price_rupiah' => 20000,
                    'tax_input' => '11%',
                ]],
                'external_purchase_lines' => [],
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
            'customer_name' => 'Budi Pajak',
            'total_rupiah' => 44400,
        ]);

        $this->assertDatabaseHas('work_items', [
            'id' => $workItemId,
            'note_id' => $noteId,
            'transaction_type' => 'store_stock_sale_only',
            'subtotal_rupiah' => 44400,
        ]);

        $this->assertDatabaseHas('work_item_store_stock_lines', [
            'id' => $storeStockLineId,
            'work_item_id' => $workItemId,
            'product_id' => 'product-tax-1',
            'qty' => 2,
            'base_total_rupiah' => 40000,
            'tax_input' => '11%',
            'tax_mode' => 'percent',
            'tax_rate_basis_points' => 1100,
            'tax_amount_rupiah' => 4400,
            'line_total_rupiah' => 44400,
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => 'product-tax-1',
            'movement_type' => 'stock_out',
            'source_type' => 'work_item_store_stock_line',
            'source_id' => $storeStockLineId,
            'tanggal_mutasi' => '2026-03-15',
            'qty_delta' => -2,
            'unit_cost_rupiah' => 10000,
            'total_cost_rupiah' => -20000,
        ]);
    }
}
