<?php

declare(strict_types=1);

namespace App\Application\Note\Services\RevisionWorkspace;

use App\Core\Note\Revision\NoteRevision;

final class RevisionSnapshotStoreStockLineTrustMarker
{
    /**
     * @param list<array<string, mixed>> $items
     * @return list<array<string, mixed>>
     */
    public function mark(array $items, ?NoteRevision $currentRevision): array
    {
        if ($currentRevision === null) {
            return $this->clearTrust($items);
        }

        $available = $this->snapshotCounts($currentRevision);

        foreach ($items as $itemIndex => $item) {
            if (! is_array($item)) {
                continue;
            }

            $lines = $item['product_lines'] ?? null;

            if (! is_array($lines) || ! isset($lines[0]) || ! is_array($lines[0])) {
                continue;
            }

            $line = $lines[0];
            $line['_server_trusted_revision_snapshot'] = false;

            if (($line['price_basis'] ?? null) !== 'revision_snapshot') {
                $items[$itemIndex]['product_lines'][0] = $line;

                continue;
            }

            $qty = (int) ($line['qty'] ?? 0);
            $unitPrice = (int) ($line['unit_price_rupiah'] ?? 0);
            $lineTotal = $qty * $unitPrice;
            $key = $this->key((string) ($line['product_id'] ?? ''), $qty, $lineTotal);

            if (($available[$key] ?? 0) > 0) {
                $line['_server_trusted_revision_snapshot'] = true;
                $available[$key]--;
            }

            $items[$itemIndex]['product_lines'][0] = $line;
        }

        return $items;
    }

    /**
     * @param list<array<string, mixed>> $items
     * @return list<array<string, mixed>>
     */
    private function clearTrust(array $items): array
    {
        foreach ($items as $itemIndex => $item) {
            if (! is_array($item)) {
                continue;
            }

            $lines = $item['product_lines'] ?? null;

            if (is_array($lines) && isset($lines[0]) && is_array($lines[0])) {
                $line = $lines[0];
                $line['_server_trusted_revision_snapshot'] = false;
                $items[$itemIndex]['product_lines'][0] = $line;
            }
        }

        return $items;
    }

    /**
     * @return array<string, int>
     */
    private function snapshotCounts(NoteRevision $revision): array
    {
        $counts = [];

        foreach ($revision->lines() as $line) {
            $payload = $line->payload();
            $storeLines = $payload['store_stock_lines'] ?? [];

            if (! is_array($storeLines)) {
                continue;
            }

            foreach ($storeLines as $storeLine) {
                if (! is_array($storeLine)) {
                    continue;
                }

                $key = $this->key(
                    (string) ($storeLine['product_id'] ?? ''),
                    (int) ($storeLine['qty'] ?? 0),
                    (int) ($storeLine['line_total_rupiah'] ?? 0)
                );

                $counts[$key] = ($counts[$key] ?? 0) + 1;
            }
        }

        return $counts;
    }

    private function key(string $productId, int $qty, int $lineTotalRupiah): string
    {
        return trim($productId) . '|' . $qty . '|' . $lineTotalRupiah;
    }
}
