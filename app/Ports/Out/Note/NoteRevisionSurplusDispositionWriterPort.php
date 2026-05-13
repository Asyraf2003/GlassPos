<?php

declare(strict_types=1);

namespace App\Ports\Out\Note;

use App\Application\Note\DTO\NoteRevisionSurplusDisposition;

interface NoteRevisionSurplusDispositionWriterPort
{
    public function create(NoteRevisionSurplusDisposition $disposition): void;
}
