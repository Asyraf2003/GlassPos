<?php

declare(strict_types=1);

namespace App\Application\Procurement\UseCases;

use App\Application\Procurement\Services\SupplierPaymentProof\SupplierPaymentProofDirectUploadRequestValidator;
use App\Application\Procurement\Services\SupplierPaymentProof\SupplierPaymentProofPrepareIntentService;
use App\Application\Procurement\Services\SupplierPaymentProof\SupplierPaymentProofPrepareResponse;
use App\Application\Procurement\Services\SupplierPaymentProof\SupplierPaymentProofPublicError;
use App\Application\Shared\DTO\Result;
use App\Ports\Out\Procurement\SupplierPaymentProofFailureCode;
use App\Ports\Out\Procurement\SupplierPaymentProofFailureReporterPort;
use Throwable;

final class PrepareSupplierPaymentProofDirectUploadHandler
{
    public function __construct(
        private readonly SupplierPaymentProofDirectUploadRequestValidator $validator,
        private readonly SupplierPaymentProofPrepareIntentService $intents,
        private readonly SupplierPaymentProofPrepareResponse $response,
        private readonly SupplierPaymentProofFailureReporterPort $failures,
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
        } catch (Throwable $exception) {
            $this->report($exception, $scopeType, $scopeId, count($files));

            return SupplierPaymentProofPublicError::PREPARE_FAILED->result();
        }
    }

    private function report(Throwable $exception, string $scopeType, string $scopeId, int $fileCount): void
    {
        try {
            $this->failures->report(
                'prepare.handler.exception',
                SupplierPaymentProofFailureCode::PREPARE_HANDLER_EXCEPTION,
                $exception,
                [
                    'scope_type' => trim($scopeType),
                    'scope_id' => trim($scopeId),
                    'file_count' => $fileCount,
                ],
            );
        } catch (Throwable) {
        }
    }
}
