<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Note;

use App\Adapters\In\Http\Controllers\Note\Support\NoteRouteAreaResolver;
use App\Adapters\In\Http\Requests\Note\StoreNoteRevisionRequest;
use App\Application\Note\UseCases\CreateNoteRevisionHandler;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

final class StoreNoteRevisionController extends Controller
{
    public function __invoke(
        string $noteId,
        StoreNoteRevisionRequest $request,
        CreateNoteRevisionHandler $handler,
        NoteRouteAreaResolver $routes,
    ): RedirectResponse|JsonResponse {
        $user = $request->user();
        $actorId = $user !== null ? (string) $user->getAuthIdentifier() : null;

        // Admin workspace revisions are an explicit post-close correction path.
        // Cashier/default workspace revisions must keep the editability guard enabled.
        $enforceWorkspaceEditability = ! $request->routeIs('admin.notes.workspace.update');

        $result = $handler->handle(
            $noteId,
            $request->validated(),
            $actorId,
            $enforceWorkspaceEditability,
        );

        if ($result->isFailure()) {
            if ($request->expectsJson() && ($result->data()['code'] ?? null) === 'STALE_REVISION') {
                return response()->json(['success' => false, 'data' => null, 'code' => 'STALE_REVISION', 'message' => $result->message(), 'errors' => ['revision' => ['STALE_REVISION']]], 409);
            }
            return back()
                ->withErrors(['revision' => $result->message() ?? 'Revisi nota gagal disimpan.'])
                ->withInput();
        }

        return redirect()
            ->route($routes->showRoute($request), ['noteId' => $noteId])
            ->with('success', $result->message() ?? 'Revisi nota berhasil disimpan.');
    }
}
