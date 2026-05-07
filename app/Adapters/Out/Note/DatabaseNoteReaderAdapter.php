<?php

declare(strict_types=1);

namespace App\Adapters\Out\Note;

use App\Adapters\Out\Note\Mappers\NoteMapper;
use App\Adapters\Out\Note\Mappers\WorkItemMapper;
use App\Core\Note\Note\Note;
use App\Ports\Out\Note\NoteReaderPort;
use Illuminate\Support\Facades\DB;

final class DatabaseNoteReaderAdapter implements NoteReaderPort
{
    public function __construct(
        private readonly DatabaseNoteWorkItemDetailLoader $details,
    ) {
    }

    public function getById(string $id): ?Note
    {
        return $this->loadById($id, false);
    }

    public function getByIdForUpdate(string $id): ?Note
    {
        return $this->loadById($id, true);
    }

    private function loadById(string $id, bool $forUpdate): ?Note
    {
        $query = DB::table('notes')->where('id', trim($id));

        if ($forUpdate) {
            $query->lockForUpdate();
        }

        $noteRow = $query->first();

        if (!$noteRow) {
            return null;
        }

        $itemRows = DB::table('work_items')
            ->where('note_id', $noteRow->id)
            ->orderBy('line_no')
            ->get()
            ->all();

        $ids = array_map(fn ($row) => (string) $row->id, $itemRows);

        $detailMap = $this->details->loadDetails($ids);
        $externalMap = $this->details->loadExternals($ids);
        $stockMap = $this->details->loadStocks($ids);

        $workItems = array_map(
            fn ($row) => WorkItemMapper::map($row, $detailMap, $externalMap, $stockMap),
            $itemRows
        );

        return NoteMapper::map($noteRow, $workItems);
    }

    public function countAll(): int
    {
        return (int) DB::table('notes')->count();
    }
}
