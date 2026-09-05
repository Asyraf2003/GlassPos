<?php

declare(strict_types=1);

namespace App\Application\ProductCatalog\DTO;

final class ProductTableQuery
{
    public function __construct(
        private readonly ?string $q,
        private readonly int $page,
        private readonly int $perPage,
        private readonly string $sortBy,
        private readonly string $sortDir,
        private readonly string $status,
        private readonly ?string $merek,
        private readonly ?int $ukuranMin,
        private readonly ?int $ukuranMax,
        private readonly ?int $hargaMin,
        private readonly ?int $hargaMax,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromValidated(array $data): self
    {
        $q = self::nullableString($data['q'] ?? null);

        return new self(
            $q,
            isset($data['page']) ? (int) $data['page'] : 1,
            isset($data['per_page']) ? (int) $data['per_page'] : 10,
            isset($data['sort_by']) ? (string) $data['sort_by'] : ($q !== null ? 'relevance' : 'nama_barang'),
            isset($data['sort_dir']) ? (string) $data['sort_dir'] : 'asc',
            isset($data['status']) ? (string) $data['status'] : 'active',
            self::nullableString($data['merek'] ?? null),
            isset($data['ukuran_min']) ? (int) $data['ukuran_min'] : null,
            isset($data['ukuran_max']) ? (int) $data['ukuran_max'] : null,
            isset($data['harga_min']) ? (int) $data['harga_min'] : null,
            isset($data['harga_max']) ? (int) $data['harga_max'] : null,
        );
    }

    public function q(): ?string { return $this->q; }
    public function page(): int { return $this->page; }
    public function perPage(): int { return $this->perPage; }
    public function sortBy(): string { return $this->sortBy; }
    public function sortDir(): string { return $this->sortDir; }
    public function status(): string { return $this->status; }
    public function merek(): ?string { return $this->merek; }
    public function ukuranMin(): ?int { return $this->ukuranMin; }
    public function ukuranMax(): ?int { return $this->ukuranMax; }
    public function hargaMin(): ?int { return $this->hargaMin; }
    public function hargaMax(): ?int { return $this->hargaMax; }

    private static function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $normalized = trim($value);

        return $normalized === '' ? null : $normalized;
    }
}
