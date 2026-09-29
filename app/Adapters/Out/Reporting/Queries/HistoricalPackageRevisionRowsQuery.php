<?php

declare(strict_types=1);

namespace App\Adapters\Out\Reporting\Queries;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class HistoricalPackageRevisionRowsQuery
{
    public function __construct(
        private readonly TransactionHistoricalNoteStateQuery $historicalNoteState,
    ) {}

    /** @return Collection<int, object> */
    public function rows(string $from, string $to): Collection
    {
        $cutoff = $to.' 23:59:59';
        $query = DB::table('notes')
            ->joinSub(
                $this->historicalNoteState->latestRevisionNumbers($cutoff),
                'historical_revision_numbers',
                fn ($join) => $join->on('historical_revision_numbers.note_root_id', '=', 'notes.id'),
            )
            ->join('note_revisions as revision', function ($join): void {
                $join->on('revision.note_root_id', '=', 'notes.id')
                    ->on('revision.revision_number', '=', 'historical_revision_numbers.revision_number');
            })
            ->join('note_revision_lines as revision_line', 'revision_line.note_revision_id', '=', 'revision.id')
            ->where('revision_line.transaction_type', 'service_with_store_stock_part')
            ->where('revision_line.status', '<>', 'canceled')
            ->whereBetween('revision.transaction_date', [$from, $to]);
        $this->historicalNoteState->applyActiveAtCutoff($query, $cutoff);

        return $query->orderBy('revision.transaction_date')
            ->orderBy('notes.id')->orderBy('revision_line.line_no')
            ->get([
                'notes.id as note_id', 'revision.transaction_date', 'revision.customer_name',
                'revision_line.work_item_root_id as work_item_id',
                'revision_line.line_no as package_line_no',
                'revision_line.subtotal_rupiah as package_sold_amount_rupiah',
                'revision_line.service_price_rupiah', 'revision_line.payload',
            ]);
    }
}
