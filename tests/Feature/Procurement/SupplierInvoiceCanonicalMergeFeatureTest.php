<?php

declare(strict_types=1);

namespace Tests\Feature\Procurement;

use App\Application\ProductCatalog\UseCases\AdoptTransferredProductMergeHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsTransferredProductMergeFixture;
use Tests\TestCase;

final class SupplierInvoiceCanonicalMergeFeatureTest extends TestCase
{
    use RefreshDatabase, SeedsTransferredProductMergeFixture;

    public function test_adoption_revises_current_identity_without_replaying_inventory(): void
    {
        $actor = $this->seedTransferredMerge();
        app(\App\Application\Procurement\Services\SupplierInvoiceListProjectionService::class)->syncInvoice('invoice-1');
        $oldVersion = DB::table('supplier_invoice_versions')->where('id', 'original-version')->value('snapshot_json');
        $headerBefore = (array) DB::table('supplier_invoices')->where('id', 'invoice-1')->sole();
        $before = [];
        foreach (['inventory_movements', 'product_inventory', 'product_inventory_costing', 'inventory_cost_adjustments', 'supplier_payments', 'supplier_receipts', 'supplier_receipt_lines'] as $table) {
            $before[$table] = DB::table($table)->get()->toJson();
        }
        $handler = app(AdoptTransferredProductMergeHandler::class);
        $changed = $handler->handle('merge-1', 'product-1', 'product-2', $actor, 'Duplicate identity of same physical product', 'prior-transfer');
        $this->assertSame(1, $changed);
        $this->assertDatabaseHas('product_identity_merges', ['id' => 'merge-1', 'source_product_id' => 'product-1', 'canonical_product_id' => 'product-2', 'prior_stock_transfer_source_id' => 'prior-transfer']);
        $this->assertDatabaseHas('supplier_invoice_lines', ['supplier_invoice_id' => 'invoice-1', 'is_current' => true, 'product_id' => 'product-2', 'qty_pcs' => 2, 'line_total_rupiah' => 20000]);
        $this->assertDatabaseHas('supplier_invoice_lines', ['id' => 'invoice-line-1', 'is_current' => false, 'product_id' => 'product-1', 'qty_pcs' => 2]);
        $this->assertDatabaseHas('supplier_invoices', ['id' => 'invoice-1', 'grand_total_rupiah' => 20000, 'last_revision_no' => 2]);
        $headerAfter = (array) DB::table('supplier_invoices')->where('id', 'invoice-1')->sole();
        unset($headerBefore['last_revision_no'], $headerAfter['last_revision_no']);
        $this->assertSame($headerBefore, $headerAfter);
        $this->assertDatabaseHas('supplier_invoice_list_projection', ['supplier_invoice_id' => 'invoice-1', 'outstanding_rupiah' => 15000]);
        foreach ($before as $table => $rows) {
            $this->assertSame($rows, DB::table($table)->get()->toJson(), $table);
        }
        $relation = app(\App\Ports\Out\ProductCatalog\ProductIdentityMergePort::class)->findBySource('product-1');
        $this->assertSame('product-2', $relation?->canonicalProductId);
        $event = DB::table('audit_events')->where('aggregate_type', 'supplier_invoice')->sole();
        $this->assertSame('merge-1', $event->correlation_id);
        $this->assertSame('Duplicate identity of same physical product', $event->reason);
        $this->assertSame($actor, $event->actor_id);
        $metadata = json_decode($event->metadata_json, true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(1, $metadata['before_revision_no']);
        $this->assertSame(2, $metadata['after_revision_no']);
        $snapshots = DB::table('audit_event_snapshots')->where('audit_event_id', $event->id)->pluck('payload_json', 'snapshot_kind');
        $this->assertSame('product-1', json_decode($snapshots['before'], true)['lines'][0]['product_id']);
        $this->assertSame('product-2', json_decode($snapshots['after'], true)['lines'][0]['product_id']);
        $this->assertSame(0, $handler->handle('merge-1', 'product-1', 'product-2', $actor, 'Duplicate identity of same physical product', 'prior-transfer'));
        $this->assertDatabaseCount('product_identity_merges', 1);
        $this->assertDatabaseCount('supplier_invoice_versions', 2);
        $this->assertSame($oldVersion, DB::table('supplier_invoice_versions')->where('id', 'original-version')->value('snapshot_json'));
    }

    public function test_identity_adoption_preserves_unrelated_stale_projection_values(): void
    {
        $actor = $this->seedTransferredMerge();
        app(\App\Application\Procurement\Services\SupplierInvoiceListProjectionService::class)->syncInvoice('invoice-1');
        DB::table('supplier_invoice_list_projection')->update(['total_received_qty' => 50]);
        DB::table('supplier_list_projection')->update(['invoice_count' => 14, 'last_shipment_date' => '2026-07-21']);
        $invoiceBefore = (array) DB::table('supplier_invoice_list_projection')->sole();
        $supplierBefore = DB::table('supplier_list_projection')->get()->toJson();
        $handler = app(AdoptTransferredProductMergeHandler::class);
        $this->assertSame(1, $handler->handle('merge-1', 'product-1', 'product-2', $actor, 'Explicit duplicate identity', 'prior-transfer'));
        $invoiceAfter = (array) DB::table('supplier_invoice_list_projection')->sole();
        $this->assertSame(2, $invoiceAfter['last_revision_no']);
        unset($invoiceBefore['last_revision_no'], $invoiceAfter['last_revision_no']);
        $this->assertSame($invoiceBefore, $invoiceAfter);
        $this->assertSame($supplierBefore, DB::table('supplier_list_projection')->get()->toJson());
        $this->assertDatabaseHas('supplier_invoice_lines', ['is_current' => true, 'product_id' => 'product-2']);
        $this->assertSame(0, $handler->handle('merge-1', 'product-1', 'product-2', $actor, 'Explicit duplicate identity', 'prior-transfer'));
        $this->assertSame($supplierBefore, DB::table('supplier_list_projection')->get()->toJson());
    }

    public function test_ordinary_economic_edit_after_adoption_moves_only_canonical_stock(): void
    {
        $actor = $this->seedTransferredMerge();
        app(\App\Application\Procurement\Services\SupplierInvoiceListProjectionService::class)->syncInvoice('invoice-1');
        app(AdoptTransferredProductMergeHandler::class)->handle('merge-1', 'product-1', 'product-2', $actor, 'Explicit duplicate identity', 'prior-transfer');
        $line = DB::table('supplier_invoice_lines')->where('is_current', true)->sole();
        $this->put(route('admin.procurement.supplier-invoices.update', ['supplierInvoiceId' => 'invoice-1']), [
            'expected_revision_no' => 2, 'change_reason' => 'Increase canonical quantity',
            'nomor_faktur' => 'INV-SUP-001', 'nama_pt_pengirim' => 'PT Sumber Makmur', 'tanggal_pengiriman' => '2026-03-15',
            'lines' => [['previous_line_id' => $line->id, 'line_no' => 1, 'product_id' => 'product-2', 'qty_pcs' => 5, 'line_total_rupiah' => 50000]],
        ])->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertDatabaseHas('inventory_movements', ['product_id' => 'product-2', 'source_type' => 'supplier_invoice_revision_delta_line', 'qty_delta' => 3]);
        $this->assertSame(0, DB::table('inventory_movements')->where('product_id', 'product-1')->where('source_type', 'supplier_invoice_revision_delta_line')->count());
        $this->assertDatabaseHas('product_inventory', ['product_id' => 'product-1', 'qty_on_hand' => 0]);
        $this->assertDatabaseHas('product_inventory', ['product_id' => 'product-2', 'qty_on_hand' => 5]);
        $this->assertDatabaseHas('supplier_invoices', ['id' => 'invoice-1', 'last_revision_no' => 3]);
    }

    public function test_all_affected_invoices_are_revised_but_canonical_and_unrelated_are_untouched(): void
    {
        $actor = $this->seedTransferredMerge();
        app(\App\Application\Procurement\Services\SupplierInvoiceListProjectionService::class)->syncInvoice('invoice-1');
        foreach (['invoice-2' => 'product-1', 'invoice-3' => 'product-2'] as $id => $product) {
            $invoice = (array) DB::table('supplier_invoices')->where('id', 'invoice-1')->sole();
            unset($invoice['active_nomor_faktur_normalized']);
            $invoice['id'] = $id;
            $invoice['nomor_faktur'] = $invoice['nomor_faktur_normalized'] = $id;
            DB::table('supplier_invoices')->insert($invoice);
            $line = (array) DB::table('supplier_invoice_lines')->where('id', 'invoice-line-1')->sole();
            $line['id'] = $id.'-line';
            $line['supplier_invoice_id'] = $id;
            $line['product_id'] = $product;
            DB::table('supplier_invoice_lines')->insert($line);
        }
        $untouched = DB::table('supplier_invoice_lines')->where('supplier_invoice_id', 'invoice-3')->get()->toJson();
        $this->assertSame(2, app(AdoptTransferredProductMergeHandler::class)->handle('merge-1', 'product-1', 'product-2', $actor, 'Explicit duplicate identity', 'prior-transfer'));
        $this->assertSame(0, DB::table('supplier_invoice_lines')->where('is_current', true)->where('product_id', 'product-1')->count());
        $this->assertSame($untouched, DB::table('supplier_invoice_lines')->where('supplier_invoice_id', 'invoice-3')->get()->toJson());
    }
}
