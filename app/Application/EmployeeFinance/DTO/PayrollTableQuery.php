<?php

declare(strict_types=1);

namespace App\Application\EmployeeFinance\DTO;

final class PayrollTableQuery
{
    public function __construct(
        private readonly ?string $q,
        private readonly int $page,
        private readonly int $perPage,
        private readonly string $sortBy,
        private readonly string $sortDir,
        private readonly ?string $mode,
        private readonly string $status,
        private readonly ?string $dateFrom,
        private readonly ?string $dateTo,
    ) {}

    public static function fromValidated(array $data): self
    {
        $q = self::nullableString($data['q'] ?? null);

        return new self(
            $q,
            isset($data['page']) ? (int) $data['page'] : 1,
            isset($data['per_page']) ? (int) $data['per_page'] : 10,
            isset($data['sort_by']) ? (string) $data['sort_by'] : ($q !== null ? 'relevance' : 'disbursement_date'),
            isset($data['sort_dir']) ? (string) $data['sort_dir'] : ($q !== null ? 'asc' : 'desc'),
            self::nullableString($data['mode'] ?? null),
            isset($data['status']) ? (string) $data['status'] : 'all',
            self::nullableString($data['date_from'] ?? null),
            self::nullableString($data['date_to'] ?? null),
        );
    }

    public function q(): ?string
    {
        return $this->q;
    }

    public function page(): int
    {
        return $this->page;
    }

    public function perPage(): int
    {
        return $this->perPage;
    }

    public function sortBy(): string
    {
        return $this->sortBy;
    }

    public function sortDir(): string
    {
        return $this->sortDir;
    }

    public function mode(): ?string
    {
        return $this->mode;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function dateFrom(): ?string
    {
        return $this->dateFrom;
    }

    public function dateTo(): ?string
    {
        return $this->dateTo;
    }

    private static function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }
        $normalized = trim($value);

        return $normalized === '' ? null : $normalized;
    }
}
