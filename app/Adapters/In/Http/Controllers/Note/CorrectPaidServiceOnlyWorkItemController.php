<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Note;

use App\Adapters\In\Http\Requests\Note\CorrectPaidServiceOnlyWorkItemRequest;
use App\Application\Note\UseCases\CorrectPaidServiceOnlyWorkItemHandler;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

final class CorrectPaidServiceOnlyWorkItemController extends Controller
{
    public function __invoke(
        string $noteId,
        CorrectPaidServiceOnlyWorkItemRequest $request,
        CorrectPaidServiceOnlyWorkItemHandler $useCase,
    ): RedirectResponse|JsonResponse {
        $data = $request->validated();
        $actorId = (string) $request->user()->getAuthIdentifier();

        $result = $useCase->handle(
            $noteId,
            (int) $data['line_no'],
            (string) $data['service_name'],
            (int) $data['service_price_rupiah'],
            (string) $data['part_source'],
            (string) $data['reason'],
            $actorId,
            (string) $data['base_revision_id'],
        );

        if ($result->isFailure()) {
            if ($request->expectsJson() && in_array('STALE_REVISION', $result->errors()['revision'] ?? [], true)) {
                return response()->json(['success' => false, 'data' => null, 'code' => 'STALE_REVISION', 'message' => $result->message(), 'errors' => ['revision' => ['STALE_REVISION']]], 409);
            }
            return back()->withErrors(['correction' => $result->message() ?? 'Correction nominal gagal disimpan.'])->withInput();
        }

        return redirect()
            ->route('cashier.notes.show', ['noteId' => $noteId])
            ->with('success', 'Correction nominal service_only berhasil disimpan.');
    }
}
