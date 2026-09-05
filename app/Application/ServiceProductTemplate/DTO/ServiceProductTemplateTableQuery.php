<?php

declare(strict_types=1);

namespace App\Application\ServiceProductTemplate\DTO;

final readonly class ServiceProductTemplateTableQuery
{
    public function __construct(
        public ?string $q,
        public string $status,
        public int $page,
        public int $perPage,
        public ?string $sortBy,
        public string $sortDir,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromValidated(array $data): self
    {
        $q = isset($data['q']) && is_string($data['q']) && trim($data['q']) !== '' ? trim($data['q']) : null;

        return new self(
            $q,
            (string) ($data['status'] ?? 'all'),
            (int) ($data['page'] ?? 1),
            (int) ($data['per_page'] ?? 10),
            isset($data['sort_by']) ? (string) $data['sort_by'] : null,
            (string) ($data['sort_dir'] ?? 'asc'),
        );
    }
}
