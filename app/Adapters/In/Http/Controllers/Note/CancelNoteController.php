<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Note;

use App\Adapters\In\Http\Requests\Note\CancelNoteRequest;
use App\Application\Note\UseCases\CancelNoteHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;

final class CancelNoteController extends Controller
{
    public function __invoke(string $noteId, CancelNoteRequest $request, CancelNoteHandler $handler): JsonResponse|RedirectResponse
    {
        $result = $handler->handle($noteId, (string) $request->user()->getAuthIdentifier(), $request->validated());
        if ($result->isFailure()) {
            $code = (string) ($result->errors()['cancellation'][0] ?? 'CANCELLATION_FAILED');
            $message = $this->message($code, $result->message());
            if ($request->expectsJson()) {
                $status = match ($code) {
                    'STALE_REVISION', 'NOTE_ALREADY_CANCELLED', 'IDEMPOTENCY_KEY_PAYLOAD_MISMATCH' => 409,
                    'CANCELLATION_FORBIDDEN', 'CANCELLATION_DATE_FORBIDDEN' => 403,
                    'NOTE_NOT_FOUND' => 404,
                    default => 422,
                };

                return response()->json(['success' => false, 'data' => null, 'code' => $code, 'message' => $message, 'errors' => $result->errors()], $status);
            }

            return back()->withErrors(['cancellation' => $message])->withInput();
        }
        if ($request->expectsJson()) {
            return response()->json($result->toArray());
        }

        return back()->with('success', $result->message() ?? 'Transaksi dibatalkan.');
    }

    private function message(string $code, ?string $fallback): string
    {
        return match ($code) {
            'REFUND_REQUIRED' => 'Transaksi ini sudah memiliki pembayaran. Lanjutkan melalui Refund.',
            'EXTERNAL_REFUND_REQUIRED' => 'Transaksi memuat pembelian luar. Pembatalan dan refund pembelian luar belum didukung pada alur ini. Transaksi tidak diubah.',
            'STALE_REVISION' => 'Transaksi berubah. Muat ulang sebelum membatalkan.',
            'NOTE_ALREADY_CANCELLED' => 'Transaksi sudah dibatalkan.',
            'CANCELLATION_DATE_FORBIDDEN' => 'Kasir hanya dapat membatalkan transaksi hari ini atau kemarin.',
            'IDEMPOTENCY_KEY_PAYLOAD_MISMATCH' => 'Kunci permintaan sudah digunakan dengan data berbeda.',
            'CANCELLATION_INVENTORY_INCOMPLETE', 'CANCELLATION_INVENTORY_INCONSISTENT' => 'Riwayat stok transaksi belum lengkap. Pembatalan tidak disimpan; minta admin meninjau riwayat stok.',
            'CANCELLATION_HISTORY_UNRESOLVED' => 'Riwayat penyelesaian transaksi belum dapat dipastikan. Pembatalan tidak dilakukan.',
            default => $fallback ?? 'Pembatalan gagal.',
        };
    }
}
