<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Admin\Procurement;

use App\Application\Procurement\UseCases\FinalizeSupplierPaymentProofDirectUploadHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Throwable;

final class FinalizeSupplierPaymentProofDirectUploadController extends Controller
{
    public function __invoke(
        Request $request,
        FinalizeSupplierPaymentProofDirectUploadHandler $handler,
        string $uploadIntentId,
    ): JsonResponse {
        $actorId = $request->user()?->getAuthIdentifier();
        try {
            $result = $handler->handle(
                $uploadIntentId,
                $actorId === null ? '' : (string) $actorId,
            );
        } catch (Throwable) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Upload bukti pembayaran gagal difinalisasi.',
                'errors' => ['upload_intent' => ['FINALIZE_FAILED']],
            ], 500);
        }

        if ($result->isFailure()) {
            $codes = $result->errors()['upload_intent'] ?? [];
            $status = match (true) {
                in_array('UPLOAD_INTENT_NOT_FOUND', $codes, true) => 404,
                in_array('FINALIZE_NOT_AVAILABLE', $codes, true) => 409,
                default => 422,
            };

            return response()->json($result->toArray(), $status);
        }

        return response()->json($result->toArray());
    }
}
