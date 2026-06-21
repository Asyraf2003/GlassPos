<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Core\Shared\Exceptions\DomainException;

final class CreateTransactionWorkspaceExternalPurchaseLineMapper
{
    /**
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     */
	    public function map(array $item): array
	    {
	        $line = $this->firstLine($item['external_purchase_lines'] ?? []);
	        $total = $this->positiveInt($line['total_rupiah'] ?? null);
	        $qty = $total > 0 ? 1 : $this->requiredInt($line['qty'] ?? null, 'Qty pembelian luar wajib diisi.');
	        $unitCost = $total > 0
	            ? $total
	            : $this->requiredInt($line['unit_cost_rupiah'] ?? null, 'Biaya pembelian luar wajib diisi.');

	        return [
	            'cost_description' => $this->requiredString($line['label'] ?? null, 'Label pembelian luar wajib diisi.'),
	            'qty' => $qty,
	            'unit_cost_rupiah' => $unitCost,
	        ];
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

    private function requiredString(mixed $value, string $message): string
    {
        if (! is_string($value) || trim($value) === '') {
            throw new DomainException($message);
        }

        return trim($value);
    }

	    private function requiredInt(mixed $value, string $message): int
	    {
	        if (! is_int($value) || $value <= 0) {
	            throw new DomainException($message);
	        }

	        return $value;
	    }

	    private function positiveInt(mixed $value): int
	    {
	        return is_int($value) && $value > 0 ? $value : 0;
	    }
	}
