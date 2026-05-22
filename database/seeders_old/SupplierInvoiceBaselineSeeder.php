<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Application\Procurement\UseCases\CreateSupplierInvoiceFlowHandler;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

final class SupplierInvoiceBaselineSeeder extends Seeder
{
    private const INVOICE_NO_PREFIX = 'SI-BL-';

    public function run(): void
    {
        $suppliers = DB::table('suppliers')
            ->select('id', 'nama_pt_pengirim')
            ->orderBy('nama_pt_pengirim')
            ->get();

        $products = DB::table('products')
            ->select('id', 'nama_barang')
            ->whereNull('deleted_at')
            ->orderBy('nama_barang')
            ->get();

        if ($suppliers->count() < 5 || $products->count() < 8) {
            $this->command?->warn('SupplierInvoiceBaselineSeeder dilewati: butuh minimal 5 supplier dan 8 product aktif.');
            return;
        }

        $this->purgeSeededInvoices();

        $handler = app(CreateSupplierInvoiceFlowHandler::class);
        $startDate = CarbonImmutable::today('Asia/Jakarta')->subDays(29);
        $invoicePlan = $this->buildMonthlyInvoicePlan();

        $primarySuppliers = $suppliers->take(5)->values();
        $secondarySuppliers = $suppliers->slice(5)->values();

        $invoiceRunningNo = 1;

        foreach ($invoicePlan as $dayOffset => $invoiceCount) {
            $shipDate = $startDate->addDays($dayOffset);

            for ($n = 1; $n <= $invoiceCount; $n++) {
                $invoiceNo = sprintf(
                    '%s%s-%03d',
                    self::INVOICE_NO_PREFIX,
                    $shipDate->format('Ymd'),
                    $invoiceRunningNo
                );

                $supplier = $this->pickSupplier(
                    $invoiceRunningNo,
                    $primarySuppliers,
                    $secondarySuppliers
                );

                $result = $handler->handle(
                    $invoiceNo,
                    (string) $supplier->nama_pt_pengirim,
                    $shipDate->format('Y-m-d'),
                    $this->buildLines($invoiceRunningNo, $products),
                    false,
                    null,
                    'seed-system',
                    'seeder',
                    'seeder'
                );

                if ($result->isFailure()) {
                    throw new RuntimeException(
                        'SupplierInvoiceBaselineSeeder gagal membuat ' . $invoiceNo . ': '
                        . ($result->message() ?? 'unknown error')
                    );
                }

                $invoiceRunningNo++;
            }
        }

        $this->command?->info(sprintf(
            'SupplierInvoiceBaselineSeeder selesai: %d faktur baseline 1 bulan dibuat via system path.',
            $invoiceRunningNo - 1
        ));
    }

    /**
     * @return list<int>
     */
    private function buildMonthlyInvoicePlan(): array
    {
        $plan = [];

        for ($day = 1; $day <= 30; $day++) {
            $plan[] = match (true) {
                $day % 10 === 0 => 4,
                $day % 5 === 0 => 3,
                default => 2,
            };
        }

        return $plan;
    }

    private function pickSupplier(
        int $invoiceRunningNo,
        Collection $primarySuppliers,
        Collection $secondarySuppliers,
    ): object {
        if ($invoiceRunningNo % 4 !== 0 || $secondarySuppliers->isEmpty()) {
            return $primarySuppliers[($invoiceRunningNo - 1) % $primarySuppliers->count()];
        }

        return $secondarySuppliers[($invoiceRunningNo - 1) % $secondarySuppliers->count()];
    }

    /**
     * @return list<array<string, int|string>>
     */
    private function buildLines(int $invoiceRunningNo, Collection $products): array
    {
        $lineCount = min(3 + (($invoiceRunningNo - 1) % 6), $products->count());
        $productOffset = (($invoiceRunningNo - 1) * 7) % $products->count();
        $lines = [];

        for ($lineNo = 1; $lineNo <= $lineCount; $lineNo++) {
            $productIndex = ($productOffset + $lineNo - 1) % $products->count();
            $product = $products[$productIndex];

            $qty = 1 + (($invoiceRunningNo + ($lineNo * 2)) % 20);
            $unitCost = 18000 + ((($invoiceRunningNo * 6500) + ($lineNo * 3750)) % 280000);

            $lines[] = [
                'line_no' => $lineNo,
                'product_id' => (string) $product->id,
                'qty_pcs' => $qty,
                'line_total_rupiah' => $qty * $unitCost,
            ];
        }

        return $lines;
    }

    private function purgeSeededInvoices(): void
    {
        if (! Schema::hasTable('supplier_invoices')) {
            return;
        }

        $invoiceIds = DB::table('supplier_invoices')
            ->where('nomor_faktur', 'like', self::INVOICE_NO_PREFIX . '%')
            ->pluck('id')
            ->map(static fn ($id): string => (string) $id)
            ->values();

        if ($invoiceIds->isEmpty()) {
            return;
        }

        if (Schema::hasTable('audit_events')) {
            $auditEventIds = DB::table('audit_events')
                ->where('aggregate_type', 'supplier_invoice')
                ->whereIn('aggregate_id', $invoiceIds)
                ->pluck('id')
                ->map(static fn ($id): string => (string) $id)
                ->values();

            if ($auditEventIds->isNotEmpty() && Schema::hasTable('audit_event_snapshots')) {
                DB::table('audit_event_snapshots')
                    ->whereIn('audit_event_id', $auditEventIds)
                    ->delete();
            }

            DB::table('audit_events')
                ->where('aggregate_type', 'supplier_invoice')
                ->whereIn('aggregate_id', $invoiceIds)
                ->delete();
        }

        if (Schema::hasTable('supplier_invoice_list_projection')) {
            DB::table('supplier_invoice_list_projection')
                ->whereIn('supplier_invoice_id', $invoiceIds)
                ->delete();
        }

        if (Schema::hasTable('supplier_invoice_versions')) {
            DB::table('supplier_invoice_versions')
                ->whereIn('supplier_invoice_id', $invoiceIds)
                ->delete();
        }

        if (Schema::hasTable('supplier_invoice_lines')) {
            DB::table('supplier_invoice_lines')
                ->whereIn('supplier_invoice_id', $invoiceIds)
                ->delete();
        }

        DB::table('supplier_invoices')
            ->whereIn('id', $invoiceIds)
            ->delete();
    }
}
