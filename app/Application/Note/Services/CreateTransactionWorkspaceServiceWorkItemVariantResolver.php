<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

final class CreateTransactionWorkspaceServiceWorkItemVariantResolver
{
    /**
     * @param array<string, mixed> $item
     */
    public function hasStoreStockLines(array $item): bool
    {
        $line = $this->firstLine($item['product_lines'] ?? []);

        return is_string($line['product_id'] ?? null)
            && trim((string) $line['product_id']) !== ''
            && is_int($line['qty'] ?? null)
            && (int) $line['qty'] > 0
            && is_int($line['unit_price_rupiah'] ?? null)
            && (int) $line['unit_price_rupiah'] > 0;
    }

    /**
     * @param array<string, mixed> $item
     */
    public function hasExternalPurchaseLines(array $item): bool
    {
        $line = $this->firstLine($item['external_purchase_lines'] ?? []);

	        return is_string($line['label'] ?? null)
	            && trim((string) $line['label']) !== ''
	            && (
	                (is_int($line['total_rupiah'] ?? null) && (int) $line['total_rupiah'] > 0)
	                || (
	                    is_int($line['qty'] ?? null)
	                    && (int) $line['qty'] > 0
	                    && is_int($line['unit_cost_rupiah'] ?? null)
	                    && (int) $line['unit_cost_rupiah'] > 0
	                )
	            );
	    }

    /**
     * @param mixed $value
     * @return array<string, mixed>
     */
    private function firstLine(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $first = array_values($value)[0] ?? [];

        return is_array($first) ? $first : [];
    }
}
