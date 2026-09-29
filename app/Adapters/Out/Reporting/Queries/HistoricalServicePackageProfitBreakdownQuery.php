<?php

declare(strict_types=1);

namespace App\Adapters\Out\Reporting\Queries;

use App\Adapters\Out\Reporting\Queries\ServicePackageProfitBreakdown\BreakdownRowMapper;
use App\Adapters\Out\Reporting\Queries\ServicePackageProfitBreakdown\BreakdownSourceRowsQuery;

final class HistoricalServicePackageProfitBreakdownQuery
{
    public function __construct(
        private readonly BreakdownSourceRowsQuery $legacyRows,
        private readonly BreakdownRowMapper $legacyMapper,
        private readonly HistoricalPackageRevisionRowsQuery $revisionRows,
        private readonly HistoricalPackageSnapshotPayload $payloads,
        private readonly HistoricalPackageFactsQuery $facts,
        private readonly HistoricalPackageRevisionRowMapper $revisionMapper,
        private readonly ServicePackageProfitSummaryBuilder $summaryBuilder,
    ) {}

    /** @return list<array<string, int|string|null>> */
    public function rows(string $from, string $to): array
    {
        $rows = $this->legacyRows->rows($from, $to, $to, true)
            ->map(fn (object $row): array => $this->legacyMapper->map($row))->all();
        $revisionRows = $this->revisionRows->rows($from, $to);
        if ($revisionRows->isEmpty()) {
            return $this->sort($rows);
        }

        $stockIds = [];
        foreach ($revisionRows as $row) {
            $stockIds = array_merge(
                $stockIds,
                $this->payloads->stockLineIds($this->payloads->decode($row->payload ?? null)),
            );
        }
        $workItemIds = $revisionRows->pluck('work_item_id')
            ->map(static fn ($id): string => (string) $id)->filter()->unique()->values()->all();
        $cogs = $this->facts->cogsByStockLine(array_values(array_unique($stockIds)), $to);
        $refunds = $this->facts->refundsByWorkItem($workItemIds, $to);
        foreach ($revisionRows as $row) {
            $rows[] = $this->revisionMapper->map($row, $cogs, $refunds);
        }
        return $this->sort($rows);
    }

    /** @return array<string, int> */
    public function summary(string $from, string $to): array
    {
        return $this->summaryBuilder->build($this->rows($from, $to));
    }

    /** @param list<array<string, int|string|null>> $rows @return list<array<string, int|string|null>> */
    private function sort(array $rows): array
    {
        usort($rows, static fn (array $left, array $right): int => [
            (string) $left['transaction_date'], (string) $left['note_id'], (string) $left['work_item_id'],
        ] <=> [
            (string) $right['transaction_date'], (string) $right['note_id'], (string) $right['work_item_id'],
        ]);
        return $rows;
    }
}
