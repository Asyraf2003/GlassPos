<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Note;

use App\Adapters\In\Http\Requests\Note\RestoreCancelledNoteRequest;
use App\Application\Note\UseCases\RestoreCancelledNoteHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;

final class RestoreCancelledNoteController extends Controller
{
    public function __invoke(string $noteId, RestoreCancelledNoteRequest $request, RestoreCancelledNoteHandler $handler): JsonResponse|RedirectResponse
    {
        $result = $handler->handle($noteId, (string) $request->user()->getAuthIdentifier(), $request->validated());
        if ($result->isFailure()) {
            $code = (string) ($result->errors()['restore'][0] ?? 'RESTORE_FAILED');
            $message = $this->message($code, $result->message());
            if ($request->expectsJson()) {
                $status = match ($code) {
                    'STALE_REVISION', 'STALE_CANCELLATION', 'NOTE_NOT_CANCELLED', 'IDEMPOTENCY_KEY_PAYLOAD_MISMATCH' => 409,
                    'CANCELLATION_FORBIDDEN', 'CANCELLATION_DATE_FORBIDDEN' => 403,
                    'NOTE_NOT_FOUND' => 404,
                    default => 422,
                };

                return response()->json(['success' => false, 'data' => null, 'code' => $code, 'message' => $message, 'errors' => $result->errors()], $status);
            }

            return back()->withErrors(['restore' => $message])->withInput();
        }
        if ($request->expectsJson()) {
            return response()->json($result->toArray());
        }

        return back()->with('success', $result->message() ?? 'Transaksi dipulihkan sebagai revisi baru.');
    }

    private function message(string $code, ?string $fallback): string
    {
        return match ($code) {
            'NOTE_NOT_CANCELLED' => 'Transaksi ini tidak sedang dibatalkan.',
            'STALE_REVISION', 'STALE_CANCELLATION' => 'Transaksi berubah. Muat ulang sebelum memulihkan.',
            'RESTORE_SOURCE_REVISION_INVALID' => 'Riwayat revisi yang dipilih tidak valid untuk transaksi ini.',
            'RESTORE_EXTERNAL_PURCHASE_UNSUPPORTED' => 'Revisi ini memiliki pembelian eksternal yang belum didukung untuk pemulihan.',
            'INSUFFICIENT_STOCK' => 'Stok saat ini tidak cukup untuk memulihkan transaksi.',
            default => $fallback ?? 'Pemulihan gagal.',
        };
    }
}
