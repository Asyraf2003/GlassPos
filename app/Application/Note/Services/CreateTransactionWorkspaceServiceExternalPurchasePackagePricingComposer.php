<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Core\Shared\Exceptions\DomainException;

final class CreateTransactionWorkspaceServiceExternalPurchasePackagePricingComposer
{
    /**
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     */
    public function compose(array $item): array
    {
        if (($item['pricing_mode'] ?? null) !== 'package_auto_split') {
            return $item;
        }

        $line = $this->firstLine($item['external_purchase_lines'] ?? []);
        $externalTotal = $this->intValue($line['total_rupiah'] ?? null);

        if ($externalTotal <= 0) {
            return $item;
        }

        throw new DomainException('Pembelian luar tidak boleh memakai jalur package auto split sebelum kontrak label + total dikunci.');
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

    private function intValue(mixed $value): int
    {
        return is_int($value) ? $value : 0;
    }
}
