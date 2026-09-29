<?php

declare(strict_types=1);

namespace App\Adapters\Out\Reporting\Queries\ServicePackageProfitBreakdown;

use App\Adapters\Out\Reporting\Queries\TransactionHistoricalNoteStateQuery;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class BreakdownSourceBaseQuery
{
    public function __construct(
        private readonly PartsTotalSubquery $parts,
        private readonly CogsSubqueries $cogs,
        private readonly RefundComponentSubqueries $refunds,
        private readonly TransactionHistoricalNoteStateQuery $historicalNoteState,
    ) {}

    public function build(
        string $fromTransactionDate,
        string $toTransactionDate,
        ?string $asOfDate,
        bool $legacyOnly,
    ): Builder {
        $query = DB::table('work_items')
            ->join('notes', 'notes.id', '=', 'work_items.note_id')
            ->join('work_item_service_details', 'work_item_service_details.work_item_id', '=', 'work_items.id')
            ->leftJoinSub($this->parts->query(), 'parts_totals', static fn ($join) => $join->on('parts_totals.work_item_id', '=', 'work_items.id'))
            ->leftJoinSub($this->cogs->issued($asOfDate), 'issued_cogs', static fn ($join) => $join->on('issued_cogs.work_item_id', '=', 'work_items.id'))
            ->leftJoinSub($this->cogs->returned($asOfDate), 'returned_cogs', static fn ($join) => $join->on('returned_cogs.work_item_id', '=', 'work_items.id'))
            ->leftJoinSub($this->refunds->product($asOfDate), 'refunded_product_components', static fn ($join) => $join->on('refunded_product_components.work_item_id', '=', 'work_items.id'))
            ->leftJoinSub($this->refunds->service($asOfDate), 'refunded_service_components', static fn ($join) => $join->on('refunded_service_components.work_item_id', '=', 'work_items.id'))
            ->where('work_items.transaction_type', 'service_with_store_stock_part')
            ->where('work_items.status', '<>', 'canceled')
            ->whereBetween('notes.transaction_date', [$fromTransactionDate, $toTransactionDate])
            ->when($legacyOnly, static function (Builder $query): void {
                $query->whereNotExists(function (Builder $revisions): void {
                    $revisions->selectRaw('1')
                        ->from('note_revisions')
                        ->whereColumn('note_revisions.note_root_id', 'notes.id');
                });
            });

        if ($legacyOnly && $asOfDate !== null) {
            $this->historicalNoteState->applyRootExistenceAtCutoff(
                $query,
                $asOfDate.' 23:59:59',
            );
        }

        return $query;
    }
}
