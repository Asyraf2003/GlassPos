<?php

declare(strict_types=1);

namespace Tests\Feature\Procurement;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\SeedsReceivedSupplierInvoiceRevisionMatrixFixture;
use Tests\TestCase;

final class SupplierInvoiceLegacyMetadataFeatureTest extends TestCase
{
    use RefreshDatabase, SeedsReceivedSupplierInvoiceRevisionMatrixFixture;

    public function test_metadata_correction_preserves_active_line_and_all_economic_state(): void
    {
        $this->assertMetadataCorrection(false);
    }

    public function test_metadata_correction_preserves_inactive_historical_line_and_all_economic_state(): void
    {
        $this->assertMetadataCorrection(true);
    }

    public function test_edit_and_detail_render_historical_snapshot_after_master_is_renamed_and_deleted(): void
    {
        $this->seedReceivedInvoiceBase();
        $this->loginAsAuthorizedAdmin();
        $this->retireProduct();
        $this->get(route('admin.procurement.supplier-invoices.revise', ['supplierInvoiceId' => 'invoice-1']))
            ->assertOk()->assertSee('KB-001')->assertSee('Ban Luar')->assertSee('Historis')
            ->assertDontSee('CURRENT MASTER NAME');
        $this->get(route('admin.procurement.supplier-invoices.show', ['supplierInvoiceId' => 'invoice-1']))
            ->assertOk()->assertSee('Ban Luar')->assertDontSee('CURRENT MASTER NAME');
    }

    public function test_inactive_product_is_absent_from_new_selection_options(): void
    {
        $this->seedReceivedInvoiceBase();
        $this->retireProduct();
        $this->assertSame([], app(\App\Application\Procurement\Services\SupplierInvoiceProductOptionsData::class)->findAll());
        $this->loginAsAuthorizedAdmin();
        foreach ([['q' => 'KB-001'], ['ids' => ['product-1']]] as $query) {
            $this->getJson(route('admin.procurement.products.lookup', $query))->assertOk()->assertJsonCount(0, 'data.rows');
        }
    }

    public function test_changed_reference_to_inactive_product_is_rejected_atomically(): void
    {
        $this->seedReceivedInvoiceBase();
        $this->seedReplacementProduct();
        $this->loginAsAuthorizedAdmin();
        DB::table('products')->where('id', 'product-2')->update(['deleted_at' => now()]);
        $before = $this->economicState();
        $payload = $this->payload();
        $payload['lines'][0]['product_id'] = 'product-2';
        $this->submit($payload)->assertSessionHasErrors('supplier_invoice');
        $this->assertSame($before, $this->economicState());
        $this->assertDatabaseCount('supplier_invoice_versions', 0);
    }

    public function test_new_line_cannot_reuse_inactive_reference_without_current_line_identity(): void
    {
        $this->seedReceivedInvoiceBase();
        $this->loginAsAuthorizedAdmin();
        $this->retireProduct();
        $payload = $this->payload();
        $payload['lines'][0]['previous_line_id'] = null;
        $this->submit($payload)->assertSessionHasErrors('supplier_invoice');
        $this->assertDatabaseCount('supplier_invoice_versions', 0);
    }

    public function test_stale_metadata_submit_does_not_overwrite_new_revision(): void
    {
        $this->seedReceivedInvoiceBase();
        $this->loginAsAuthorizedAdmin();
        $this->submit($this->payload())->assertSessionHasNoErrors();
        $before = $this->economicState();
        $payload = $this->payload();
        $payload['nomor_faktur'] = 'STALE-EDIT';
        $this->submit($payload)->assertSessionHasErrors('supplier_invoice');
        $this->assertSame($before, $this->economicState());
        $this->assertDatabaseHas('supplier_invoices', ['id' => 'invoice-1', 'nomor_faktur' => 'INV-CORRECTED', 'last_revision_no' => 2]);
        $this->assertDatabaseCount('supplier_invoice_versions', 1);
    }

    public function test_tax_residue_metadata_does_not_require_reconfirmation_or_revalue(): void
    {
        $this->seedReceivedInvoiceBase();
        $this->loginAsAuthorizedAdmin();
        DB::table('supplier_invoices')->where('id', 'invoice-1')->update([
            'subtotal_before_tax_rupiah' => 20000, 'tax_input' => '1', 'tax_mode' => 'fixed',
            'tax_amount_rupiah' => 1, 'grand_total_rupiah' => 20001,
        ]);
        DB::table('supplier_invoice_lines')->where('id', 'invoice-line-1')->update([
            'line_subtotal_before_tax_rupiah' => 20000, 'line_total_rupiah' => 20001, 'rounding_residue_rupiah' => 1,
        ]);
        $this->retireProduct();
        $before = $this->economicState();
        $payload = $this->payload();
        $payload['tax_input'] = '1';
        $payload['tax_rounding_residue_confirmed'] = false;
        $this->submit($payload)->assertSessionHasNoErrors();
        $this->assertSame($before, $this->economicState());
    }

    public function test_economic_revision_after_metadata_still_writes_stock_delta_and_current_only_audit(): void
    {
        $this->seedReceivedInvoiceBase();
        $this->loginAsAuthorizedAdmin();
        $this->submit($this->payload())->assertSessionHasNoErrors();
        $payload = $this->payload();
        $payload['expected_revision_no'] = 2;
        $payload['lines'][0]['qty_pcs'] = 3;
        $payload['lines'][0]['line_total_rupiah'] = 30000;
        $this->submit($payload)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('inventory_movements', ['source_type' => 'supplier_invoice_revision_delta_line', 'qty_delta' => 1]);
        $this->assertDatabaseHas('product_inventory', ['product_id' => 'product-1', 'qty_on_hand' => 3]);
        $versionsBefore = DB::table('supplier_invoice_versions')->orderBy('revision_no')->get()->map(fn ($row) => (array) $row)->all();
        $this->retireProduct();
        $payload['expected_revision_no'] = 3;
        $payload['nomor_faktur'] = 'THIRD-CORRECTION';
        $payload['lines'][0]['previous_line_id'] = DB::table('supplier_invoice_lines')->where('is_current', true)->value('id');
        $before = $this->economicState();
        $this->submit($payload)->assertSessionHasNoErrors();
        $this->assertSame($before, $this->economicState());
        $this->assertSame($versionsBefore, DB::table('supplier_invoice_versions')->where('revision_no', '<', 4)->orderBy('revision_no')->get()->map(fn ($row) => (array) $row)->all());
        $event = DB::table('audit_events')->where('aggregate_id', 'invoice-1')->get()->first(
            fn ($event) => json_decode($event->metadata_json, true)['revision_no'] === 4
        );
        $snapshot = json_decode(DB::table('audit_event_snapshots')->where('audit_event_id', $event->id)->where('snapshot_kind', 'before')->value('payload_json'), true);
        $this->assertCount(1, $snapshot['lines']);
        $this->assertSame(3, $snapshot['lines'][0]['qty_pcs']);
        $this->assertSame('Ban Luar', $snapshot['lines'][0]['product_nama_barang_snapshot']);
    }

    public function test_unchanged_legacy_line_survives_addition_of_active_line(): void
    {
        $this->seedReceivedInvoiceBase();
        $this->seedReplacementProduct();
        $this->loginAsAuthorizedAdmin();
        $this->retireProduct();
        $payload = $this->payload();
        $payload['lines'][] = ['previous_line_id' => null, 'line_no' => 2, 'product_id' => 'product-2', 'qty_pcs' => 1, 'line_total_rupiah' => 15000];
        $this->submit($payload)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('supplier_invoice_lines', ['is_current' => true, 'product_id' => 'product-1', 'product_nama_barang_snapshot' => 'Ban Luar', 'qty_pcs' => 2]);
        $this->assertSame(1, DB::table('inventory_movements')->where('product_id', 'product-1')->count());
        $this->assertDatabaseHas('inventory_movements', ['product_id' => 'product-2', 'qty_delta' => 1, 'source_type' => 'supplier_invoice_revision_delta_line']);
    }

    public function test_appended_inactive_line_and_forged_previous_id_are_rejected(): void
    {
        $this->seedReceivedInvoiceBase();
        $this->seedReplacementProduct();
        $this->loginAsAuthorizedAdmin();
        DB::table('products')->where('id', 'product-2')->update(['deleted_at' => now()]);
        foreach ([null, 'invoice-line-1', 'foreign-line'] as $previous) {
            $payload = $this->payload();
            $payload['lines'][] = ['previous_line_id' => $previous, 'line_no' => 2, 'product_id' => 'product-2', 'qty_pcs' => 1, 'line_total_rupiah' => 15000];
            $this->submit($payload)->assertSessionHasErrors('supplier_invoice');
        }
        $this->assertDatabaseCount('supplier_invoice_versions', 0);
        $this->assertDatabaseCount('inventory_movements', 1);
    }

    public function test_direct_handler_and_create_factory_enforce_reference_boundary(): void
    {
        $this->seedReceivedInvoiceBase();
        $this->retireProduct();
        $payload = $this->payload();
        $result = app(\App\Application\Procurement\UseCases\UpdateSupplierInvoiceHandler::class)->handle(
            'invoice-1', 'DIRECT-CORRECTION', 'PT Sumber Makmur', '2026-03-15', $payload['lines'], 1, 'Koreksi nomor faktur',
        );
        $this->assertFalse($result->isFailure());
        $this->expectException(\App\Core\Shared\Exceptions\DomainException::class);
        app(\App\Application\Procurement\Services\SupplierInvoiceFactory::class)->makeLines($payload['lines']);
    }

    private function assertMetadataCorrection(bool $inactive): void
    {
        $this->seedReceivedInvoiceBase();
        $this->seedPayment();
        $actor = $this->loginAsAuthorizedAdmin();
        if ($inactive) {
            $this->retireProduct();
            // Model a merged-away identity with no stock remaining: metadata must not re-issue it.
            $this->setProduct1Projection(0, 0);
        } else {
            DB::table('products')->where('id', 'product-1')->update(['nama_barang' => 'CURRENT MASTER NAME']);
        }
        $before = $this->economicState();
        $this->submit($this->payload())->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertSame($before, $this->economicState());
        $this->assertDatabaseHas('supplier_invoices', ['id' => 'invoice-1', 'nomor_faktur' => 'INV-CORRECTED', 'grand_total_rupiah' => 20000, 'last_revision_no' => 2]);
        $version = DB::table('supplier_invoice_versions')->sole();
        $this->assertSame((string) $actor->getAuthIdentifier(), $version->changed_by_actor_id);
        $this->assertSame('Koreksi nomor faktur', $version->change_reason);
        $snapshot = json_decode($version->snapshot_json, true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('Ban Luar', $snapshot['lines'][0]['product_nama_barang_snapshot']);
        $this->assertSame('invoice-line-1', $snapshot['lines'][0]['id']);
        $snapshots = DB::table('audit_event_snapshots')->pluck('payload_json', 'snapshot_kind');
        $old = json_decode($snapshots['before'], true, 512, JSON_THROW_ON_ERROR);
        $new = json_decode($snapshots['after'], true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('INV-SUP-001', $old['nomor_faktur']);
        $this->assertSame('INV-CORRECTED', $new['nomor_faktur']);
        $this->assertSame($old['lines'], $new['lines']);
    }

    private function economicState(): array
    {
        $invoice = (array) DB::table('supplier_invoices')->where('id', 'invoice-1')->first();
        // Generated uniqueness marker follows the corrected number; it is metadata too.
        unset($invoice['nomor_faktur'], $invoice['nomor_faktur_normalized'], $invoice['active_nomor_faktur_normalized'], $invoice['last_revision_no']);
        $state = ['invoice_economics' => $invoice];
        foreach (['supplier_invoice_lines', 'supplier_receipts', 'supplier_receipt_lines', 'inventory_movements', 'product_inventory', 'product_inventory_costing', 'supplier_payments'] as $table) {
            $state[$table] = DB::table($table)->get()->map(fn ($row) => (array) $row)->all();
        }
        return $state;
    }

    private function retireProduct(): void
    {
        DB::table('products')->where('id', 'product-1')->update(['deleted_at' => now(), 'nama_barang' => 'CURRENT MASTER NAME']);
    }

    private function submit(array $payload): \Illuminate\Testing\TestResponse
    {
        return $this->from(route('admin.procurement.supplier-invoices.revise', ['supplierInvoiceId' => 'invoice-1']))
            ->put(route('admin.procurement.supplier-invoices.update', ['supplierInvoiceId' => 'invoice-1']), $payload);
    }

    private function payload(): array
    {
        return [
            'expected_revision_no' => 1, 'change_reason' => 'Koreksi nomor faktur',
            'nomor_faktur' => 'INV-CORRECTED', 'nama_pt_pengirim' => 'PT Sumber Makmur', 'tanggal_pengiriman' => '2026-03-15',
            'lines' => [['previous_line_id' => 'invoice-line-1', 'line_no' => 1, 'product_id' => 'product-1', 'qty_pcs' => 2, 'line_total_rupiah' => 20000]],
        ];
    }
}
