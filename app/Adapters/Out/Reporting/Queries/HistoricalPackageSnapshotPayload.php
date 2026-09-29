<?php

declare(strict_types=1);

namespace App\Adapters\Out\Reporting\Queries;

final class HistoricalPackageSnapshotPayload
{
    /** @return array<string, mixed> */
    public function decode(mixed $payload): array
    {
        if (is_array($payload)) {
            return $payload;
        }
        $decoded = json_decode((string) $payload, true);
        return is_array($decoded) ? $decoded : [];
    }

    /** @param array<string, mixed> $payload @return list<array<string, mixed>> */
    public function storeStockLines(array $payload): array
    {
        $source = $payload['store_stock_lines'] ?? [];
        if (! is_array($source)) {
            return [];
        }
        $lines = [];
        foreach ($source as $line) {
            if (is_array($line)) {
                $lines[] = $line;
            }
        }
        return $lines;
    }

    /** @param array<string, mixed> $payload @return list<string> */
    public function stockLineIds(array $payload): array
    {
        $ids = [];
        foreach ($this->storeStockLines($payload) as $line) {
            $id = trim((string) ($line['id'] ?? ''));
            if ($id !== '') {
                $ids[] = $id;
            }
        }
        return $ids;
    }

    /** @param array<string, mixed> $payload */
    public function partsTotal(array $payload): int
    {
        if (array_key_exists('parts_total_rupiah', $payload)) {
            return (int) $payload['parts_total_rupiah'];
        }
        return array_sum(array_map(
            static fn (array $line): int => (int) ($line['line_total_rupiah'] ?? 0),
            $this->storeStockLines($payload),
        ));
    }

    /** @param array<string, mixed> $payload */
    public function servicePrice(array $payload, mixed $fallback): int
    {
        if (array_key_exists('service_price_rupiah', $payload)) {
            return (int) $payload['service_price_rupiah'];
        }
        $service = $payload['service'] ?? null;
        if (is_array($service) && array_key_exists('service_price_rupiah', $service)) {
            return (int) $service['service_price_rupiah'];
        }
        return (int) ($fallback ?? 0);
    }
}
