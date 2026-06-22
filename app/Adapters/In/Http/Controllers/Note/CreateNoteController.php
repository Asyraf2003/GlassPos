<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Note;

use App\Adapters\In\Http\Requests\Note\CreateNoteRequest;
use App\Application\Note\UseCases\CreateNoteHandler;
use App\Ports\Out\TransactionManagerPort;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Throwable;

final class CreateNoteController extends Controller
{
    public function __invoke(
        CreateNoteRequest $request,
        CreateNoteHandler $createNote,
        CreateNoteRowsAction $addRows,
        TransactionManagerPort $transactions,
    ): RedirectResponse {
        $data = $request->validated();

        $transactions->begin();

        try {
            $createResult = $createNote->handle(
                (string) $data['customer_name'],
                is_string($data['customer_phone'] ?? null) ? $data['customer_phone'] : null,
                (string) $data['transaction_date'],
            );

            if ($createResult->isFailure()) {
                $transactions->rollBack();

                return back()->withErrors([
                    'note' => $createResult->message() ?? 'Nota gagal dibuat.',
                ])->withInput();
            }

            $noteId = $this->extractNoteId($createResult->data());

            if ($noteId === null) {
                $transactions->rollBack();

                return back()->withErrors([
                    'note' => 'ID nota tidak ditemukan setelah create.',
                ])->withInput();
            }

            $rowFailure = $addRows->handle($noteId, $data['rows'] ?? []);

            if ($rowFailure !== null) {
                $transactions->rollBack();

                return back()->withErrors([
                    'note' => $rowFailure->message() ?? 'Baris nota gagal ditambahkan.',
                ])->withInput();
            }

            $transactions->commit();

            return redirect()
                ->route('cashier.notes.show', ['noteId' => $noteId])
                ->with('success', 'Nota berhasil dibuat.');
        } catch (Throwable $e) {
            $transactions->rollBack();

            throw $e;
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function extractNoteId(array $payload): ?string
    {
        $noteId = $payload['id'] ?? null;

        if (! is_string($noteId) || $noteId === '') {
            return null;
        }

        return $noteId;
    }
}
