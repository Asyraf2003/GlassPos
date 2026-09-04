<?php

declare(strict_types=1);

namespace App\Adapters\Out\Note\Queries;

use DateTimeImmutable;

final class CashierNoteHistoryCriteria
{
    public const BUCKET_UNFINISHED = 'unfinished';

    public const BUCKET_COMPLETED = 'completed';

    public function __construct(
        public readonly string $anchorDateText,
        public readonly string $previousDateText,
        public readonly string $search,
        public readonly string $bucket,
        public readonly int $page,
        public readonly int $perPage,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public static function fromFilters(array $filters): self
    {
        $anchorDate = self::resolveAnchorDate();

        return new self(
            anchorDateText: $anchorDate->format('Y-m-d'),
            previousDateText: $anchorDate->modify('-1 day')->format('Y-m-d'),
            search: self::normalizeString($filters['search'] ?? null),
            bucket: self::resolveBucket($filters['bucket'] ?? null),
            page: max((int) ($filters['page'] ?? 1), 1),
            perPage: 10,
        );
    }

    private static function resolveAnchorDate(): DateTimeImmutable
    {
        return new DateTimeImmutable(date('Y-m-d'));
    }

    private static function normalizeString(mixed $value): string
    {
        return is_string($value) ? trim($value) : '';
    }

    private static function resolveBucket(mixed $value): string
    {
        return self::normalizeString($value) === self::BUCKET_COMPLETED
            ? self::BUCKET_COMPLETED
            : self::BUCKET_UNFINISHED;
    }
}
