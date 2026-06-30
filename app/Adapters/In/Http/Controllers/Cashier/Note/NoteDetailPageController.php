<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Cashier\Note;

use App\Application\Note\Services\CashierNoteDetailPageAccessData;
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
        CashierNoteDetailPageAccessData $accessData,
        NoteDetailPageDataBuilder $builder,
        NoteCorrectionUiOptionsBuilder $options,
        EnsureInitialNoteRevisionExists $ensureInitialRevision,
        UuidPort $uuid,
    ): View {
        try {
            $canView = $accessData->ensureCanView($noteId);
        } catch (DomainException $e) {
            abort(403, $e->getMessage());
        }

        abort_if(! $canView, 404);

        $user = $request->user();
        $actorId = $user !== null ? (string) $user->getAuthIdentifier() : null;

        try {
            $ensureInitialRevision->handle($noteId, trim($noteId).'-r001', $actorId);
        } catch (DomainException $e) {
            abort(500, $e->getMessage());
        }

        $data = $builder->build($noteId);
        abort_if($data === null, 404);

        $paymentAction = route('cashier.notes.payments.store', ['noteId' => $noteId]);
        $refundAction = route('cashier.notes.refunds.store', ['noteId' => $noteId]);
        $oldPaymentIdempotencyKey = $request->old('idempotency_key');
        $paymentIdempotencyKey = is_string($oldPaymentIdempotencyKey) && trim($oldPaymentIdempotencyKey) !== ''
            ? trim($oldPaymentIdempotencyKey)
            : $uuid->generate();
        $oldRefundIdempotencyKey = $request->old('idempotency_key');
        $refundIdempotencyKey = is_string($oldRefundIdempotencyKey) && trim($oldRefundIdempotencyKey) !== ''
            ? trim($oldRefundIdempotencyKey)
            : $uuid->generate();

        return view('shared.notes.show', $data + [
            'backUrl' => route('cashier.notes.index'),
            'detailConfig' => [
                'workspace_edit_route' => 'cashier.notes.workspace.edit',
            ],
            'addRowsAction' => route('cashier.notes.rows.store', ['noteId' => $noteId]),
            'oldRows' => array_values(old('rows', [['line_type' => 'service']])),
            'paymentAction' => $paymentAction,
            'paymentDateDefault' => date('Y-m-d'),
            'paymentModalConfig' => [
                'action' => $paymentAction,
                'date_default' => date('Y-m-d'),
                'idempotency_key' => $paymentIdempotencyKey,
            ],
            'refundAction' => $refundAction,
            'refundDateDefault' => date('Y-m-d'),
            'refundModalConfig' => [
                'action' => $refundAction,
                'date_default' => date('Y-m-d'),
                'idempotency_key' => $refundIdempotencyKey,
            ],
            'statusCorrectionAction' => route('cashier.notes.corrections.status.store', ['noteId' => $noteId]),
            'serviceOnlyCorrectionAction' => route('cashier.notes.corrections.service-only.store', ['noteId' => $noteId]),
        ] + $options->build());
    }
}
