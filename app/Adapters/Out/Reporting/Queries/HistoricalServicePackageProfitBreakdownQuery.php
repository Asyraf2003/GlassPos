<?php

declare(strict_types=1);

namespace App\Adapters\Out\Reporting\Queries;

use App\Adapters\Out\Reporting\Queries\ServicePackageProfitBreakdown\BreakdownRowMapper;
use App\Adapters\Out\Reporting\Queries\ServicePackageProfitBreakdown\BreakdownSourceRowsQuery;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class HistoricalServicePackageProfitBreakdownQuery
{
    public function __construct(
        private readonly BreakdownSourceRowsQuery $legacyRows,
        private readonly BreakdownRowMapper $mapper,
        private readonly TransactionHistoricalNoteStateQuery $historicalNoteState,
    ) {}

    /** @return list<array<string, int|string|null>> */
    public function rows(string $fromTransactionDate, string $toTransactionDate): array
    {
        $cutoff = $toTransactionDate.' 23:59:59';
        $rows = $this->legacyRows
            ->rows($fromTransactionDate, $toTransactionDate, $toTransactionDate, true)
            ->map(fn (object $row): array => $this->mapper->map($row))
            ->all();

        $latestRevisions = $this->historicalNoteState->latestRevisionNumbers($cutoff);
        $query = DB::table('notes')
            ->joinSub($latestRevisions, 'historical_revision_numbers', fn ($join) => $join->on('historical_revision_numbers.note_root_id', '=', 'notes.id'))
            ->join('note_revisions as revision', function ($join): void {
                $join->on('revision.note_root_id', '=', 'notes.id')
                    ->on('revision.revision_number', '=', 'historical_revision_numbers.revision_number');
            })
            ->join('note_revision_lines as revision_line', 'revision_line.note_revision_id', '=', 'revision.id')
            ->where('revision_line.transaction_type', 'service_with_store_stock_part')
            ->where('revision_line.status', '<>', 'canceled')
            ->whereBetween('revision.transaction_date', [$fromTransactionDate, $toTransactionDate]);
        $this->historicalNoteState->applyActiveAtCutoff($query, $cutoff);

        $revisionRows = $query->orderBy('revision.transaction_date')
            ->orderBy('notes.id')
            ->orderBy('revision_line.line_no')
            ->get([
                'notes.id as note_id',
                'revision.transaction_date',
                'revision.customer_name',
                'revision_line.work_item_root_id as work_item_id',
                'revision_line.subtotal_rupiah as package_sold_amount_rupiah',
                'revision_line.service_price_rupiah',
                'revision_line.payload',
            ]);

        if ($revisionRows->isEmpty()) {
            return $this->sort($rows);
        }

        $stockLineIds = [];
        $payloadByWorkItem = [];
        foreach ($revisionRows as $row) {
            $payload = $this->payload($row->payload ?? null);
            $payloadByWorkItem[(string) $row->work_item_id] = $payload;
            foreach ($this->storeStockLines($payload) as $line) {
                $id = trim((string) ($line['id'] ?? ''));
                if ($id !== '') {
                    $stockLineIds[] = $id;
                }
            }
        }

        $cogsByStockLine = $this->cogsByStockLine(array_values(array_unique($stockLineIds)), $toTransactionDate);
        $refundsByWorkItem = $this->refundsByWorkItem(
            $revisionRows->pluck('work_item_id')->map(static fn ($id): string => (string) $id)->filter()->unique()->values()->all(),
            $toTransactionDate,
        );

        foreach ($revisionRows as $row) {
            $workItemId = (string) $row->work_item_id;
            $payload = $payloadByWorkItem[$workItemId] ?? [];
            $storeStockLines = $this->storeStockLines($payload);
            $partsTotal = array_key_exists('parts_total_rupiah', $payload)
                ? (int) $payload['parts_total_rupiah']
                : array_sum(array_map(static fn (array $line): int => (int) ($line['line_total_rupiah'] ?? 0), $storeStockLines));
            $packageTotal = (int) $row->package_sold_amount_rupiah;
            $servicePrice = (int) ($payload['service_price_rupiah'] ?? $payload['service']['service_price_rupiah'] ?? $row->service_price_rupiah ?? 0);
            $totalService = (int) ($payload['total_service_component_rupiah'] ?? ($packageTotal - $partsTotal));
            $packageProfit = (int) ($payload['package_profit_rupiah'] ?? ($totalService - $servicePrice));
            $cogs = 0;
            foreach ($storeStockLines as $line) {
                $cogs += $cogsByStockLine[(string) ($line['id'] ?? '')] ?? 0;
            }
            $sparepartMargin = $partsTotal - $cogs;
            $refunds = $refundsByWorkItem[$workItemId] ?? ['product' => 0, 'service' => 0];

            $rows[] = [
                'note_id' => (string) $row->note_id,
                'work_item_id' => $workItemId,
                'transaction_date' => (string) $row->transaction_date,
                'customer_name' => (string) $row->customer_name,
                'package_sold_amount_rupiah' => $packageTotal,
                'parts_total_rupiah' => $partsTotal,
                'service_price_rupiah' => $servicePrice,
                'package_base_service_price_rupiah' => array_key_exists('package_base_service_price_rupiah', $payload) && $payload['package_base_service_price_rupiah'] !== null
                    ? (int) $payload['package_base_service_price_rupiah'] : null,
                'package_service_extra_rupiah' => (int) ($payload['package_service_extra_rupiah'] ?? 0),
                'package_profit_rupiah' => $packageProfit,
                'total_service_component_rupiah' => $servicePrice + $packageProfit,
                'refunded_product_component_rupiah' => $refunds['product'],
                'refunded_service_component_rupiah' => $refunds['service'],
                'sparepart_cogs_rupiah' => $cogs,
                'sparepart_margin_rupiah' => $sparepartMargin,
                'total_package_gross_profit_rupiah' => $sparepartMargin + $packageProfit,
            ];
        }

        return $this->sort($rows);
    }

    /** @return array<string, int> */
    public function summary(string $fromTransactionDate, string $toTransactionDate): array
    {
        $rows = $this->rows($fromTransactionDate, $toTransactionDate);
        $summary = [
            'total_packages' => count($rows), 'package_sold_amount_rupiah' => 0, 'parts_total_rupiah' => 0,
            'sparepart_cogs_rupiah' => 0, 'sparepart_margin_rupiah' => 0, 'service_fee_rupiah' => 0,
            'package_profit_rupiah' => 0, 'total_service_component_rupiah' => 0,
            'refunded_product_component_rupiah' => 0, 'refunded_service_component_rupiah' => 0,
            'total_package_gross_profit_rupiah' => 0,
        ];
        $map = ['package_sold_amount_rupiah' => 'package_sold_amount_rupiah', 'parts_total_rupiah' => 'parts_total_rupiah',
            'sparepart_cogs_rupiah' => 'sparepart_cogs_rupiah', 'sparepart_margin_rupiah' => 'sparepart_margin_rupiah',
            'service_fee_rupiah' => 'service_price_rupiah', 'package_profit_rupiah' => 'package_profit_rupiah',
            'total_service_component_rupiah' => 'total_service_component_rupiah',
            'refunded_product_component_rupiah' => 'refunded_product_component_rupiah',
            'refunded_service_component_rupiah' => 'refunded_service_component_rupiah',
            'total_package_gross_profit_rupiah' => 'total_package_gross_profit_rupiah'];
        foreach ($rows as $row) {
            foreach ($map as $summaryKey => $rowKey) {
                $summary[$summaryKey] += (int) ($row[$rowKey] ?? 0);
            }
        }
        return $summary;
    }

    /** @return array<string, mixed> */
    private function payload(mixed $payload): array
    {
        if (is_array($payload)) {
            return $payload;
        }
        $decoded = json_decode((string) $payload, true);
        return is_array($decoded) ? $decoded : [];
    }

    /** @param array<string, mixed> $payload @return list<array<string, mixed>> */
    private function storeStockLines(array $payload): array
    {
        $lines = $payload['store_stock_lines'] ?? [];
        return is_array($lines) ? array_values(array_filter($lines, 'is_array')) : [];
    }

    /** @param list<string> $stockLineIds @return array<string, int> */
    private function cogsByStockLine(array $stockLineIds, string $asOfDate): array
    {
        if ($stockLineIds === []) {
            return [];
        }
        $rows = DB::table('inventory_movements')
            ->whereIn('source_id', $stockLineIds)
            ->whereIn('source_type', ['work_item_store_stock_line', 'work_item_store_stock_line_reversal', 'transaction_workspace_updated'])
            ->where('tanggal_mutasi', '<=', $asOfDate)
            ->get(['source_id', 'movement_type', 'total_cost_rupiah']);
        $totals = [];
        foreach ($rows as $row) {
            $id = (string) $row->source_id;
            $cost = abs((int) $row->total_cost_rupiah);
            $totals[$id] = ($totals[$id] ?? 0) + ((string) $row->movement_type === 'stock_out' ? $cost : -$cost);
        }
        return $totals;
    }

    /** @param list<string> $workItemIds @return array<string, array{product:int,service:int}> */
    private function refundsByWorkItem(array $workItemIds, string $asOfDate): array
    {
        if ($workItemIds === []) {
            return [];
        }
        $rows = DB::table('refund_component_allocations as allocations')
            ->join('customer_refunds as refunds', 'refunds.id', '=', 'allocations.customer_refund_id')
            ->whereIn('allocations.work_item_id', $workItemIds)
            ->where('refunds.refunded_at', '<=', $asOfDate)
            ->get(['allocations.work_item_id', 'allocations.component_type', 'allocations.refunded_amount_rupiah']);
        $totals = [];
        foreach ($rows as $row) {
            $id = (string) $row->work_item_id;
            $totals[$id] ??= ['product' => 0, 'service' => 0];
            $bucket = (string) $row->component_type === 'service_fee' ? 'service' : 'product';
            $totals[$id][$bucket] += (int) $row->refunded_amount_rupiah;
        }
        return $totals;
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
