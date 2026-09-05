<?php

declare(strict_types=1);

namespace App\Adapters\Out\Note\Queries;

final class AdminNoteHistoryCriteria
{
    public function __construct(
        public readonly string $dateFromText,
        public readonly string $dateToText,
        public readonly string $search,
        public readonly string $lineStatus,
        public readonly string $sortBy,
        public readonly string $sortDir,
        public readonly int $page,
        public readonly int $perPage,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public static function fromFilters(array $filters): self
    {
        return new self(
            self::resolveDate($filters, 'date_from'),
            self::resolveDate($filters, 'date_to'),
            self::resolveString($filters, 'search'),
            self::resolveString($filters, 'line_status'),
            self::resolveSortBy($filters['sort_by'] ?? null),
            self::resolveSortDir($filters['sort_dir'] ?? null),
            self::resolvePositiveInt($filters, 'page', 1),
            self::resolvePositiveInt($filters, 'per_page', 10),
        );
    }

    private static function resolveSortBy(mixed $value): string
    {
        $sortBy = is_string($value) ? trim($value) : '';

        return in_array($sortBy, ['created_at', 'total_rupiah', 'net_paid_rupiah', 'outstanding_rupiah'], true)
            ? $sortBy
            : 'created_at';
    }

    private static function resolveSortDir(mixed $value): string
    {
        return is_string($value) && trim($value) === 'asc' ? 'asc' : 'desc';
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private static function resolveDate(array $filters, string $key): string
    {
        $value = $filters[$key] ?? null;

        if (! is_string($value)) {
            return date('Y-m-d');
        }

        $trimmed = trim($value);

        return $trimmed === '' ? date('Y-m-d') : $trimmed;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private static function resolveString(array $filters, string $key): string
    {
        $value = $filters[$key] ?? null;

        if (! is_string($value)) {
            return '';
        }

        return trim($value);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private static function resolvePositiveInt(array $filters, string $key, int $default): int
    {
        $value = $filters[$key] ?? null;

        if (! is_numeric($value)) {
            return $default;
        }

        $number = (int) $value;

        return $number > 0 ? $number : $default;
    }
}
