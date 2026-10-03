<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Support\Facades\DB;

trait SeedsTransferredProductMergeFixture
{
    use SeedsReceivedSupplierInvoiceRevisionMatrixFixture;

    private function seedTransferredMerge(): string
    {
        $this->seedReceivedInvoiceBase();
        $this->seedReplacementProduct();
        DB::table('products')->where('id', 'product-2')->update(['nama_barang' => 'Ban Luar Canonical']);
        $this->seedPayment();
        $actor = $this->loginAsAuthorizedAdmin();
        DB::table('products')->where('id', 'product-1')->update(['deleted_at' => now()]);
        foreach (['product-1' => -2, 'product-2' => 2] as $product => $qty) {
            DB::table('inventory_movements')->insert([
                'id' => 'merge-'.$product, 'product_id' => $product,
                'movement_type' => $qty < 0 ? 'stock_out' : 'stock_in',
                'source_type' => 'product_master_merge', 'source_id' => 'prior-transfer',
                'tanggal_mutasi' => '2026-03-17', 'qty_delta' => $qty,
                'unit_cost_rupiah' => 10000, 'total_cost_rupiah' => $qty * 10000,
            ]);
        }
        $this->setProduct1Projection(0, 0);
        DB::table('product_inventory')->insert(['product_id' => 'product-2', 'qty_on_hand' => 2]);
        DB::table('product_inventory_costing')->insert(['product_id' => 'product-2', 'avg_cost_rupiah' => 10000, 'inventory_value_rupiah' => 20000]);
        DB::table('supplier_invoice_versions')->insert([
            'id' => 'original-version', 'supplier_invoice_id' => 'invoice-1', 'revision_no' => 1,
            'event_name' => 'supplier_invoice_created', 'changed_at' => '2026-03-15 00:00:00',
            'snapshot_json' => json_encode(['lines' => DB::table('supplier_invoice_lines')->get()->toArray()], JSON_THROW_ON_ERROR),
        ]);
        return (string) $actor->getAuthIdentifier();
    }
}
