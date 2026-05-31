<?php

declare(strict_types=1);

namespace App\Application\Note\Services\CurrentRevision;

use App\Core\Note\Revision\NoteRevisionLineSnapshot;
use App\Core\Note\WorkItem\WorkItem;
use Illuminate\Support\Facades\DB;

final class CurrentRevisionPackageBreakdownMapper
{
    /** @param array<string, mixed> $payload */
    public function map(NoteRevisionLineSnapshot $line, array $payload): ?array
    {
        if ($line->transactionType() !== WorkItem::TYPE_SERVICE_WITH_STORE_STOCK_PART) {
            return null;
        }

        $parts = $this->storeStockParts($payload);
        if ($parts === []) {
            return null;
        }

        $partsTotal = array_sum(array_map(
            static fn (array $part): int => (int) $part['line_total_rupiah'],
            $parts,
        ));

        return [
            'package_total_rupiah' => $line->subtotalRupiah(),
            'parts_total_rupiah' => $partsTotal,
            'service_residual_rupiah' => (int) ($payload['service']['service_price_rupiah'] ?? 0),
            'parts' => $parts,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return list<array<string, mixed>>
     */
    private function storeStockParts(array $payload): array
    {
        $lines = is_array($payload['store_stock_lines'] ?? null)
            ? array_values(array_filter($payload['store_stock_lines'], 'is_array'))
            : [];

        $names = $this->productNames(array_map(
            static fn (array $line): string => trim((string) ($line['product_id'] ?? '')),
            $lines,
        ));

        $parts = [];
        foreach ($lines as $line) {
            $productId = trim((string) ($line['product_id'] ?? ''));
            if ($productId === '') {
                continue;
            }

            $parts[] = [
                'id' => trim((string) ($line['id'] ?? '')),
                'product_id' => $productId,
                'product_name' => $names[$productId] ?? $productId,
                'qty' => (int) ($line['qty'] ?? 0),
                'line_total_rupiah' => (int) ($line['line_total_rupiah'] ?? 0),
            ];
        }

        return $parts;
    }

    /**
     * @param list<string> $productIds
     * @return array<string, string>
     */
    private function productNames(array $productIds): array
    {
        $ids = array_values(array_unique(array_filter($productIds)));

        if ($ids === []) {
            return [];
        }

        return DB::table('products')
            ->whereIn('id', $ids)
            ->pluck('nama_barang', 'id')
            ->map(static fn ($name): string => (string) $name)
            ->all();
    }
}
