<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Cashier\Note\Support;

use App\Application\Note\Services\TransactionWorkspaceDraftData;
use Illuminate\Http\Request;

final class CreateTransactionWorkspaceDraftPayloadLoader
{
    public function __construct(
        private readonly TransactionWorkspaceDraftData $drafts,
    ) {
    }

    /** @return array<string, mixed> */
    public function load(Request $request, bool $sessionHasOldInput): array
    {
        if ($sessionHasOldInput) {
            return [];
        }

        $actorId = $request->user()?->getAuthIdentifier();
        if ($actorId === null) {
            return [];
        }

        $draft = $this->drafts->findByActorAndWorkspaceKey((string) $actorId, 'create');
        $payload = $draft['payload'] ?? null;

        return is_array($payload) ? $payload : [];
    }
}
