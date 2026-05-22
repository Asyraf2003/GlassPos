<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Application\Procurement\UseCases\CreateSupplierInvoiceFlowHandler;
use App\Application\Procurement\UseCases\VoidSupplierInvoiceHandler;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

final class SupplierInvoiceVoidedScenarioSeeder extends Seeder
{
    /**
     * @var list<string>
     */
    private const SEEDED_INVOICE_NOS = [
        'SI-VOID-001',
        'SI-VOID-REUSE-001',
    ];

    public function run(): void
    {
        $supplier = DB::table('suppliers')
            ->select('id', 'nama_pt_pengirim')
            ->orderBy('nama_pt_pengirim')
            ->first();

        $products = DB::table('products')
            ->select('id', 'kode_barang', 'nama_barang', 'merek', 'ukuran')
            ->whereNull('deleted_at')
            ->orderBy('nama_barang')
            ->limit(3)
            ->get()
            ->values();

        if ($supplier === null || $products->count() < 2) {
            $this->command?->warn('SupplierInvoiceVoidedScenarioSeeder dilewati: butuh minimal 1 supplier dan 2 product aktif.');
            return;
        }

        $adminId = (string) (DB::table('users')->where('email', 'admin@gmail.com')->value('id') ?? '1');

        $this->purgeSeededInvoices();

        $createHandler = app(CreateSupplierInvoiceFlowHandler::class);
        $voidHandler = app(VoidSupplierInvoiceHandler::class);

        $this->seedInvoice(
            createHandler: $createHandler,
            voidHandler: $voidHandler,
            supplier: $supplier,
            lineDefs: [
                ['product' => $products[0], 'qty' => 2, 'unit_cost' => 12500],
                ['product' => $products[1], 'qty' => 1, 'unit_cost' => 18000],
            ],
            nomorFaktur: 'SI-VOID-001',
            shipDate: '2026-03-09',
            adminId: $adminId,
            voidReason: 'Salah input sebelum ada efek domain.',
        );

        $this->seedInvoice(
            createHandler: $createHandler,
            voidHandler: $voidHandler,
            supplier: $supplier,
            lineDefs: [
                ['product' => $products[0], 'qty' => 1, 'unit_cost' => 14000],
                ['product' => $products[1], 'qty' => 2, 'unit_cost' => 16000],
            ],
            nomorFaktur: 'SI-VOID-REUSE-001',
            shipDate: '2026-03-10',
            adminId: $adminId,
            voidReason: 'Nomor faktur lama dibatalkan sebelum dipakai ulang.',
        );

        $this->seedInvoice(
            createHandler: $createHandler,
            voidHandler: $voidHandler,
            supplier: $supplier,
            lineDefs: [
                ['product' => $products[0], 'qty' => 3, 'unit_cost' => 15000],
                ['product' => $products[1], 'qty' => 1, 'unit_cost' => 20000],
            ],
            nomorFaktur: 'SI-VOID-REUSE-001',
            shipDate: '2026-03-11',
            adminId: $adminId,
            voidReason: null,
        );

        $this->command?->info('SupplierInvoiceVoidedScenarioSeeder selesai: skenario nota void dibuat via invoice + void system path.');
    }

    private function seedInvoice(
        CreateSupplierInvoiceFlowHandler $createHandler,
        VoidSupplierInvoiceHandler $voidHandler,
        object $supplier,
        array $lineDefs,
        string $nomorFaktur,
        string $shipDate,
        string $adminId,
        ?string $voidReason,
    ): void {
        [$handlerLines, $grandTotal] = $this->buildHandlerLines($lineDefs);

        $result = $createHandler->handle(
            $nomorFaktur,
            (string) $supplier->nama_pt_pengirim,
            $shipDate,
            $handlerLines,
            false,
            null,
            $adminId,
            'admin',
            'seeder'
        );

        if ($result->isFailure()) {
            throw new RuntimeException(
                'SupplierInvoiceVoidedScenarioSeeder gagal membuat ' . $nomorFaktur . ': '
                . ($result->message() ?? 'unknown error')
            );
        }

        $invoice = $this->findActiveInvoiceByNumber($nomorFaktur);

        if ($invoice === null) {
            throw new RuntimeException('SupplierInvoiceVoidedScenarioSeeder gagal membaca ulang invoice aktif ' . $nomorFaktur . '.');
        }

        $invoiceId = (string) $invoice->id;
        $this->assertCreatedInvoiceShape($invoiceId, $nomorFaktur, $grandTotal, count($handlerLines));

        if ($voidReason === null) {
            return;
        }

        $voidResult = $voidHandler->handle($invoiceId, $voidReason, $adminId);

        if ($voidResult->isFailure()) {
            throw new RuntimeException(
                'SupplierInvoiceVoidedScenarioSeeder gagal void ' . $nomorFaktur . ': '
                . ($voidResult->message() ?? 'unknown error')
            );
        }
    }

    /**
     * @return array{0: list<array<string, int|string>>, 1: int}
     */
    private function buildHandlerLines(array $lineDefs): array
    {
        $handlerLines = [];
        $grandTotal = 0;

        foreach ($lineDefs as $index => $def) {
            $qty = (int) $def['qty'];
            $unitCost = (int) $def['unit_cost'];
            $lineTotal = $qty * $unitCost;
            $grandTotal += $lineTotal;

            $handlerLines[] = [
                'line_no' => $index + 1,
                'product_id' => (string) $def['product']->id,
                'qty_pcs' => $qty,
                'line_total_rupiah' => $lineTotal,
            ];
        }

        return [$handlerLines, $grandTotal];
    }

    private function findActiveInvoiceByNumber(string $nomorFaktur): ?object
    {
        return DB::table('supplier_invoices')
            ->select('id', 'grand_total_rupiah')
            ->where('nomor_faktur_normalized', mb_strtolower(trim($nomorFaktur), 'UTF-8'))
            ->whereNull('voided_at')
            ->first();
    }

    private function assertCreatedInvoiceShape(
        string $invoiceId,
        string $nomorFaktur,
        int $expectedGrandTotal,
        int $expectedLineCount,
    ): void {
        $invoice = DB::table('supplier_invoices')
            ->select('grand_total_rupiah')
            ->where('id', $invoiceId)
            ->first();

        if ($invoice === null) {
            throw new RuntimeException('SupplierInvoiceVoidedScenarioSeeder gagal menemukan invoice ' . $nomorFaktur . '.');
        }

        if ((int) $invoice->grand_total_rupiah !== $expectedGrandTotal) {
            throw new RuntimeException('SupplierInvoiceVoidedScenarioSeeder menemukan grand total tidak cocok untuk ' . $nomorFaktur . '.');
        }

        $lineCount = DB::table('supplier_invoice_lines')
            ->where('supplier_invoice_id', $invoiceId)
            ->count();

        if ($lineCount !== $expectedLineCount) {
            throw new RuntimeException('SupplierInvoiceVoidedScenarioSeeder menemukan jumlah line tidak cocok untuk ' . $nomorFaktur . '.');
        }
    }

    private function purgeSeededInvoices(): void
    {
        if (! Schema::hasTable('supplier_invoices')) {
            return;
        }

        $normalizedInvoiceNos = array_map(
            static fn (string $invoiceNo): string => mb_strtolower(trim($invoiceNo), 'UTF-8'),
            self::SEEDED_INVOICE_NOS
        );

        $invoiceIds = DB::table('supplier_invoices')
            ->whereIn('nomor_faktur_normalized', $normalizedInvoiceNos)
            ->orWhereIn('nomor_faktur', self::SEEDED_INVOICE_NOS)
            ->pluck('id')
            ->map(static fn ($id): string => (string) $id)
            ->values();

        if ($invoiceIds->isEmpty()) {
            return;
        }

        $this->deletePaymentsForInvoices($invoiceIds->all());
        $this->deleteReceiptsForInvoices($invoiceIds->all());
        $this->deleteAuditEventsForInvoices($invoiceIds->all());
        $this->deleteAuditLogsForInvoices($invoiceIds->all());

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

    /**
     * @param list<string> $invoiceIds
     */
    private function deletePaymentsForInvoices(array $invoiceIds): void
    {
        if (! Schema::hasTable('supplier_payments')) {
            return;
        }

        $paymentIds = DB::table('supplier_payments')
            ->whereIn('supplier_invoice_id', $invoiceIds)
            ->pluck('id')
            ->map(static fn ($id): string => (string) $id)
            ->values();

        if ($paymentIds->isNotEmpty() && Schema::hasTable('supplier_payment_proof_attachments')) {
            DB::table('supplier_payment_proof_attachments')
                ->whereIn('supplier_payment_id', $paymentIds)
                ->delete();
        }

        DB::table('supplier_payments')
            ->whereIn('supplier_invoice_id', $invoiceIds)
            ->delete();
    }

    /**
     * @param list<string> $invoiceIds
     */
    private function deleteReceiptsForInvoices(array $invoiceIds): void
    {
        if (! Schema::hasTable('supplier_receipts')) {
            return;
        }

        $receiptIds = DB::table('supplier_receipts')
            ->whereIn('supplier_invoice_id', $invoiceIds)
            ->pluck('id')
            ->map(static fn ($id): string => (string) $id)
            ->values();

        if ($receiptIds->isNotEmpty() && Schema::hasTable('supplier_receipt_lines')) {
            DB::table('supplier_receipt_lines')
                ->whereIn('supplier_receipt_id', $receiptIds)
                ->delete();
        }

        DB::table('supplier_receipts')
            ->whereIn('supplier_invoice_id', $invoiceIds)
            ->delete();
    }

    /**
     * @param list<string> $invoiceIds
     */
    private function deleteAuditEventsForInvoices(array $invoiceIds): void
    {
        if (! Schema::hasTable('audit_events')) {
            return;
        }

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

    /**
     * @param list<string> $invoiceIds
     */
    private function deleteAuditLogsForInvoices(array $invoiceIds): void
    {
        if (! Schema::hasTable('audit_logs')) {
            return;
        }

        DB::table('audit_logs')
            ->where('event', 'supplier_invoice_voided')
            ->where(function ($query) use ($invoiceIds): void {
                foreach ($invoiceIds as $invoiceId) {
                    $query->orWhere('context', 'like', '%"supplier_invoice_id":"' . $invoiceId . '"%');
                }
            })
            ->delete();
    }
}
