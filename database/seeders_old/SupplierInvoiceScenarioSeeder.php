<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Application\Procurement\Services\SupplierInvoiceListProjectionService;
use App\Application\Procurement\UseCases\CreateSupplierInvoiceFlowHandler;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

final class SupplierInvoiceScenarioSeeder extends Seeder
{
    public function run(): void
    {
        $supplier = DB::table('suppliers')
            ->select('id', 'nama_pt_pengirim')
            ->orderBy('nama_pt_pengirim')
            ->first();

        $products = DB::table('products')
            ->select('id', 'kode_barang', 'nama_barang', 'merek', 'ukuran')
            ->orderBy('nama_barang')
            ->limit(4)
            ->get()
            ->values();

        if ($supplier === null || $products->count() < 3) {
            $this->command?->warn('SupplierInvoiceScenarioSeeder dilewati: butuh minimal 1 supplier dan 3 product.');
            return;
        }

        $adminId = (string) (DB::table('users')->where('email', 'admin@gmail.com')->value('id') ?? '1');
        $handler = app(CreateSupplierInvoiceFlowHandler::class);

        $this->seedInvoice($handler, 'seed-si-editable', $supplier, [
            ['product' => $products[0], 'qty' => 2, 'unit_cost' => 12000],
            ['product' => $products[1], 'qty' => 1, 'unit_cost' => 18000],
        ], 'SI-EDIT-001', '2026-03-01', null, null, null, null, $adminId);

        $this->seedInvoice($handler, 'seed-si-received', $supplier, [
            ['product' => $products[0], 'qty' => 3, 'unit_cost' => 11000],
            ['product' => $products[2], 'qty' => 2, 'unit_cost' => 17000],
        ], 'SI-RECV-001', '2026-03-02', '2026-03-03', null, null, null, $adminId);

        $this->seedInvoice($handler, 'seed-si-paid-pending', $supplier, [
            ['product' => $products[1], 'qty' => 2, 'unit_cost' => 15000],
            ['product' => $products[2], 'qty' => 1, 'unit_cost' => 21000],
        ], 'SI-PAYP-001', '2026-03-04', null, '2026-03-06', 'pending', null, $adminId);

        $this->seedInvoice($handler, 'seed-si-paid-uploaded', $supplier, [
            ['product' => $products[0], 'qty' => 1, 'unit_cost' => 14000],
            ['product' => $products[2], 'qty' => 2, 'unit_cost' => 16000],
        ], 'SI-PROOF-001', '2026-03-05', null, '2026-03-07', 'uploaded', '1.jpg', $adminId);

        $this->seedInvoice($handler, 'seed-si-full', $supplier, [
            ['product' => $products[1], 'qty' => 2, 'unit_cost' => 13000],
            ['product' => $products[2], 'qty' => 2, 'unit_cost' => 17500],
        ], 'SI-FULL-001', '2026-03-06', '2026-03-07', '2026-03-08', 'uploaded', '2.jpg', $adminId);

        $this->command?->info('SupplierInvoiceScenarioSeeder selesai: 5 skenario nota supplier terbaru aktif via invoice system path.');
    }

    private function seedInvoice(
        CreateSupplierInvoiceFlowHandler $handler,
        string $scenarioKey,
        object $supplier,
        array $lineDefs,
        string $nomorFaktur,
        string $shipDate,
        ?string $receiptDate,
        ?string $paidAt,
        ?string $proofStatus,
        ?string $proofFile,
        string $adminId,
    ): void {
        $paymentId = $paidAt !== null ? $scenarioKey . '-payment-1' : null;
        $receiptId = $receiptDate !== null ? $scenarioKey . '-receipt-1' : null;

        $this->purgeScenarioInvoice($nomorFaktur, $paymentId, $receiptId);

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

        $result = $handler->handle(
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
                'SupplierInvoiceScenarioSeeder gagal membuat ' . $nomorFaktur . ': '
                . ($result->message() ?? 'unknown error')
            );
        }

        $invoice = $this->findInvoiceByNumber($nomorFaktur);

        if ($invoice === null) {
            throw new RuntimeException('SupplierInvoiceScenarioSeeder gagal membaca ulang invoice ' . $nomorFaktur . '.');
        }

        $invoiceId = (string) $invoice->id;
        $createdLines = DB::table('supplier_invoice_lines')
            ->select('id', 'line_no')
            ->where('supplier_invoice_id', $invoiceId)
            ->orderBy('line_no')
            ->get()
            ->keyBy('line_no');

        if ($createdLines->count() !== count($lineDefs)) {
            throw new RuntimeException('SupplierInvoiceScenarioSeeder menemukan jumlah line tidak cocok untuk ' . $nomorFaktur . '.');
        }

        if ((int) $invoice->grand_total_rupiah !== $grandTotal) {
            throw new RuntimeException('SupplierInvoiceScenarioSeeder menemukan grand total tidak cocok untuk ' . $nomorFaktur . '.');
        }

        if ($receiptId !== null) {
            $receiptLines = $this->buildReceiptLines($receiptId, $lineDefs, $createdLines);

            DB::table('supplier_receipts')->insert([
                'id' => $receiptId,
                'supplier_invoice_id' => $invoiceId,
                'tanggal_terima' => $receiptDate,
            ]);

            DB::table('supplier_receipt_lines')->insert($receiptLines);
        }

        if ($paymentId !== null && $proofStatus !== null) {
            $this->insertPaymentProofScenario(
                $paymentId,
                $invoiceId,
                $grandTotal,
                $paidAt,
                $proofStatus,
                $proofFile,
                $adminId
            );
        }

        app(SupplierInvoiceListProjectionService::class)->syncInvoice($invoiceId);
    }

    private function findInvoiceByNumber(string $nomorFaktur): ?object
    {
        return DB::table('supplier_invoices')
            ->select('id', 'grand_total_rupiah')
            ->where('nomor_faktur_normalized', mb_strtolower(trim($nomorFaktur), 'UTF-8'))
            ->first();
    }

    private function buildReceiptLines(string $receiptId, array $lineDefs, object $createdLines): array
    {
        $receiptLines = [];

        foreach ($lineDefs as $index => $def) {
            $lineNo = $index + 1;
            $createdLine = $createdLines->get($lineNo);

            if ($createdLine === null) {
                throw new RuntimeException('SupplierInvoiceScenarioSeeder gagal mapping line receipt nomor ' . $lineNo . '.');
            }

            $receiptLines[] = [
                'id' => $receiptId . '-line-' . $lineNo,
                'supplier_receipt_id' => $receiptId,
                'supplier_invoice_line_id' => (string) $createdLine->id,
                'product_id_snapshot' => (string) $def['product']->id,
                'product_kode_barang_snapshot' => $def['product']->kode_barang,
                'product_nama_barang_snapshot' => $def['product']->nama_barang,
                'product_merek_snapshot' => $def['product']->merek,
                'product_ukuran_snapshot' => $def['product']->ukuran,
                'unit_cost_rupiah_snapshot' => (int) $def['unit_cost'],
                'qty_diterima' => (int) $def['qty'],
            ];
        }

        return $receiptLines;
    }

    private function insertPaymentProofScenario(
        string $paymentId,
        string $invoiceId,
        int $grandTotal,
        ?string $paidAt,
        string $proofStatus,
        ?string $proofFile,
        string $adminId,
    ): void {
        $storagePath = $proofStatus === 'uploaded' && $proofFile !== null
            ? 'supplier-payment-proofs/' . $proofFile
            : null;

        DB::table('supplier_payments')->insert([
            'id' => $paymentId,
            'supplier_invoice_id' => $invoiceId,
            'amount_rupiah' => $grandTotal,
            'paid_at' => $paidAt,
            'proof_status' => $proofStatus,
            'proof_storage_path' => $storagePath,
        ]);

        if ($storagePath === null) {
            return;
        }

        DB::table('supplier_payment_proof_attachments')->insert([
            'id' => $paymentId . '-proof-1',
            'supplier_payment_id' => $paymentId,
            'storage_path' => $storagePath,
            'original_filename' => $proofFile,
            'mime_type' => 'image/jpeg',
            'file_size_bytes' => 250000,
            'uploaded_at' => CarbonImmutable::parse((string) $paidAt)->addHour()->format('Y-m-d H:i:s'),
            'uploaded_by_actor_id' => $adminId,
        ]);
    }

    private function purgeScenarioInvoice(string $nomorFaktur, ?string $paymentId, ?string $receiptId): void
    {
        if (! Schema::hasTable('supplier_invoices')) {
            return;
        }

        $normalizedInvoiceNo = mb_strtolower(trim($nomorFaktur), 'UTF-8');

        $invoiceIds = DB::table('supplier_invoices')
            ->where('nomor_faktur_normalized', $normalizedInvoiceNo)
            ->orWhere('nomor_faktur', $nomorFaktur)
            ->pluck('id')
            ->map(static fn ($id): string => (string) $id)
            ->values();

        if ($paymentId !== null && Schema::hasTable('supplier_payment_proof_attachments')) {
            DB::table('supplier_payment_proof_attachments')
                ->where('supplier_payment_id', $paymentId)
                ->delete();
        }

        if ($paymentId !== null && Schema::hasTable('supplier_payments')) {
            DB::table('supplier_payments')
                ->where('id', $paymentId)
                ->delete();
        }

        if ($receiptId !== null && Schema::hasTable('supplier_receipt_lines')) {
            DB::table('supplier_receipt_lines')
                ->where('supplier_receipt_id', $receiptId)
                ->delete();
        }

        if ($receiptId !== null && Schema::hasTable('supplier_receipts')) {
            DB::table('supplier_receipts')
                ->where('id', $receiptId)
                ->delete();
        }

        if ($invoiceIds->isEmpty()) {
            return;
        }

        if (Schema::hasTable('supplier_payments')) {
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

        if (Schema::hasTable('supplier_receipts')) {
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
