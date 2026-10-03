<?php

declare(strict_types=1);

namespace Tests\Feature\Procurement;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsReceivedSupplierInvoiceRevisionMatrixFixture;
use Tests\TestCase;

/** Characterization of PR #77 risk, not an approved lifecycle policy. */
final class SupplierInvoiceInactiveEconomicAuditFeatureTest extends TestCase
{
    use RefreshDatabase, SeedsReceivedSupplierInvoiceRevisionMatrixFixture;

    public function test_pr77_accepts_inactive_current_quantity_increase_and_posts_delta_to_old_identity(): void
    {
        $this->seedReceivedInvoiceBase();
        $this->seedReplacementProduct();
        $this->seedPayment();
        $actor = $this->loginAsAuthorizedAdmin();
        DB::table('supplier_invoices')->where('id', 'invoice-1')->update(['grand_total_rupiah' => 50000]);
        DB::table('supplier_invoice_lines')->where('id', 'invoice-line-1')->update(['qty_pcs' => 5, 'line_total_rupiah' => 50000]);
        DB::table('supplier_receipt_lines')->where('id', 'receipt-line-1')->update(['qty_diterima' => 5]);
        DB::table('inventory_movements')->where('id', 'movement-receipt-1')->update(['qty_delta' => 5, 'total_cost_rupiah' => 50000]);
        DB::table('products')->where('id', 'product-2')->update(['nama_barang' => 'Ban Luar', 'merek' => 'Canonical']);
        DB::table('products')->where('id', 'product-1')->update(['deleted_at' => now()]);
        // Fixture represents persisted incomplete merge state, not a merge implementation.
        foreach (['product-1' => -5, 'product-2' => 5] as $product => $qty) {
            DB::table('inventory_movements')->insert([
                'id' => 'merge-'.$product, 'product_id' => $product,
                'movement_type' => $qty < 0 ? 'stock_out' : 'stock_in',
                'source_type' => 'product_master_merge', 'source_id' => 'same-physical-product-operation',
                'tanggal_mutasi' => '2026-03-17', 'qty_delta' => $qty,
                'unit_cost_rupiah' => 10000, 'total_cost_rupiah' => $qty * 10000,
            ]);
        }
        $this->setProduct1Projection(0, 0);
        DB::table('product_inventory')->insert(['product_id' => 'product-2', 'qty_on_hand' => 5]);
        DB::table('product_inventory_costing')->insert(['product_id' => 'product-2', 'avg_cost_rupiah' => 10000, 'inventory_value_rupiah' => 50000]);

        $this->put(route('admin.procurement.supplier-invoices.update', ['supplierInvoiceId' => 'invoice-1']), [
            'expected_revision_no' => 1, 'change_reason' => 'Characterize inactive current identity risk',
            'nomor_faktur' => 'INV-SUP-001', 'nama_pt_pengirim' => 'PT Sumber Makmur', 'tanggal_pengiriman' => '2026-03-15',
            'lines' => [['previous_line_id' => 'invoice-line-1', 'line_no' => 1, 'product_id' => 'product-1', 'qty_pcs' => 10, 'line_total_rupiah' => 100000]],
        ])->assertSessionHasNoErrors()->assertSessionHas('success');

        $this->assertDatabaseHas('inventory_movements', ['product_id' => 'product-1', 'source_type' => 'supplier_invoice_revision_delta_line', 'qty_delta' => 5, 'total_cost_rupiah' => 50000]);
        $this->assertSame(0, DB::table('inventory_movements')->where('product_id', 'product-2')->where('source_type', 'supplier_invoice_revision_delta_line')->count());
        $this->assertDatabaseHas('product_inventory', ['product_id' => 'product-1', 'qty_on_hand' => 5]);
        $this->assertDatabaseHas('product_inventory', ['product_id' => 'product-2', 'qty_on_hand' => 5]);
        foreach (['product-1', 'product-2'] as $product) {
            $this->assertDatabaseHas('product_inventory_costing', ['product_id' => $product, 'avg_cost_rupiah' => 10000, 'inventory_value_rupiah' => 50000]);
        }
        $this->assertDatabaseHas('supplier_invoices', ['id' => 'invoice-1', 'grand_total_rupiah' => 100000, 'last_revision_no' => 2]);
        $this->assertSame(5000, (int) DB::table('supplier_payments')->sum('amount_rupiah'));
        $this->assertDatabaseHas('supplier_invoice_list_projection', ['supplier_invoice_id' => 'invoice-1', 'outstanding_rupiah' => 95000]);
        $version = DB::table('supplier_invoice_versions')->sole();
        $this->assertSame((string) $actor->getAuthIdentifier(), $version->changed_by_actor_id);
        $this->assertSame('Characterize inactive current identity risk', $version->change_reason);
        $after = json_decode($version->snapshot_json, true, 512, JSON_THROW_ON_ERROR);
        $before = json_decode(DB::table('audit_event_snapshots')->where('snapshot_kind', 'before')->value('payload_json'), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('product-1', $after['lines'][0]['product_id']);
        $this->assertSame(10, $after['lines'][0]['qty_pcs']);
        $this->assertSame('product-1', $before['lines'][0]['product_id']);
        $this->assertSame(5, $before['lines'][0]['qty_pcs']);
        $this->assertDatabaseHas('supplier_invoice_lines', ['id' => 'invoice-line-1', 'is_current' => false, 'product_id' => 'product-1', 'qty_pcs' => 5]);
        $this->assertDatabaseHas('supplier_receipt_lines', ['id' => 'receipt-line-1', 'qty_diterima' => 5]);
        $this->assertNotNull(DB::table('products')->where('id', 'product-1')->value('deleted_at'));
    }
}
