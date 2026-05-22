<?php

declare(strict_types=1);

namespace Database\Seeders\CreateOnly;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

final class CreateSupplierProcurementSeeder extends Seeder
{
    public function run(): void
    {
        $this->assertLocalOrTesting();

        $suppliers = DB::table('suppliers')
            ->orderBy('id')
            ->limit(24)
            ->get();

        $products = DB::table('products')
            ->orderBy('id')
            ->limit(96)
            ->get();

        if ($suppliers->count() < 6) {
            throw new RuntimeException('CreateSupplierProcurementSeeder requires at least 6 suppliers.');
        }

        if ($products->count() < 24) {
            throw new RuntimeException('CreateSupplierProcurementSeeder requires at least 24 products.');
        }

        $now = now()->format('Y-m-d H:i:s');
        $created = [
            'supplier_invoices' => 0,
            'supplier_invoice_lines' => 0,
            'supplier_receipts' => 0,
            'supplier_receipt_lines' => 0,
        ];

        DB::transaction(function () use ($suppliers, $products, $now, &$created): void {
            for ($invoiceNo = 1; $invoiceNo <= 24; $invoiceNo++) {
                $supplier = $suppliers[($invoiceNo - 1) % $suppliers->count()];
                $invoiceId = sprintf('seed-supplier-invoice-%04d', $invoiceNo);
                $receiptId = sprintf('seed-supplier-receipt-%04d', $invoiceNo);

                $tanggalPengiriman = sprintf('2026-05-%02d', (($invoiceNo - 1) % 24) + 1);
                $jatuhTempo = sprintf('2026-06-%02d', (($invoiceNo - 1) % 24) + 1);
                $tanggalTerima = sprintf('2026-05-%02d', (($invoiceNo + 1) % 24) + 1);

                $invoiceLines = [];

                for ($lineNo = 1; $lineNo <= 3; $lineNo++) {
                    $product = $products[(($invoiceNo - 1) * 3 + ($lineNo - 1)) % $products->count()];
                    $qtyPcs = (($invoiceNo + $lineNo) % 5) + 1;
                    $unitCostRupiah = 7000 + ($invoiceNo * 250) + ($lineNo * 1000);
                    $lineTotalRupiah = $qtyPcs * $unitCostRupiah;
                    $lineId = sprintf('seed-supplier-invoice-line-%04d-%02d', $invoiceNo, $lineNo);

                    $invoiceLines[] = [
                        'id' => $lineId,
                        'supplier_invoice_id' => $invoiceId,
                        'line_no' => $lineNo,
                        'product_id' => (string) $product->id,
                        'product_kode_barang_snapshot' => $this->stringFromRow(
                            $product,
                            ['kode_barang', 'product_code', 'code', 'sku'],
                            sprintf('SEED-PRODUCT-%04d-%02d', $invoiceNo, $lineNo)
                        ),
                        'product_nama_barang_snapshot' => $this->stringFromRow(
                            $product,
                            ['nama_barang', 'product_name', 'name', 'nama'],
                            sprintf('Seed Product %04d-%02d', $invoiceNo, $lineNo)
                        ),
                        'product_merek_snapshot' => $this->nullableStringFromRow(
                            $product,
                            ['merek', 'brand', 'merk']
                        ),
                        'product_ukuran_snapshot' => $this->nullableIntFromRow(
                            $product,
                            ['ukuran', 'size', 'product_ukuran']
                        ),
                        'qty_pcs' => $qtyPcs,
                        'line_total_rupiah' => $lineTotalRupiah,
                        'unit_cost_rupiah' => $unitCostRupiah,
                        'revision_no' => 1,
                        'is_current' => true,
                        'source_line_id' => null,
                        'superseded_by_line_id' => null,
                        'superseded_at' => null,
                    ];
                }

                $grandTotalRupiah = array_sum(array_column($invoiceLines, 'line_total_rupiah'));

                if ($this->insertIfMissing('supplier_invoices', $invoiceId, [
                    'id' => $invoiceId,
                    'supplier_id' => (string) $supplier->id,
                    'supplier_nama_pt_pengirim_snapshot' => $this->stringFromRow(
                        $supplier,
                        ['nama_pt_pengirim', 'nama_supplier', 'company_name', 'name', 'nama'],
                        sprintf('Seed Supplier %04d', $invoiceNo)
                    ),
                    'nomor_faktur' => sprintf('SEED-INV-%04d', $invoiceNo),
                    'nomor_faktur_normalized' => sprintf('seed-inv-%04d', $invoiceNo),
                    'document_kind' => 'invoice',
                    'lifecycle_status' => 'active',
                    'origin_supplier_invoice_id' => null,
                    'superseded_by_supplier_invoice_id' => null,
                    'tanggal_pengiriman' => $tanggalPengiriman,
                    'jatuh_tempo' => $jatuhTempo,
                    'grand_total_rupiah' => $grandTotalRupiah,
                    'voided_at' => null,
                    'void_reason' => null,
                    'last_revision_no' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])) {
                    $created['supplier_invoices']++;
                }

                foreach ($invoiceLines as $invoiceLine) {
                    if ($this->insertIfMissing('supplier_invoice_lines', (string) $invoiceLine['id'], $invoiceLine)) {
                        $created['supplier_invoice_lines']++;
                    }
                }

                if ($this->insertIfMissing('supplier_receipts', $receiptId, [
                    'id' => $receiptId,
                    'supplier_invoice_id' => $invoiceId,
                    'tanggal_terima' => $tanggalTerima,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])) {
                    $created['supplier_receipts']++;
                }

                foreach ($invoiceLines as $invoiceLine) {
                    $receiptLineId = str_replace(
                        'seed-supplier-invoice-line',
                        'seed-supplier-receipt-line',
                        (string) $invoiceLine['id']
                    );

                    if ($this->insertIfMissing('supplier_receipt_lines', $receiptLineId, [
                        'id' => $receiptLineId,
                        'supplier_receipt_id' => $receiptId,
                        'supplier_invoice_line_id' => $invoiceLine['id'],
                        'qty_diterima' => $invoiceLine['qty_pcs'],
                        'product_id_snapshot' => $invoiceLine['product_id'],
                        'product_kode_barang_snapshot' => $invoiceLine['product_kode_barang_snapshot'],
                        'product_nama_barang_snapshot' => $invoiceLine['product_nama_barang_snapshot'],
                        'product_merek_snapshot' => $invoiceLine['product_merek_snapshot'],
                        'product_ukuran_snapshot' => $invoiceLine['product_ukuran_snapshot'],
                        'unit_cost_rupiah_snapshot' => $invoiceLine['unit_cost_rupiah'],
                    ])) {
                        $created['supplier_receipt_lines']++;
                    }
                }
            }
        });

        foreach ($created as $table => $count) {
            $this->command?->info($table.' created='.$count);
        }
    }

    private function assertLocalOrTesting(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Create-only seeders may only run in local or testing environments.');
        }
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function insertIfMissing(string $table, string $id, array $values): bool
    {
        if (DB::table($table)->where('id', $id)->exists()) {
            return false;
        }

        DB::table($table)->insert($this->filterExistingColumns($table, $values));

        return true;
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function filterExistingColumns(string $table, array $values): array
    {
        $columns = array_flip(Schema::getColumnListing($table));

        return array_intersect_key($values, $columns);
    }

    /**
     * @param  list<string>  $columns
     */
    private function stringFromRow(object $row, array $columns, string $fallback): string
    {
        foreach ($columns as $column) {
            $value = $row->{$column} ?? null;

            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }

            if (is_int($value) || is_float($value)) {
                return (string) $value;
            }
        }

        return $fallback;
    }

    /**
     * @param  list<string>  $columns
     */
    private function nullableStringFromRow(object $row, array $columns): ?string
    {
        foreach ($columns as $column) {
            $value = $row->{$column} ?? null;

            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }

            if (is_int($value) || is_float($value)) {
                return (string) $value;
            }
        }

        return null;
    }

    /**
     * @param  list<string>  $columns
     */
    private function nullableIntFromRow(object $row, array $columns): ?int
    {
        foreach ($columns as $column) {
            $value = $row->{$column} ?? null;

            if (is_int($value)) {
                return $value;
            }

            if (is_numeric($value)) {
                return (int) $value;
            }
        }

        return null;
    }
}
