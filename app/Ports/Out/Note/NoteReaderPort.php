<?php

declare(strict_types=1);

namespace App\Ports\Out\Note;

use App\Core\Note\Note\Note;

interface NoteReaderPort
{
    public function getById(string $id): ?Note;

    public function getByIdForUpdate(string $id): ?Note;

    public function countAll(): int;
}
