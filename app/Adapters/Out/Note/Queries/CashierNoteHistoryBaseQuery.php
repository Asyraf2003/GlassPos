<?php

declare(strict_types=1);

namespace App\Adapters\Out\Note\Queries;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class CashierNoteHistoryBaseQuery
{
    public function __construct(
        private readonly NoteHistoryAggregationSubqueries $aggregations,
    ) {}

    public function paginate(CashierNoteHistoryCriteria $criteria): LengthAwarePaginator
    {
        $query = DB::table('note_history_projection')
            ->leftJoin('notes', 'notes.id', '=', 'note_history_projection.note_id')
            ->leftJoinSub(
                $this->aggregations->workSummary(),
                'work_summary',
                fn ($join) => $join->on('work_summary.note_id', '=', 'note_history_projection.note_id')
            )
            ->select([
                'note_history_projection.note_id as id',
                'note_history_projection.transaction_date',
                'note_history_projection.note_state',
                'note_history_projection.customer_name',
                'note_history_projection.customer_phone',
                'note_history_projection.total_rupiah',
                'note_history_projection.allocated_rupiah',
                'note_history_projection.refunded_rupiah',
                'note_history_projection.net_paid_rupiah',
                'note_history_projection.outstanding_rupiah',
                'note_history_projection.line_open_count',
                'note_history_projection.line_close_count',
                'note_history_projection.line_refund_count',
                DB::raw('COALESCE(work_summary.open_count, 0) as open_count'),
                DB::raw('COALESCE(work_summary.done_count, 0) as done_count'),
                DB::raw('COALESCE(work_summary.canceled_count, 0) as canceled_count'),
                'notes.created_at',
            ])
            ->whereBetween('note_history_projection.transaction_date', [
                $criteria->previousDateText,
                $criteria->anchorDateText,
            ]);

        $query = $this->applySearch($query, $criteria->search);
        $query = $this->applyBucket($query, $criteria->bucket);

        return $query
            ->orderByDesc('notes.created_at')
            ->orderByDesc('note_history_projection.note_id')
            ->paginate($criteria->perPage, ['*'], 'page', $criteria->page);
    }

    private function applySearch(Builder $query, string $search): Builder
    {
        if ($search === '') {
            return $query;
        }

        $normalizedSearch = mb_strtolower(trim($search), 'UTF-8');

        return $query->where(function (Builder $builder) use ($search, $normalizedSearch): void {
            $builder
                ->where('note_history_projection.note_id', 'like', '%'.$search.'%')
                ->orWhere('note_history_projection.customer_name', 'like', '%'.$search.'%')
                ->orWhere('note_history_projection.customer_name_normalized', 'like', '%'.$normalizedSearch.'%')
                ->orWhere('note_history_projection.customer_phone', 'like', '%'.$search.'%');
        });
    }

    private function applyBucket(Builder $query, string $bucket): Builder
    {
        if ($bucket === CashierNoteHistoryCriteria::BUCKET_COMPLETED) {
            return $query->where(function (Builder $builder): void {
                $builder
                    ->where('note_history_projection.note_state', 'refunded')
                    ->orWhere(function (Builder $settled): void {
                        $settled
                            ->where('note_history_projection.outstanding_rupiah', '<=', 0)
                            ->whereRaw('COALESCE(work_summary.open_count, 0) = 0');
                    });
            });
        }

        return $query
            ->where('note_history_projection.note_state', '!=', 'refunded')
            ->where(function (Builder $builder): void {
                $builder
                    ->where('note_history_projection.outstanding_rupiah', '>', 0)
                    ->orWhereRaw('COALESCE(work_summary.open_count, 0) > 0');
            });
    }
}
