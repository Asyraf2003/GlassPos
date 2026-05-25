<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Note;

use App\Adapters\In\Http\Requests\Note\StoreTransactionWorkspaceRequest;
use App\Application\Note\Services\DeleteTransactionWorkspaceDraftOperation;
use App\Application\Note\UseCases\CreateTransactionWorkspaceHandler;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;

final class StoreTransactionWorkspaceController extends Controller
{
    public function __invoke(
        StoreTransactionWorkspaceRequest $request,
        CreateTransactionWorkspaceHandler $handler,
        DeleteTransactionWorkspaceDraftOperation $drafts,
    ): RedirectResponse {
        $actorId = $request->user()?->getAuthIdentifier();
        $payload = $request->validated();

        if ($actorId !== null) {
            $payload['_actor_id'] = (string) $actorId;
        }

        $result = $handler->handle($payload);

        if ($result->isFailure()) {
            return back()
                ->withErrors([
                    'workspace' => $result->message() ?? 'Workspace nota gagal disimpan.',
                ])
                ->withInput();
        }

        if ($actorId !== null) {
            $drafts->deleteForActorAndWorkspace((string) $actorId, 'create');
        }

        return redirect()
            ->route('cashier.notes.index')
            ->with('success', $result->message() ?? 'Workspace nota berhasil disimpan.');
    }
}
