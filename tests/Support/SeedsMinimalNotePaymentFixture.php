<?php

declare(strict_types=1);

namespace Tests\Support;

use Illuminate\Support\Facades\DB;

trait SeedsMinimalNotePaymentFixture
{
    private function seedNotePaymentProduct(
        string $id,
        ?string $kodeBarang = null,
        ?string $namaBarang = null,
        string $merek = 'General',
        ?int $ukuran = 100,
        int $hargaJual = 10000
    ): void {
        $kodeBarang ??= strtoupper(str_replace(['_', ' '], '-', $id));
        $namaBarang ??= 'Produk ' . $id;

        DB::table('products')->updateOrInsert(
            ['id' => $id],
            [
                'kode_barang' => $kodeBarang,
                'nama_barang' => $namaBarang,
                'nama_barang_normalized' => $this->normalize($namaBarang),
                'merek' => $merek,
                'merek_normalized' => $this->normalize($merek),
                'ukuran' => $ukuran,
                'harga_jual' => $hargaJual,
                'deleted_at' => null,
                'deleted_by_actor_id' => null,
                'delete_reason' => null,
            ]
        );
    }

    private function seedNoteBase(
        string $id,
        string $customerName,
        string $transactionDate,
        int $totalRupiah,
        string $noteState = 'open'
    ): void {
        DB::table('notes')->updateOrInsert(
            ['id' => $id],
            [
                'customer_name' => $customerName,
                'customer_phone' => null,
                'transaction_date' => $transactionDate,
                'note_state' => $noteState,
                'closed_at' => null,
                'closed_by_actor_id' => null,
                'reopened_at' => null,
                'reopened_by_actor_id' => null,
                'total_rupiah' => $totalRupiah,
            ]
        );
    }

    private function seedWorkItemBase(
        string $id,
        string $noteId,
        int $lineNo,
        string $transactionType,
        string $status,
        int $subtotalRupiah
    ): void {
        DB::table('work_items')->updateOrInsert(
            ['id' => $id],
            [
                'note_id' => $noteId,
                'line_no' => $lineNo,
                'transaction_type' => $transactionType,
                'status' => $status,
                'subtotal_rupiah' => $subtotalRupiah,
            ]
        );
    }

    private function seedServiceDetailBase(
        string $workItemId,
        string $serviceName,
        int $servicePriceRupiah,
        string $partSource
    ): void {
        DB::table('work_item_service_details')->updateOrInsert(
            ['work_item_id' => $workItemId],
            [
                'service_name' => $serviceName,
                'service_price_rupiah' => $servicePriceRupiah,
                'part_source' => $partSource,
            ]
        );
    }

    private function seedStoreStockLineBase(
        string $id,
        string $workItemId,
        string $productId,
        int $qty,
        int $lineTotalRupiah
    ): void {
        DB::table('work_item_store_stock_lines')->updateOrInsert(
            ['id' => $id],
            [
                'work_item_id' => $workItemId,
                'product_id' => $productId,
                'qty' => $qty,
                'line_total_rupiah' => $lineTotalRupiah,
            ]
        );
    }

    private function seedCustomerPaymentBase(
        string $id,
        int $amountRupiah,
        string $paidAt
    ): void {
        DB::table('customer_payments')->updateOrInsert(
            ['id' => $id],
            [
                'amount_rupiah' => $amountRupiah,
                'paid_at' => $paidAt,
            ]
        );
    }

    private function seedPaymentAllocationBase(
        string $id,
        string $customerPaymentId,
        string $noteId,
        int $amountRupiah
    ): void {
        DB::table('payment_allocations')->updateOrInsert(
            ['id' => $id],
            [
                'customer_payment_id' => $customerPaymentId,
                'note_id' => $noteId,
                'amount_rupiah' => $amountRupiah,
            ]
        );
    }

    private function seedServiceOnlyCurrentRevision(
        string $noteId,
        string $revisionId,
        string $workItemId,
        string $customerName,
        string $transactionDate,
        int $grandTotalRupiah,
        string $serviceName,
        int $servicePriceRupiah,
        string $status = 'open',
        ?string $customerPhone = null
    ): void {
        $this->seedCurrentRevision(
            $noteId,
            $revisionId,
            $customerName,
            $customerPhone,
            $transactionDate,
            $grandTotalRupiah,
            [[
                'id' => $revisionId . '-l001',
                'work_item_root_id' => $workItemId,
                'line_no' => 1,
                'transaction_type' => 'service_only',
                'status' => $status,
                'service_label' => $serviceName,
                'service_price_rupiah' => $servicePriceRupiah,
                'subtotal_rupiah' => $grandTotalRupiah,
                'payload' => [
                    'work_item_root_id' => $workItemId,
                    'transaction_type' => 'service_only',
                    'status' => $status,
                    'external_purchase_lines' => [],
                    'store_stock_lines' => [],
                    'service' => [
                        'service_name' => $serviceName,
                        'service_price_rupiah' => $servicePriceRupiah,
                        'part_source' => 'none',
                    ],
                ],
            ]],
        );
    }

    private function seedServiceWithStoreStockCurrentRevision(
        string $noteId,
        string $revisionId,
        string $workItemId,
        string $customerName,
        string $transactionDate,
        int $grandTotalRupiah,
        string $serviceName,
        int $servicePriceRupiah,
        string $storeStockLineId,
        string $productId,
        int $qty,
        int $storeStockLineTotalRupiah,
        string $status = 'open',
        ?string $customerPhone = null,
        ?int $packageProfitRupiah = null
    ): void {
        $payload = [
            'work_item_root_id' => $workItemId,
            'transaction_type' => 'service_with_store_stock_part',
            'status' => $status,
            'external_purchase_lines' => [],
            'store_stock_lines' => [[
                'id' => $storeStockLineId,
                'product_id' => $productId,
                'qty' => $qty,
                'line_total_rupiah' => $storeStockLineTotalRupiah,
            ]],
            'service' => [
                'service_name' => $serviceName,
                'service_price_rupiah' => $servicePriceRupiah,
                'part_source' => 'store_stock',
            ],
        ];

        if ($packageProfitRupiah !== null) {
            $payload['pricing_mode'] = 'package_auto_split';
            $payload['package_total_rupiah'] = $grandTotalRupiah;
            $payload['parts_total_rupiah'] = $storeStockLineTotalRupiah;
            $payload['service_price_rupiah'] = $servicePriceRupiah;
            $payload['package_profit_rupiah'] = $packageProfitRupiah;
            $payload['total_service_component_rupiah'] = $servicePriceRupiah + $packageProfitRupiah;
        }

        $this->seedCurrentRevision(
            $noteId,
            $revisionId,
            $customerName,
            $customerPhone,
            $transactionDate,
            $grandTotalRupiah,
            [[
                'id' => $revisionId . '-l001',
                'work_item_root_id' => $workItemId,
                'line_no' => 1,
                'transaction_type' => 'service_with_store_stock_part',
                'status' => $status,
                'service_label' => $serviceName,
                'service_price_rupiah' => $servicePriceRupiah,
                'subtotal_rupiah' => $grandTotalRupiah,
                'payload' => $payload,
            ]],
        );
    }

    /**
     * @param list<array<string, mixed>> $lines
     */
    private function seedCurrentRevision(
        string $noteId,
        string $revisionId,
        string $customerName,
        ?string $customerPhone,
        string $transactionDate,
        int $grandTotalRupiah,
        array $lines
    ): void {
        DB::table('notes')
            ->where('id', $noteId)
            ->update([
                'current_revision_id' => $revisionId,
                'latest_revision_number' => 1,
            ]);

        DB::table('note_revisions')->updateOrInsert(
            ['id' => $revisionId],
            [
                'note_root_id' => $noteId,
                'revision_number' => 1,
                'parent_revision_id' => null,
                'created_by_actor_id' => null,
                'reason' => 'minimal current revision fixture',
                'customer_name' => $customerName,
                'customer_phone' => $customerPhone,
                'transaction_date' => $transactionDate,
                'grand_total_rupiah' => $grandTotalRupiah,
                'line_count' => count($lines),
                'created_at' => now()->format('Y-m-d H:i:s'),
                'updated_at' => null,
            ]
        );

        DB::table('note_revision_lines')
            ->where('note_revision_id', $revisionId)
            ->delete();

        foreach ($lines as $line) {
            DB::table('note_revision_lines')->updateOrInsert(
                ['id' => (string) $line['id']],
                [
                    'note_revision_id' => $revisionId,
                    'work_item_root_id' => $line['work_item_root_id'],
                    'line_no' => $line['line_no'],
                    'transaction_type' => $line['transaction_type'],
                    'status' => $line['status'],
                    'service_label' => $line['service_label'],
                    'service_price_rupiah' => $line['service_price_rupiah'],
                    'subtotal_rupiah' => $line['subtotal_rupiah'],
                    'payload' => json_encode($line['payload'], JSON_THROW_ON_ERROR),
                    'created_at' => now()->format('Y-m-d H:i:s'),
                    'updated_at' => null,
                ]
            );
        }
    }

    private function normalize(string $value): string
    {
        $value = preg_replace('/\s+/', ' ', trim($value)) ?? trim($value);

        return mb_strtolower($value);
    }
}
