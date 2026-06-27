<?php

declare(strict_types=1);

namespace App\Adapters\Out\Reporting\Queries;

use App\Adapters\Out\Reporting\Queries\ServicePackageProfitBreakdown\BreakdownRowMapper;
use App\Adapters\Out\Reporting\Queries\ServicePackageProfitBreakdown\BreakdownSourceRowsQuery;

final class ServicePackageProfitBreakdownQuery
{
    public function __construct(
        private readonly BreakdownSourceRowsQuery $sourceRows,
        private readonly BreakdownRowMapper $mapper,
    ) {
    }

    /**
     * @return list<array<string, int|string|null>>
     */
    public function rows(string $fromTransactionDate, string $toTransactionDate): array
    {
        return $this->sourceRows
            ->rows($fromTransactionDate, $toTransactionDate)
            ->map(fn (object $row): array => $this->mapper->map($row))
            ->all();
    }

    /**
     * @return array<string, int>
     */
    public function summary(string $fromTransactionDate, string $toTransactionDate): array
    {
        $row = (array) $this->sourceRows->summary($fromTransactionDate, $toTransactionDate);

        return array_map(static fn (mixed $value): int => (int) $value, $row);
    }
}
