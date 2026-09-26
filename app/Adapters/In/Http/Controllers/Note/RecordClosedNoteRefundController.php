<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Note;

use App\Adapters\In\Http\Controllers\Note\Support\ClosedNoteRefundResponseFactory;
use App\Adapters\In\Http\Controllers\Note\Support\NoteRouteAreaResolver;
use App\Adapters\In\Http\Requests\Note\RecordClosedNoteRefundRequest;
use App\Application\Note\Services\SelectedNoteRowsRefundPlanResolver;
use App\Application\Payment\DTO\SelectedRowsRefundPlan;
use App\Application\Payment\Services\RecordSelectedRowsRefundIdempotencyService;
use App\Application\Payment\Services\RecordSelectedRowsRefundPlanTransaction;
use App\Ports\Out\Note\NoteReaderPort;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;

final class RecordClosedNoteRefundController extends Controller
{
    public function __invoke(
        string $noteId,
        RecordClosedNoteRefundRequest $request,
        SelectedNoteRowsRefundPlanResolver $plans,
        RecordSelectedRowsRefundPlanTransaction $transaction,
        NoteRouteAreaResolver $routes,
        NoteReaderPort $notes,
        RecordSelectedRowsRefundIdempotencyService $idempotency,
        ClosedNoteRefundResponseFactory $responses,
    ): RedirectResponse {
        $data = $request->validated();
        $data['stock_returns'] = array_map(static fn ($value): bool => (bool) $value, $data['stock_returns'] ?? []);
        $data['selected_row_ids'] = array_map('trim', $data['selected_row_ids']);
        sort($data['selected_row_ids']);
        $actorId = (string) $request->user()->getAuthIdentifier();
        $actorRole = $request->routeIs('admin.notes.*') ? 'admin' : 'kasir';
        $idempotencyPayload = $data + [
            '_actor_id' => $actorId,
            '_note_id' => trim($noteId),
        ];

        $replayed = $idempotency->replay($idempotencyPayload);

        if ($replayed !== null) {
            return $replayed->isFailure()
                ? $responses->failed($replayed->message())
                : $responses->success($request, $routes, $replayed->message());
        }

        $selectedRowIds = $data['selected_row_ids'];

        $note = $notes->getById(trim($noteId));

        if ($note === null) {
            return $responses->failed('Nota tidak ditemukan.');
        }

        $planResult = $plans->resolve($noteId, $selectedRowIds, $data['stock_returns'] ?? []);

        if ($planResult->isFailure()) {
            return $responses->failed($planResult->message());
        }

        $plan = $planResult->data()['plan'] ?? null;

        if (! $plan instanceof SelectedRowsRefundPlan) {
            return $responses->failed('Refund plan tidak valid.');
        }

        $result = $transaction->run(
            $plan,
            (string) $data['refunded_at'],
            (string) $data['reason'],
            $actorId,
            $actorRole,
            $idempotencyPayload,
        );

        return $result->isFailure()
            ? $responses->failed($result->message())
            : $responses->success($request, $routes, $result->message());
    }
}
