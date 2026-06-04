<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use App\Core\Note\WorkItem\ServiceDetail;
use App\Core\Note\WorkItem\WorkItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class TransactionWorkspaceServiceCatalogSyncFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_service_only_syncs_new_service_catalog_item(): void
    {
        $this->loginAsKasir();

        $this->post(route('notes.workspace.store'), $this->payload([
            'service' => ['name' => 'Skir Klep Custom', 'price_rupiah' => 95000],
        ]))->assertRedirect(route('cashier.notes.index'));

        $this->assertDatabaseHas('service_catalog_items', [
            'normalized_name' => 'skir klep custom',
            'default_price_rupiah' => 95000,
        ]);
    }

    public function test_create_service_does_not_update_existing_catalog_default_price(): void
    {
        $this->loginAsKasir();
        $this->seedService('svc-1', 'Tune Up Racing', 'tune up racing', 120000);

        $this->post(route('notes.workspace.store'), $this->payload([
            'service' => ['name' => 'tune up racing', 'price_rupiah' => 999000],
        ]))->assertRedirect(route('cashier.notes.index'));

        $this->assertDatabaseHas('service_catalog_items', [
            'normalized_name' => 'tune up racing',
            'default_price_rupiah' => 120000,
        ]);
    }

    public function test_create_service_store_stock_syncs_computed_service_fee(): void
    {
        $this->loginAsKasir();
        $this->seedProduct();

        $this->post(route('notes.workspace.store'), $this->payload([
            'pricing_mode' => 'package_auto_split',
            'package_total_rupiah' => 150000,
            'service' => ['name' => 'Paket Kopling Baru', 'price_rupiah' => 0],
            'product_lines' => [[
                'product_id' => 'product-service-catalog-1',
                'qty' => 1,
                'unit_price_rupiah' => 40000,
            ]],
        ]))->assertRedirect(route('cashier.notes.index'));

        $this->assertDatabaseHas('service_catalog_items', [
            'normalized_name' => 'paket kopling baru',
            'default_price_rupiah' => 110000,
        ]);
    }

    public function test_edit_revision_syncs_new_service_catalog_item(): void
    {
        $this->loginAsKasir();
        $this->seedOpenNote();

        $this->get(route('cashier.notes.show', ['noteId' => 'note-service-catalog-1']))->assertOk();
        $this->patch(route('cashier.notes.workspace.update', ['noteId' => 'note-service-catalog-1']), $this->payload([
            'service' => ['name' => 'Setting In Besar', 'price_rupiah' => 85000],
        ]))->assertRedirect(route('cashier.notes.show', ['noteId' => 'note-service-catalog-1']));

        $this->assertDatabaseHas('service_catalog_items', [
            'normalized_name' => 'setting in besar',
            'default_price_rupiah' => 85000,
        ]);
    }

    private function payload(array $item): array
    {
        return [
            'note' => [
                'customer_name' => 'Budi Service Catalog',
                'customer_phone' => '08123',
                'transaction_date' => date('Y-m-d'),
            ],
            'items' => [array_replace_recursive([
                'entry_mode' => 'service',
                'part_source' => 'none',
                'service' => ['name' => 'Servis A', 'price_rupiah' => 50000, 'notes' => ''],
                'product_lines' => [['product_id' => '', 'qty' => '', 'unit_price_rupiah' => '']],
                'external_purchase_lines' => [['label' => '', 'qty' => '', 'unit_cost_rupiah' => '']],
            ], $item)],
            'inline_payment' => ['decision' => 'skip', 'paid_at' => '2026-03-15'],
        ];
    }

    private function seedProduct(): void
    {
        DB::table('products')->insert([
            'id' => 'product-service-catalog-1',
            'nama_barang' => 'Kampas Kopling',
            'merek' => 'Federal',
            'harga_jual' => 40000,
        ]);
        DB::table('product_inventory')->insert(['product_id' => 'product-service-catalog-1', 'qty_on_hand' => 5]);
        DB::table('product_inventory_costing')->insert([
            'product_id' => 'product-service-catalog-1',
            'avg_cost_rupiah' => 25000,
            'inventory_value_rupiah' => 125000,
        ]);
    }

    private function seedOpenNote(): void
    {
        DB::table('notes')->insert([
            'id' => 'note-service-catalog-1',
            'customer_name' => 'Budi Lama',
            'transaction_date' => date('Y-m-d'),
            'total_rupiah' => 50000,
            'note_state' => 'open',
        ]);
        DB::table('work_items')->insert([
            'id' => 'wi-service-catalog-1',
            'note_id' => 'note-service-catalog-1',
            'line_no' => 1,
            'transaction_type' => WorkItem::TYPE_SERVICE_ONLY,
            'status' => WorkItem::STATUS_OPEN,
            'subtotal_rupiah' => 50000,
        ]);
        DB::table('work_item_service_details')->insert([
            'work_item_id' => 'wi-service-catalog-1',
            'service_name' => 'Servis Lama',
            'service_price_rupiah' => 50000,
            'part_source' => ServiceDetail::PART_SOURCE_NONE,
        ]);
    }

    private function seedService(string $id, string $name, string $normalized, int $price): void
    {
        DB::table('service_catalog_items')->insert([
            'id' => $id,
            'name' => $name,
            'normalized_name' => $normalized,
            'default_price_rupiah' => $price,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
