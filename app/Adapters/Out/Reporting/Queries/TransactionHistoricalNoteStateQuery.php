<?php

declare(strict_types=1);

namespace App\Adapters\Out\Reporting\Queries;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class TransactionHistoricalNoteStateQuery
{
    public function latestRevisionNumbers(string $cutoff): Builder
    {
        return DB::table('note_revisions')
            ->where(function (Builder $query) use ($cutoff): void {
                // Revision 1 is the pre-edit baseline even for legacy notes that were
                // bootstrapped immediately before their first later revision.
                $query->where('created_at', '<=', $cutoff)
                    ->orWhere('revision_number', 1);
            })
            ->selectRaw('note_root_id, MAX(revision_number) as revision_number')
            ->groupBy('note_root_id');
    }

    public function applyActiveAtCutoff(Builder $query, string $cutoff): Builder
    {
        return $query
            ->whereNotExists(function (Builder $cancelled) use ($cutoff): void {
                $cancelled->selectRaw('1')
                    ->from('note_mutation_events as cancellation')
                    ->whereColumn('cancellation.note_id', 'notes.id')
                    ->where('cancellation.mutation_type', 'note_cancelled')
                    ->where('cancellation.occurred_at', '<=', $cutoff)
                    ->whereNotExists(function (Builder $restored) use ($cutoff): void {
                        $restored->selectRaw('1')
                            ->from('note_mutation_events as restoration')
                            ->whereColumn('restoration.note_id', 'cancellation.note_id')
                            ->where('restoration.mutation_type', 'note_restored')
                            ->where('restoration.occurred_at', '<=', $cutoff)
                            ->whereColumn('restoration.occurred_at', '>', 'cancellation.occurred_at');
                    });
            })
            ->where(function (Builder $active): void {
                // Modern cancellation is event-backed. A cancelled legacy row with no
                // lifecycle event has no defensible earlier state, so keep it excluded.
                $active->where('notes.note_state', '<>', 'cancelled')
                    ->orWhereExists(function (Builder $event): void {
                        $event->selectRaw('1')
                            ->from('note_mutation_events as any_cancellation')
                            ->whereColumn('any_cancellation.note_id', 'notes.id')
                            ->where('any_cancellation.mutation_type', 'note_cancelled');
                    });
            });
    }
}
