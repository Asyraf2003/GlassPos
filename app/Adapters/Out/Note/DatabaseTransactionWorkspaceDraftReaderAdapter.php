<?php

declare(strict_types=1);

namespace App\Adapters\Out\Note;

use App\Ports\Out\Note\TransactionWorkspaceDraftReaderPort;
use Illuminate\Support\Facades\DB;
use JsonException;

final class DatabaseTransactionWorkspaceDraftReaderAdapter implements TransactionWorkspaceDraftReaderPort
{
    private const MAX_AGE_HOURS = 24;

    public function findByActorAndWorkspaceKey(string $actorId, string $workspaceKey): ?array
    {
        $row = DB::table('transaction_workspace_drafts')
            ->where('actor_id', trim($actorId))
            ->where('workspace_key', trim($workspaceKey))
            ->where('updated_at', '>=', now()->subHours(self::MAX_AGE_HOURS))
            ->first();

        if ($row === null) {
            return null;
        }

        return [
            'id' => (string) $row->id,
            'actor_id' => (string) $row->actor_id,
            'workspace_mode' => (string) $row->workspace_mode,
            'workspace_key' => (string) $row->workspace_key,
            'note_id' => $row->note_id !== null ? (string) $row->note_id : null,
            'payload' => $this->decode((string) $row->payload_json),
            'created_at' => (string) $row->created_at,
            'updated_at' => (string) $row->updated_at,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(string $json): array
    {
        try {
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            return is_array($decoded) ? $decoded : [];
        } catch (JsonException) {
            return [];
        }
    }
}
