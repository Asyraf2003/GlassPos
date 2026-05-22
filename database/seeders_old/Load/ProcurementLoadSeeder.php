<?php

declare(strict_types=1);

namespace Database\Seeders\Load;

use Database\Seeders\Support\SeedDensity;
use Database\Seeders\Support\SeedWindow;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class ProcurementLoadSeeder extends Seeder
{
    private const INT_MAX = 2147483647;

    public function run(): void
    {
        $suppliers = DB::table('suppliers')
            ->select('id', 'nama_pt_pengirim')
            ->whereNull('deleted_at')
            ->orderBy('nama_pt_pengirim')
            ->get()
            ->values();

        $products = DB::table('products')
            ->select('id', 'kode_barang', 'nama_barang', 'merek', 'ukuran', 'harga_jual')
            ->whereNull('deleted_at')
            ->orderBy('nama_barang')
            ->get()
            ->values();

        if ($suppliers->count() < 5 || $products->count() < 10) {
            $this->command?->warn('ProcurementLoadSeeder dilewati: butuh minimal 5 supplier dan 10 product aktif.');
            return;
        }

        $window = SeedWindow::loadYear();
        $density = SeedDensity::monster();

        $this->purgeSeededProcurementLoad();

        $inventoryDeltas = [];
        $invoiceRunningNo = 1;

        foreach ($window['days'] as $dayIndex => $day) {
            $invoiceCount = $this->resolveInvoiceCount($day, $density);

            for ($slot = 1; $slot <= $invoiceCount; $slot++) {
                $invoiceId = sprintf('seed-load-si-%s-%02d', $day->format('Ymd'), $slot);
                $invoiceNo = sprintf('SI-LOAD-%s-%04d', $day->format('Ymd'), $invoiceRunningNo);
                $supplier = $suppliers[($invoiceRunningNo - 1) % $suppliers->count()];
                $lines = $this->buildLines($invoiceId, $invoiceRunningNo, $dayIndex, $slot, $products);
                $grandTotal = array_sum(array_column($lines, 'line_total_rupiah'));

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
                        'tanggal_pengiriman' => $day->format('Y-m-d'),
                        'jatuh_tempo' => $day->addDays(30)->format('Y-m-d'),
                        'grand_total_rupiah' => $grandTotal,
                        'voided_at' => null,
                        'void_reason' => null,
                        'last_revision_no' => 1,
                    ]
                );

                DB::table('supplier_invoice_lines')
                    ->where('supplier_invoice_id', $invoiceId)
                    ->delete();

                DB::table('supplier_invoice_lines')->insert($lines);

                $statePattern = ($invoiceRunningNo - 1) % 6;

                if ($this->shouldReceive($statePattern)) {
                    $receiptId = sprintf('seed-load-sr-%s-%02d', $day->format('Ymd'), $slot);

                    DB::table('supplier_receipts')->updateOrInsert(
                        ['id' => $receiptId],
                        [
                            'supplier_invoice_id' => $invoiceId,
                            'tanggal_terima' => $day->addDay()->format('Y-m-d'),
                        ]
                    );

                    $receiptLines = [];
                    $movementRows = [];

                    foreach ($lines as $lineIndex => $line) {
                        $receiptLineId = sprintf('%s-line-%02d', $receiptId, $lineIndex + 1);
                        $qty = (int) $line['qty_pcs'];
                        $unitCost = (int) $line['unit_cost_rupiah'];
                        $totalCost = $qty * $unitCost;

                        $receiptLines[] = [
                            'id' => $receiptLineId,
                            'supplier_receipt_id' => $receiptId,
                            'supplier_invoice_line_id' => $line['id'],
                            'product_id_snapshot' => (string) $line['product_id'],
                            'product_kode_barang_snapshot' => $line['product_kode_barang_snapshot'],
                            'product_nama_barang_snapshot' => $line['product_nama_barang_snapshot'],
                            'product_merek_snapshot' => $line['product_merek_snapshot'],
                            'product_ukuran_snapshot' => $line['product_ukuran_snapshot'],
                            'unit_cost_rupiah_snapshot' => $unitCost,
                            'qty_diterima' => $qty,
                        ];

                        $movementRows[] = [
                            'id' => sprintf('seed-load-im-%s-%02d-%02d', $day->format('Ymd'), $slot, $lineIndex + 1),
                            'product_id' => $line['product_id'],
                            'movement_type' => 'stock_in',
                            'source_type' => 'supplier_receipt_line',
                            'source_id' => $receiptLineId,
                            'tanggal_mutasi' => $day->addDay()->format('Y-m-d'),
                            'qty_delta' => $qty,
                            'unit_cost_rupiah' => $unitCost,
                            'total_cost_rupiah' => $totalCost,
                        ];

                        $productId = (string) $line['product_id'];

                        if (!isset($inventoryDeltas[$productId])) {
                            $inventoryDeltas[$productId] = [
                                'qty' => 0,
                                'cost' => 0,
                            ];
                        }

                        $inventoryDeltas[$productId]['qty'] += $qty;
                        $inventoryDeltas[$productId]['cost'] += $totalCost;
                    }

                    DB::table('supplier_receipt_lines')
                        ->where('supplier_receipt_id', $receiptId)
                        ->delete();

                    DB::table('supplier_receipt_lines')->insert($receiptLines);

                    foreach ($movementRows as $movementRow) {
                        DB::table('inventory_movements')->updateOrInsert(
                            ['id' => $movementRow['id']],
                            $movementRow
                        );
                    }

                    DB::table('audit_logs')->insert([
                        'event' => 'supplier_receipt_created',
                        'context' => json_encode([
                            'receipt_id' => $receiptId,
                            'invoice_id' => $invoiceId,
                            'line_count' => count($receiptLines),
                            'seed_source' => self::class,
                        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                    ]);
                }

                if ($this->shouldPay($statePattern)) {
                    $paidAmount = $this->resolvePaidAmount($grandTotal, $statePattern);
                    $proofUploaded = $this->isUploadedProof($statePattern);

                    DB::table('supplier_payments')->updateOrInsert(
                        ['id' => sprintf('seed-load-sp-%s-%02d', $day->format('Ymd'), $slot)],
                        [
                            'supplier_invoice_id' => $invoiceId,
                            'amount_rupiah' => $paidAmount,
                            'paid_at' => $day->addDays(2)->format('Y-m-d'),
                            'proof_status' => $proofUploaded ? 'uploaded' : 'pending',
                            'proof_storage_path' => $proofUploaded
                                ? sprintf('seed/procurement/%s/payment-proof-%02d.jpg', $day->format('Ymd'), $slot)
                                : null,
                        ]
                    );
                }

                $invoiceRunningNo++;
            }
        }

        $this->applyInventoryProjections($inventoryDeltas);

        $this->command?->info('ProcurementLoadSeeder selesai: procurement monster 1 tahun dibuat.');
    }

    private function resolveInvoiceCount(object $day, array $density): int
    {
        $spikeDays = [2, 4, 6];
        $weekday = (int) $day->dayOfWeekIso;

        $count = in_array($weekday, $spikeDays, true)
            ? (int) $density['procurement_invoices_spike_per_day']
            : (int) $density['procurement_invoices_normal_per_day'];

        if ((int) $day->day >= 26) {
            $count = (int) ceil($count * ((int) $density['month_end_procurement_multiplier_percent']) / 100);
        }

        return max(1, $count);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildLines(
        string $invoiceId,
        int $invoiceRunningNo,
        int $dayIndex,
        int $slot,
        Collection $products,
    ): array {
        $lineCount = 4 + (($invoiceRunningNo + $slot + $dayIndex) % 5);
        $lines = [];

        for ($lineNo = 1; $lineNo <= $lineCount; $lineNo++) {
            $productIndex = (($invoiceRunningNo * 5) + ($lineNo * 7) + $dayIndex) % $products->count();
            $product = $products[$productIndex];
            $qty = 3 + (($invoiceRunningNo + ($lineNo * 3) + $slot) % 10);
            $baseCost = max(1000, (int) floor(((int) $product->harga_jual) * 0.32));
            $unitCost = $baseCost + ((($dayIndex + $lineNo + $slot) % 7) * 500);
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

    private function shouldReceive(int $statePattern): bool
    {
        return in_array($statePattern, [1, 2, 3, 4, 5], true);
    }

    private function shouldPay(int $statePattern): bool
    {
        return in_array($statePattern, [2, 3, 4, 5], true);
    }

    private function isUploadedProof(int $statePattern): bool
    {
        return in_array($statePattern, [4, 5], true);
    }

    private function resolvePaidAmount(int $grandTotal, int $statePattern): int
    {
        if ($statePattern === 2) {
            return max(1000, intdiv($grandTotal * 60, 100));
        }

        return $grandTotal;
    }

    private function applyInventoryProjections(array $inventoryDeltas): void
    {
        foreach ($inventoryDeltas as $productId => $delta) {
            $currentQty = (int) (DB::table('product_inventory')->where('product_id', $productId)->value('qty_on_hand') ?? 0);
            $currentValue = (int) (DB::table('product_inventory_costing')->where('product_id', $productId)->value('inventory_value_rupiah') ?? 0);

            $newQty = $currentQty + (int) $delta['qty'];
            $newValueRaw = $currentValue + (int) $delta['cost'];
            $newValue = min(self::INT_MAX, max(0, $newValueRaw));
            $avgCost = $newQty > 0 ? (int) floor($newValue / $newQty) : 0;

            DB::table('product_inventory')->updateOrInsert(
                ['product_id' => $productId],
                ['qty_on_hand' => $newQty]
            );

            DB::table('product_inventory_costing')->updateOrInsert(
                ['product_id' => $productId],
                [
                    'avg_cost_rupiah' => $avgCost,
                    'inventory_value_rupiah' => $newValue,
                ]
            );
        }
    }

    private function purgeSeededProcurementLoad(): void
    {
        $oldMovements = DB::table('inventory_movements')
            ->select('product_id', DB::raw('SUM(qty_delta) as qty_total'), DB::raw('SUM(total_cost_rupiah) as cost_total'))
            ->where('id', 'like', 'seed-load-im-%')
            ->groupBy('product_id')
            ->get();

        foreach ($oldMovements as $movement) {
            $productId = (string) $movement->product_id;
            $currentQty = (int) (DB::table('product_inventory')->where('product_id', $productId)->value('qty_on_hand') ?? 0);
            $currentValue = (int) (DB::table('product_inventory_costing')->where('product_id', $productId)->value('inventory_value_rupiah') ?? 0);

            $newQty = max(0, $currentQty - (int) $movement->qty_total);
            $newValue = min(self::INT_MAX, max(0, $currentValue - (int) $movement->cost_total));
            $avgCost = $newQty > 0 ? (int) floor($newValue / $newQty) : 0;

            DB::table('product_inventory')->updateOrInsert(
                ['product_id' => $productId],
                ['qty_on_hand' => $newQty]
            );

            DB::table('product_inventory_costing')->updateOrInsert(
                ['product_id' => $productId],
                [
                    'avg_cost_rupiah' => $avgCost,
                    'inventory_value_rupiah' => $newValue,
                ]
            );
        }

        DB::table('supplier_payments')
            ->where('id', 'like', 'seed-load-sp-%')
            ->delete();

        DB::table('audit_logs')
            ->where('event', 'supplier_receipt_created')
            ->where('context', 'like', '%seed-load-sr-%')
            ->delete();

        DB::table('inventory_movements')
            ->where('id', 'like', 'seed-load-im-%')
            ->delete();

        DB::table('supplier_receipt_lines')
            ->where('id', 'like', 'seed-load-sr-%')
            ->delete();

        DB::table('supplier_receipts')
            ->where('id', 'like', 'seed-load-sr-%')
            ->delete();

        DB::table('supplier_invoice_lines')
            ->where('supplier_invoice_id', 'like', 'seed-load-si-%')
            ->delete();

        DB::table('supplier_invoices')
            ->where('id', 'like', 'seed-load-si-%')
            ->delete();
    }
}
