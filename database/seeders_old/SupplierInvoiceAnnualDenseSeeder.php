<?php

declare(strict_types=1);

namespace Database\Seeders;

use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class SupplierInvoiceAnnualDenseSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = DB::table('suppliers')
            ->select('id', 'nama_pt_pengirim')
            ->orderBy('nama_pt_pengirim')
            ->get();

        $products = DB::table('products')
            ->select('id', 'kode_barang', 'nama_barang', 'merek', 'ukuran')
            ->whereNull('deleted_at')
            ->orderBy('nama_barang')
            ->get();

        if ($suppliers->count() < 25 || $products->count() < 100) {
            $this->command?->warn('SupplierInvoiceAnnualDenseSeeder dilewati: butuh minimal 25 supplier dan 100 product aktif.');
            return;
        }

        $monthlyPlan = [82, 88, 94, 98, 104, 112, 118, 116, 108, 100, 92, 88];

        if (array_sum($monthlyPlan) !== 1200) {
            $this->command?->warn('SupplierInvoiceAnnualDenseSeeder batal: total monthly plan bukan 1.200.');
            return;
        }

        $startMonth = CarbonImmutable::today()->startOfMonth()->subMonths(11);
        $primarySuppliers = $suppliers->take(5)->values();
        $secondarySuppliers = $suppliers->slice(5)->take(20)->values();

        $invoiceRunningNo = 1;

        foreach ($monthlyPlan as $monthIndex => $invoiceCount) {
            $monthDate = $startMonth->addMonths($monthIndex);
            $daysInMonth = $monthDate->daysInMonth;

            for ($n = 1; $n <= $invoiceCount; $n++) {
                $invoiceId = sprintf('seed-annual-si-%04d', $invoiceRunningNo);
                $invoiceNo = sprintf('SI-YR-%s-%04d', $monthDate->format('Ym'), $invoiceRunningNo);
                $shipDay = 1 + ((($n * 3) + ($monthIndex * 5) + $invoiceRunningNo) % $daysInMonth);
                $shipDate = $monthDate->day($shipDay);

                $supplier = $this->pickSupplier(
                    invoiceRunningNo: $invoiceRunningNo,
                    monthIndex: $monthIndex,
                    primarySuppliers: $primarySuppliers,
                    secondarySuppliers: $secondarySuppliers,
                );

                $lines = $this->buildLines(
                    invoiceId: $invoiceId,
                    invoiceRunningNo: $invoiceRunningNo,
                    monthIndex: $monthIndex,
                    products: $products,
                );

                DB::table('supplier_invoices')->updateOrInsert(
                    ['id' => $invoiceId],
                    [
                        'supplier_id' => (string) $supplier->id,
                        'supplier_nama_pt_pengirim_snapshot' => (string) $supplier->nama_pt_pengirim,
                        'nomor_faktur' => $invoiceNo,
                        'nomor_faktur_normalized' => mb_strtolower($invoiceNo, 'UTF-8'),
                        'document_kind' => 'invoice',
                        'lifecycle_status' => 'active',
                        'origin_supplier_invoice_id' => null,
                        'superseded_by_supplier_invoice_id' => null,
                        'tanggal_pengiriman' => $shipDate->format('Y-m-d'),
                        'jatuh_tempo' => $shipDate->addDays(30)->format('Y-m-d'),
                        'grand_total_rupiah' => array_sum(array_column($lines, 'line_total_rupiah')),
                        'voided_at' => null,
                        'void_reason' => null,
                        'last_revision_no' => 1,
                    ]
                );

                DB::table('supplier_invoice_lines')->where('supplier_invoice_id', $invoiceId)->delete();
                DB::table('supplier_invoice_lines')->insert($lines);

                $invoiceRunningNo++;
            }
        }

        $this->command?->info('SupplierInvoiceAnnualDenseSeeder selesai: 1.200 faktur tahunan padat dibuat.');
    }

    private function pickSupplier(
        int $invoiceRunningNo,
        int $monthIndex,
        Collection $primarySuppliers,
        Collection $secondarySuppliers,
    ): object {
        if ($invoiceRunningNo % 5 !== 0 || $secondarySuppliers->isEmpty()) {
            return $primarySuppliers[($invoiceRunningNo + $monthIndex) % $primarySuppliers->count()];
        }

        return $secondarySuppliers[(($invoiceRunningNo * 2) + $monthIndex) % $secondarySuppliers->count()];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildLines(
        string $invoiceId,
        int $invoiceRunningNo,
        int $monthIndex,
        Collection $products,
    ): array {
        $lineCount = 3 + (($invoiceRunningNo + $monthIndex) % 6);
        $lines = [];

        for ($lineNo = 1; $lineNo <= $lineCount; $lineNo++) {
            $productIndex = (($invoiceRunningNo * 11) + ($lineNo * 5) + ($monthIndex * 7)) % $products->count();
            $product = $products[$productIndex];
            $qty = 1 + (($invoiceRunningNo + ($lineNo * 3) + $monthIndex) % 20);
            $unitCost = 16000 + ((($invoiceRunningNo * 4750) + ($lineNo * 8250) + ($monthIndex * 12000)) % 320000);
            $lineTotal = $qty * $unitCost;

            $lines[] = [
                'id' => sprintf('%s-line-%02d', $invoiceId, $lineNo),
                'supplier_invoice_id' => $invoiceId,
                'revision_no' => 1,
                'is_current' => 1,
                'source_line_id' => null,
                'superseded_by_line_id' => null,
                'superseded_at' => null,
                'line_no' => $lineNo,
                'product_id' => (string) $product->id,
                'product_kode_barang_snapshot' => $product->kode_barang,
                'product_nama_barang_snapshot' => $product->nama_barang,
                'product_merek_snapshot' => $product->merek,
                'product_ukuran_snapshot' => $product->ukuran,
                'qty_pcs' => $qty,
                'line_total_rupiah' => $lineTotal,
                'unit_cost_rupiah' => $unitCost,
            ];
        }

        return $lines;
    }
}
