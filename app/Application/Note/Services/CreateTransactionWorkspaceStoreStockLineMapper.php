<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Core\Shared\Exceptions\DomainException;

final class CreateTransactionWorkspaceStoreStockLineMapper
{
    /**
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     */
    public function map(array $item): array
    {
        return $this->mapLine($this->firstLine($item['product_lines'] ?? []));
    }

    /**
     * @param array<string, mixed> $item
     * @return list<array<string, mixed>>
     */
    public function mapMany(array $item): array
    {
        $lines = $this->lines($item['product_lines'] ?? []);

        if ($lines === []) {
            return [$this->map($item)];
        }

        return array_map(fn (array $line): array => $this->mapLine($line), $lines);
    }

    /**
     * @param array<string, mixed> $line
     * @return array<string, mixed>
     */
    private function mapLine(array $line): array
    {
        $qty = $this->requiredInt($line['qty'] ?? null, 'Qty produk wajib diisi.');
        $unitPrice = $this->requiredInt($line['unit_price_rupiah'] ?? null, 'Harga satuan produk wajib diisi.');

        return [
            'product_id' => $this->requiredString($line['product_id'] ?? null, 'Product wajib dipilih.'),
            'qty' => $qty,
            'line_total_rupiah' => $qty * $unitPrice,
            'price_basis' => $this->priceBasis($line['price_basis'] ?? null),
            '_server_trusted_revision_snapshot' => ($line['_server_trusted_revision_snapshot'] ?? false) === true,
        ];
    }

    /**
     * @param mixed $value
     * @return list<array<string, mixed>>
     */
    private function lines(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        if ($this->looksLikeLine($value)) {
            return [$value];
        }

        $lines = [];

        foreach (array_values($value) as $line) {
            if (is_array($line)) {
                $lines[] = $line;
            }
        }

        return $lines;
    }

    /**
     * @param mixed $value
     * @return array<string, mixed>
     */
    private function firstLine(mixed $value): array
    {
        return $this->lines($value)[0] ?? [];
    }

    /**
     * @param array<mixed> $value
     */
    private function looksLikeLine(array $value): bool
    {
        return array_key_exists('product_id', $value)
            || array_key_exists('qty', $value)
            || array_key_exists('unit_price_rupiah', $value)
            || array_key_exists('price_basis', $value);
    }

    private function priceBasis(mixed $value): string
    {
        return $value === 'revision_snapshot' ? 'revision_snapshot' : 'current_catalog';
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
}
