<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Note;

use App\Adapters\In\Http\Controllers\Note\Support\NoteRouteAreaResolver;
use App\Adapters\In\Http\Requests\Note\AddNoteRowsRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use App\Application\Note\Services\BuildAddNoteRowsRevisionPayload;
use App\Application\Note\UseCases\CreateNoteRevisionHandler;
use App\Core\Shared\Exceptions\DomainException;
use Illuminate\Routing\Controller;

final class AddNoteRowsController extends Controller
{
    public function __invoke(
        string $noteId,
        AddNoteRowsRequest $request,
        BuildAddNoteRowsRevisionPayload $payloads,
        CreateNoteRevisionHandler $revisions,
        NoteRouteAreaResolver $routes,
    ): RedirectResponse|JsonResponse {
        $data = $request->validated();
        try {
            $payload = $payloads->build($noteId, $data['base_revision_id'], $data['rows']);
        } catch (DomainException $e) {
            if ($request->expectsJson()) {
                $stale = str_starts_with($e->getMessage(), 'STALE_REVISION:');
                return response()->json(['success' => false, 'code' => $stale ? 'STALE_REVISION' : 'INVALID_NOTE_ROWS', 'message' => $e->getMessage()], $stale ? 409 : 422);
            }
            return back()->withErrors(['note' => $e->getMessage()])->withInput();
        }
        $result = $revisions->handle($noteId, $payload, (string) $request->user()->getAuthIdentifier());

        if ($result->isFailure()) {
            if ($request->expectsJson() && ($result->data()['code'] ?? null) === 'STALE_REVISION') {
                return response()->json(['success' => false, 'code' => 'STALE_REVISION', 'message' => $result->message()], 409);
            }
            return back()->withErrors(['note' => $result->message() ?? 'Baris nota gagal ditambahkan.'])->withInput();
        }

        return redirect()
            ->route($routes->showRoute($request), ['noteId' => $noteId])
            ->with('success', 'Baris nota berhasil ditambahkan.');
    }
}
