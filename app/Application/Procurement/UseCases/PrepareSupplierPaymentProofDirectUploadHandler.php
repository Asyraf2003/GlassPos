<?php

declare(strict_types=1);

namespace App\Application\Procurement\UseCases;

use App\Application\Procurement\Services\SupplierPaymentProof\SupplierPaymentProofDirectUploadRequestValidator;
use App\Application\Procurement\Services\SupplierPaymentProof\SupplierPaymentProofPrepareIntentService;
use App\Application\Procurement\Services\SupplierPaymentProof\SupplierPaymentProofPrepareResponse;
use App\Application\Shared\DTO\Result;
use Throwable;

final class PrepareSupplierPaymentProofDirectUploadHandler
{
    public function __construct(
        private readonly SupplierPaymentProofDirectUploadRequestValidator $validator,
        private readonly SupplierPaymentProofPrepareIntentService $intents,
        private readonly SupplierPaymentProofPrepareResponse $response,
    ) {}

    /** @param list<array<string,mixed>> $files */
    public function handle(
        string $scopeType,
        string $scopeId,
        array $files,
        string $actorId,
        string $idempotencyKey,
    ): Result {
        try {
            $validated = $this->validator->validate($scopeType, $scopeId, $files, $actorId, $idempotencyKey);

            if ($validated->isFailure()) {
                return $validated;
            }

            /** @var array<string,mixed> $request */
            $request = $validated->data();
            $resolved = $this->intents->resolve($request);

            if ($resolved->isFailure()) {
                return $resolved;
            }

            /** @var array<string,mixed> $data */
            $data = $resolved->data();

            if (is_array($data['replay_result'] ?? null)) {
                return Result::success($data['replay_result'], 'Bukti pembayaran supplier sudah diproses.');
            }

            return $this->response->make($data['intent']);
        } catch (Throwable) {
            return Result::failure('Upload bukti pembayaran gagal disiapkan.', [
                'upload_intent' => ['PREPARE_FAILED'],
            ]);
        }
    }
}
