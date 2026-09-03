<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Admin\Procurement;

use App\Application\Procurement\UseCases\PrepareSupplierPaymentProofDirectUploadHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\Rule;

final class PrepareSupplierPaymentProofDirectUploadController extends Controller
{
    public function __invoke(Request $request, PrepareSupplierPaymentProofDirectUploadHandler $handler): JsonResponse
    {
        $data = $request->validate([
            'scope_type' => ['required', 'string', Rule::in(['supplier_payment', 'supplier_invoice'])],
            'scope_id' => ['required', 'string', 'max:100'],
            'idempotency_key' => ['required', 'string', 'max:120'],
            'files' => ['required', 'array', 'min:1', 'max:3'],
            'files.*.original_filename' => ['required', 'string', 'max:255'],
            'files.*.mime_type' => [
                'required',
                'string',
                Rule::in(['image/jpeg', 'image/png', 'image/webp', 'image/heic', 'image/heif', 'application/pdf']),
            ],
            'files.*.file_size_bytes' => ['required', 'integer', 'min:1', 'max:10485760'],
        ]);
        $actorId = $request->user()?->getAuthIdentifier();
        $result = $handler->handle(
            (string) $data['scope_type'],
            (string) $data['scope_id'],
            $data['files'],
            $actorId === null ? '' : (string) $actorId,
            (string) $data['idempotency_key'],
        );

        if ($result->isFailure()) {
            $conflict = in_array(
                'SUPPLIER_PAYMENT_PROOF_UPLOAD_IDEMPOTENCY_CONFLICT',
                $result->errors()['idempotency_key'] ?? [],
                true,
            );

            return response()->json($result->toArray(), $conflict ? 409 : 422);
        }

        return response()->json($result->toArray());
    }
}
