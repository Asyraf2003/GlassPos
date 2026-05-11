<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Api\V1\Procurement;

use App\Application\IdentityAccess\Services\LoginActorAccessDecision;
use App\Application\MobileApi\Auth\DTO\MobileApiActor;
use App\Application\Procurement\UseCases\AttachSupplierPaymentProofHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

final class UploadMobileApiSupplierPaymentProofController extends Controller
{
    public function __construct(private readonly AttachSupplierPaymentProofHandler $proofs)
    {
    }

    public function __invoke(Request $request, string $supplierPaymentId): JsonResponse
    {
        $actor = $request->attributes->get('mobile_api_actor');

        if (! $actor instanceof MobileApiActor) {
            return $this->unauthenticated();
        }

        if ($actor->role !== LoginActorAccessDecision::ADMIN) {
            return $this->adminOnly();
        }

        $validator = Validator::make($request->all(), [
            'proof_files' => ['required', 'array', 'min:1', 'max:3'],
            'proof_files.*' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:2048'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Bukti pembayaran supplier tidak valid.',
                'errors' => [
                    'supplier_payment_proof' => ['INVALID_SUPPLIER_PAYMENT_PROOF'],
                ],
            ], 422);
        }

        $uploadedFiles = $this->uploadedFiles($request->file('proof_files', []));
        $result = $this->proofs->handle($supplierPaymentId, $uploadedFiles, $actor->id);

        if ($result->isFailure()) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => $result->message(),
                'errors' => $result->errors(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data' => $result->data(),
            'message' => $result->message(),
            'errors' => null,
        ]);
    }

    /**
     * @param mixed $proofFiles
     * @return list<array{source_path:string,original_filename:string,mime_type:string,file_size_bytes:int}>
     */
    private function uploadedFiles(mixed $proofFiles): array
    {
        if (! is_array($proofFiles)) {
            return [];
        }

        $uploadedFiles = [];

        foreach ($proofFiles as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $sourcePath = $file->getRealPath();

            if (! is_string($sourcePath) || $sourcePath === '') {
                continue;
            }

            $uploadedFiles[] = [
                'source_path' => $sourcePath,
                'original_filename' => (string) $file->getClientOriginalName(),
                'mime_type' => (string) $file->getClientMimeType(),
                'file_size_bytes' => (int) $file->getSize(),
            ];
        }

        return $uploadedFiles;
    }

    private function unauthenticated(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'data' => null,
            'message' => 'Autentikasi diperlukan.',
            'errors' => [
                'token' => ['UNAUTHENTICATED'],
            ],
        ], 401);
    }

    private function adminOnly(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'data' => null,
            'message' => 'Akses bukti pembayaran supplier mobile hanya untuk admin.',
            'errors' => [
                'role' => ['ADMIN_ONLY'],
            ],
        ], 403);
    }
}
