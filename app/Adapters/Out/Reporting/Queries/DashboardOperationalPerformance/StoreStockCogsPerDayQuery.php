<?php

declare(strict_types=1);

namespace App\Adapters\Out\Reporting\Queries\DashboardOperationalPerformance;

use Illuminate\Support\Facades\DB;

final class StoreStockCogsPerDayQuery
{
    private const REVISION_REVERSE_SOURCE_TYPE = 'transaction_workspace_updated';

    /**
     * @return list<array{
     *   period_key:string,
     *   period_label:string,
     *   amount_rupiah:int
     * }>
     */
    public function rows(string $fromDate, string $toDate): array
    {
        $issuedRows = DB::table('inventory_movements')
            ->where('movement_type', 'stock_out')
            ->where('source_type', 'work_item_store_stock_line')
            ->whereBetween('tanggal_mutasi', [$fromDate, $toDate])
            ->selectRaw(
                'tanggal_mutasi as period_key, ' .
                'COALESCE(SUM(ABS(total_cost_rupiah)), 0) as amount_rupiah'
            )
            ->groupBy('tanggal_mutasi')
            ->pluck('amount_rupiah', 'period_key')
            ->all();

        $returnedRows = DB::table('inventory_movements')
            ->where('movement_type', 'stock_in')
            ->where('source_type', 'work_item_store_stock_line_reversal')
            ->whereBetween('tanggal_mutasi', [$fromDate, $toDate])
            ->selectRaw(
                'tanggal_mutasi as period_key, ' .
                'COALESCE(SUM(total_cost_rupiah), 0) as amount_rupiah'
            )
            ->groupBy('tanggal_mutasi')
            ->pluck('amount_rupiah', 'period_key')
            ->all();

        $revisionReversedRows = DB::table('inventory_movements')
            ->where('movement_type', 'stock_in')
            ->where('source_type', self::REVISION_REVERSE_SOURCE_TYPE)
            ->whereBetween('tanggal_mutasi', [$fromDate, $toDate])
            ->selectRaw(
                'tanggal_mutasi as period_key, ' .
                'COALESCE(SUM(total_cost_rupiah), 0) as amount_rupiah'
            )
            ->groupBy('tanggal_mutasi')
            ->pluck('amount_rupiah', 'period_key')
            ->all();

        $periodKeys = array_unique([
            ...array_keys($issuedRows),
            ...array_keys($returnedRows),
            ...array_keys($revisionReversedRows),
        ]);

        sort($periodKeys);

        return array_map(
            static fn (string $periodKey): array => [
                'period_key' => $periodKey,
                'period_label' => $periodKey,
                'amount_rupiah' => (int) ($issuedRows[$periodKey] ?? 0)
                    - (int) ($returnedRows[$periodKey] ?? 0)
                    - (int) ($revisionReversedRows[$periodKey] ?? 0),
            ],
            $periodKeys
        );
    }
}
