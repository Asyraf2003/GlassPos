<?php

declare(strict_types=1);

namespace App\Adapters\Out\Reporting\Queries;

use Illuminate\Database\Query\Builder;

final readonly class TransactionSummaryQueryContext
{
    public function __construct(
        public Builder $query,
        public ?string $cutoff,
        public string $effectiveDateSql,
        public string $effectiveCustomerSql,
        public string $effectiveTotalSql,
    ) {}
}
