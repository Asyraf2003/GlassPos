<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Admin\Note;

use App\Adapters\In\Http\Requests\Note\CreateNoteRevisionSurplusRefundDueRequest;
use App\Application\Note\UseCases\CreateNoteRevisionSurplusRefundDueCommand;
use App\Application\Note\UseCases\CreateNoteRevisionSurplusRefundDueHandler;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;

final class CreateNoteRevisionSurplusRefundDueController extends Controller
{
    public function __invoke(
        string $settlementId,
        CreateNoteRevisionSurplusRefundDueRequest $request,
        CreateNoteRevisionSurplusRefundDueHandler $handler,
    ): RedirectResponse {
        $data = $request->validated();
        $user = $request->user();

        $actorId = $user !== null ? (string) $user->getAuthIdentifier() : '';

        $result = $handler->handle(new CreateNoteRevisionSurplusRefundDueCommand(
            noteRevisionSettlementId: trim($settlementId),
            amountRupiah: (int) $data['amount_rupiah'],
            reason: (string) $data['reason'],
            actorId: $actorId,
            actorRole: 'admin',
            occurredAt: null,
            sourceChannel: 'web_admin',
            requestId: $request->headers->get('X-Request-Id'),
            correlationId: $request->headers->get('X-Correlation-Id'),
        ));

        if ($result->isFailure()) {
            return back()
                ->withErrors(['refund_due' => $result->message() ?? 'Refund due gagal dicatat.'])
                ->withInput();
        }

        $noteRootId = (string) ($result->data()['note_root_id'] ?? '');

        return redirect()
            ->route('admin.notes.show', ['noteId' => $noteRootId])
            ->with('success', 'Refund due berhasil dicatat.');
    }
}
