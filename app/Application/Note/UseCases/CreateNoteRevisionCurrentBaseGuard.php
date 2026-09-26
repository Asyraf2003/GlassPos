<?php

declare(strict_types=1);

namespace App\Application\Note\UseCases;

use App\Application\Note\Services\EditableWorkspaceNoteGuard;
use App\Core\Note\Note\Note;
use App\Core\Note\Revision\NoteRevision;

final class CreateNoteRevisionCurrentBaseGuard
{
    public function __construct(private readonly EditableWorkspaceNoteGuard $guard) {}

    public function validate(Note $root, NoteRevision $current, array $payload, bool $enforceWorkspaceEditability): ?CreateNoteRevisionResult
    {
        if (trim((string) ($payload['base_revision_id'] ?? '')) !== $current->id()) {
            return CreateNoteRevisionResult::failure('STALE_REVISION: Nota telah berubah. Muat ulang editor sebelum menyimpan.', ['code' => 'STALE_REVISION']);
        }

        if ($enforceWorkspaceEditability) {
            $this->guard->assertEditable($root->id());
        }

        return null;
    }
}
