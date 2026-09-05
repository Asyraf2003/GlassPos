<?php

declare(strict_types=1);

namespace App\Application\Audit\DTO;

final class AuditLogTableQuery
{
    public function __construct(
        private readonly ?string $q,
        private readonly int $page,
        private readonly string $sortBy,
        private readonly string $sortDir,
        private readonly ?string $source,
    ) {}

    public static function fromValidated(array $data): self
    {
        $q = self::nullableString($data['q'] ?? null);

        return new self(
            $q,
            isset($data['page']) ? (int) $data['page'] : 1,
            isset($data['sort_by']) ? (string) $data['sort_by'] : ($q !== null ? 'relevance' : 'created_at'),
            isset($data['sort_dir']) ? (string) $data['sort_dir'] : 'desc',
            self::nullableString($data['source'] ?? null),
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
        return 20;
    }

    public function sortBy(): string
    {
        return $this->sortBy;
    }

    public function sortDir(): string
    {
        return $this->sortDir;
    }

    public function source(): ?string
    {
        return $this->source;
    }

    private static function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }
        $value = trim($value);

        return $value === '' || $value === 'all' ? null : $value;
    }
}
