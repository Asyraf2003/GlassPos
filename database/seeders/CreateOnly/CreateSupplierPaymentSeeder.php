<?php

declare(strict_types=1);

namespace Database\Seeders\CreateOnly;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

final class CreateSupplierPaymentSeeder extends Seeder
{
    public function run(): void
    {
        $this->assertLocalOrTesting();

        $invoices = DB::table('supplier_invoices')
            ->where('id', 'like', 'seed-supplier-invoice-%')
            ->orderBy('id')
            ->limit(24)
            ->get(['id', 'grand_total_rupiah', 'tanggal_pengiriman']);

        if ($invoices->count() < 6) {
            throw new RuntimeException('CreateSupplierPaymentSeeder requires seeded procurement invoices. Run make procurement first.');
        }

        $actorId = $this->resolveActorId();
        $now = now()->format('Y-m-d H:i:s');

        $created = [
            'supplier_payments' => 0,
            'supplier_payment_proof_attachments' => 0,
        ];

        DB::transaction(function () use ($invoices, $actorId, $now, &$created): void {
            foreach ($invoices as $index => $invoice) {
                $sequence = $index + 1;
                $invoiceId = (string) $invoice->id;
                $grandTotalRupiah = (int) $invoice->grand_total_rupiah;

                if ($grandTotalRupiah < 1) {
                    throw new RuntimeException('Seeded supplier invoice has invalid grand_total_rupiah: ' . $invoiceId);
                }

                $paymentId = sprintf('seed-supplier-payment-%04d', $sequence);
                $isFullPayment = $sequence <= 12;
                $isUploadedProof = $sequence % 2 === 0;

                $amountRupiah = $isFullPayment
                    ? $grandTotalRupiah
                    : max(1, intdiv($grandTotalRupiah, 2));

                $paidAt = (string) ($invoice->tanggal_pengiriman ?? '2026-05-20');
                $proofStatus = $isUploadedProof ? 'uploaded' : 'pending';

                if ($this->insertIfMissing('supplier_payments', $paymentId, [
                    'id' => $paymentId,
                    'supplier_invoice_id' => $invoiceId,
                    'amount_rupiah' => $amountRupiah,
                    'paid_at' => $paidAt,
                    'proof_status' => $proofStatus,
                    'proof_storage_path' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])) {
                    $created['supplier_payments']++;
                }

                if (! $isUploadedProof || ! Schema::hasTable('supplier_payment_proof_attachments')) {
                    continue;
                }

                $attachmentId = sprintf('seed-supplier-payment-proof-%04d-01', $sequence);
                $storagePath = sprintf('supplier-payment-proofs/%s/seed-proof-%04d.pdf', $paymentId, $sequence);

                if ($this->insertIfMissing('supplier_payment_proof_attachments', $attachmentId, [
                    'id' => $attachmentId,
                    'supplier_payment_id' => $paymentId,
                    'storage_path' => $storagePath,
                    'original_filename' => sprintf('seed-proof-%04d.pdf', $sequence),
                    'mime_type' => 'application/pdf',
                    'file_size_bytes' => 1024 + $sequence,
                    'uploaded_at' => $now,
                    'uploaded_by_actor_id' => $actorId,
                ])) {
                    $created['supplier_payment_proof_attachments']++;
                }
            }
        });

        foreach ($created as $table => $count) {
            $this->command?->info($table . ' created=' . $count);
        }
    }

    private function assertLocalOrTesting(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Create-only seeders may only run in local or testing environments.');
        }
    }

    private function resolveActorId(): string
    {
        if (Schema::hasTable('actor_accesses')) {
            $actorId = DB::table('actor_accesses')
                ->orderBy('actor_id')
                ->value('actor_id');

            if (is_string($actorId) && trim($actorId) !== '') {
                return trim($actorId);
            }

            if (is_int($actorId) || is_float($actorId)) {
                return (string) $actorId;
            }
        }

        if (Schema::hasTable('users')) {
            $userId = DB::table('users')
                ->orderBy('id')
                ->value('id');

            if (is_string($userId) && trim($userId) !== '') {
                return trim($userId);
            }

            if (is_int($userId) || is_float($userId)) {
                return (string) $userId;
            }
        }

        return 'seed-actor-admin';
    }

    /**
     * @param array<string, mixed> $values
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
     * @param array<string, mixed> $values
     * @return array<string, mixed>
     */
    private function filterExistingColumns(string $table, array $values): array
    {
        $columns = array_flip(Schema::getColumnListing($table));

        return array_intersect_key($values, $columns);
    }
}
