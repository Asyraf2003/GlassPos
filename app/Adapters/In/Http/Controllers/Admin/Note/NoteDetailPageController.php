<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Admin\Note;

use App\Application\Note\Services\EnsureInitialNoteRevisionExists;
use App\Application\Note\Services\NoteCorrectionUiOptionsBuilder;
use App\Application\Note\Services\NoteDetailPageDataBuilder;
use App\Core\Shared\Exceptions\DomainException;
use App\Ports\Out\UuidPort;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class NoteDetailPageController extends Controller
{
    public function __invoke(
        string $noteId,
        Request $request,
        NoteDetailPageDataBuilder $builder,
        NoteCorrectionUiOptionsBuilder $options,
        EnsureInitialNoteRevisionExists $ensureInitialRevision,
        UuidPort $uuid,
    ): View {
        $user = $request->user();
        $actorId = $user !== null ? (string) $user->getAuthIdentifier() : null;

        try {
            $ensureInitialRevision->handle($noteId, trim($noteId).'-r001', $actorId);
        } catch (DomainException $e) {
            abort(500, $e->getMessage());
        }

        $data = $builder->build($noteId);
        abort_if($data === null, 404);

        $paymentAction = route('admin.notes.payments.store', ['noteId' => $noteId]);
        $refundAction = route('admin.notes.refunds.store', ['noteId' => $noteId]);
        $oldRefundIdempotencyKey = $request->old('idempotency_key');
        $refundIdempotencyKey = is_string($oldRefundIdempotencyKey) && trim($oldRefundIdempotencyKey) !== ''
            ? trim($oldRefundIdempotencyKey)
            : $uuid->generate();

        return view('shared.notes.show', $data + [
            'backUrl' => route('admin.notes.index'),
            'detailConfig' => [
                'workspace_edit_route' => 'admin.notes.workspace.edit',
            ],
            'addRowsAction' => route('admin.notes.rows.store', ['noteId' => $noteId]),
            'canManageSurplusDisposition' => true,
            'oldRows' => array_values(old('rows', [['line_type' => 'service']])),
            'paymentAction' => $paymentAction,
            'paymentDateDefault' => date('Y-m-d'),
            'paymentModalConfig' => [
                'action' => $paymentAction,
                'date_default' => date('Y-m-d'),
            ],
            'refundAction' => $refundAction,
            'refundDateDefault' => date('Y-m-d'),
            'refundModalConfig' => [
                'action' => $refundAction,
                'date_default' => date('Y-m-d'),
                'idempotency_key' => $refundIdempotencyKey,
            ],
        ] + $options->build());
    }
}
