<?php

declare(strict_types=1);

namespace App\Adapters\Out\Note\Queries;

use App\Ports\Out\Note\AdminNoteHistoryTableReaderPort;
use Illuminate\Support\Facades\DB;

final class AdminNoteHistoryTableQuery implements AdminNoteHistoryTableReaderPort
{
    public function __construct(
        private readonly AdminNoteHistoryProjectionFilters $filters,
        private readonly AdminNoteHistoryProjectionItemMapper $mapper,
        private readonly CashierNoteHistoryValueFormatter $formatter,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array{
     *   filters: array<string, mixed>,
     *   items: list<array<string, mixed>>,
     *   pagination: array<string, int>,
     *   summary: array{label: string}
     * }
     */
    public function get(array $filters): array
    {
        $criteria = AdminNoteHistoryCriteria::fromFilters($filters);

        $builder = DB::table('note_history_projection')
            ->leftJoin('notes', 'notes.id', '=', 'note_history_projection.note_id')
            ->whereBetween('note_history_projection.transaction_date', [$criteria->dateFromText, $criteria->dateToText])
            ->select([
                'note_history_projection.*',
                'notes.created_at as note_created_at',
            ]);

        $builder = $this->filters->applySearch($builder, $criteria->search);
        $builder = $this->filters->applyLineStatusFilter($builder, $criteria->lineStatus);

        if ($criteria->sortBy === 'relevance' && $criteria->search !== '') {
            $term = mb_strtolower($criteria->search, 'UTF-8');
            $builder->orderByRaw(
                'CASE WHEN LOWER(note_history_projection.note_id) = ? THEN 0 '
                .'WHEN LOWER(note_history_projection.note_id) LIKE ? THEN 1 '
                .'WHEN note_history_projection.customer_name_normalized = ? THEN 2 '
                .'WHEN note_history_projection.customer_name_normalized LIKE ? THEN 3 '
                .'WHEN note_history_projection.customer_phone = ? THEN 4 ELSE 5 END',
                [$term, $term.'%', $term, $term.'%', $criteria->search],
            )->orderByDesc('notes.created_at');
        } else {
            $builder->orderBy($this->sortColumn($criteria->sortBy), $criteria->sortDir);
        }

        $paginator = $builder->orderByDesc('note_history_projection.note_id')
            ->paginate($criteria->perPage, ['*'], 'page', $criteria->page);

        $items = array_map(
            fn (object $row): array => $this->mapper->map($row),
            $paginator->items(),
        );

        return [
            'filters' => [
                'date_from' => $criteria->dateFromText,
                'date_to' => $criteria->dateToText,
                'search' => $criteria->search,
                'line_status' => $criteria->lineStatus,
                'sort_by' => $criteria->sortBy,
                'sort_dir' => $criteria->sortDir,
            ],
            'items' => $items,
            'pagination' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
            'summary' => [
                'label' => sprintf(
                    'Daftar nota admin %s sampai %s.',
                    $this->formatter->date($criteria->dateFromText),
                    $this->formatter->date($criteria->dateToText),
                ),
            ],
        ];
    }

    private function sortColumn(string $sortBy): string
    {
        return match ($sortBy) {
            'created_at' => 'notes.created_at',
            'note_number' => 'note_history_projection.note_id',
            'customer_name' => 'note_history_projection.customer_name_normalized',
            default => 'note_history_projection.'.$sortBy,
        };
    }
}
