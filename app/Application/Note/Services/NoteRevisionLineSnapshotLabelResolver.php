<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Core\Note\Revision\NoteRevisionLineSnapshot;

final class NoteRevisionLineSnapshotLabelResolver
{
    public function resolve(NoteRevisionLineSnapshot $line): string
    {
        $service = trim((string) ($line->serviceLabel() ?? ''));
        if ($service !== '') {
            return $service;
        }

        $payload = $line->payload();
        $storeLines = is_array($payload['store_stock_lines'] ?? null) ? $payload['store_stock_lines'] : [];

        if ($storeLines !== []) {
            $first = is_array($storeLines[0]) ? $storeLines[0] : [];
            $label = $this->firstNonEmpty([
                $first['product_name_snapshot'] ?? null,
                $first['product_label'] ?? null,
                $first['product_name'] ?? null,
                $first['product_nama_barang'] ?? null,
            ]);

            if ($label !== null) {
                $remaining = count($storeLines) - 1;
                return $remaining > 0 ? $label . ' +' . $remaining . ' item' : $label;
            }
        }

        $externalLines = is_array($payload['external_purchase_lines'] ?? null) ? $payload['external_purchase_lines'] : [];

        if ($externalLines !== []) {
            $first = is_array($externalLines[0]) ? $externalLines[0] : [];
            $label = $this->firstNonEmpty([
                $first['cost_description'] ?? null,
                $first['description'] ?? null,
            ]);

            if ($label !== null) {
                $remaining = count($externalLines) - 1;
                return $remaining > 0 ? $label . ' +' . $remaining . ' item' : $label;
            }
        }

        return 'Line ' . $line->lineNo();
    }

    private function firstNonEmpty(array $values): ?string
    {
        foreach ($values as $value) {
            if (!is_scalar($value)) {
                continue;
            }

            $text = trim((string) $value);

            if ($text !== '') {
                return $text;
            }
        }

        return null;
    }
}
