<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Core\Shared\Exceptions\DomainException;

final class CreateTransactionWorkspaceServiceExternalPurchasePackagePricingComposer
{
    private const DEFAULT_LABEL = 'Pembelian luar';

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

        $packageTotal = $this->requiredInt($item['package_total_rupiah'] ?? null, 'Harga paket wajib diisi.');

        if ($packageTotal < $externalTotal) {
            throw new DomainException('Harga paket tidak boleh lebih kecil dari total pembelian luar.');
        }

        $service = is_array($item['service'] ?? null) ? $item['service'] : [];
        $service['price_rupiah'] = $packageTotal - $externalTotal;

        $line['label'] = $this->labelOrDefault($line['label'] ?? null);
        $line['qty'] = 1;
        $line['unit_cost_rupiah'] = $externalTotal;

        $item['service'] = $service;
        $item['external_purchase_lines'] = [$line];

        return $item;
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

    private function labelOrDefault(mixed $value): string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : self::DEFAULT_LABEL;
    }

    private function requiredInt(mixed $value, string $message): int
    {
        if (! is_int($value) || $value <= 0) {
            throw new DomainException($message);
        }

        return $value;
    }

    private function intValue(mixed $value): int
    {
        return is_int($value) ? $value : 0;
    }
}
