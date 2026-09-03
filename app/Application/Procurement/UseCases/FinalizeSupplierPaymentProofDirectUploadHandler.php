<?php

declare(strict_types=1);

namespace App\Application\Procurement\UseCases;

use App\Application\Procurement\Services\SupplierPaymentProof\SupplierPaymentProofFinalizationObjectSet;
use App\Application\Procurement\Services\SupplierPaymentProof\SupplierPaymentProofFinalizeTransaction;
use App\Application\Shared\DTO\Result;
use App\Ports\Out\Procurement\SupplierPaymentProofUploadIntentPort;
use Throwable;

final class FinalizeSupplierPaymentProofDirectUploadHandler
{
    public function __construct(
        private readonly SupplierPaymentProofUploadIntentPort $intents,
        private readonly SupplierPaymentProofFinalizationObjectSet $objects,
        private readonly SupplierPaymentProofFinalizeTransaction $transaction,
    ) {}

    public function handle(string $uploadIntentId, string $performedByActorId): Result
    {
        $intentId = trim($uploadIntentId);
        $actorId = trim($performedByActorId);

        if ($intentId === '' || $actorId === '') {
            return $this->fail('UPLOAD_INTENT_NOT_FOUND');
        }

        $promoted = [];

        try {
            $intent = $this->intents->findByIdForActor($intentId, $actorId);

            if ($intent === null) {
                return $this->fail('UPLOAD_INTENT_NOT_FOUND');
            }

            if (($intent['status'] ?? null) === 'finalized' && is_array($intent['result_payload'] ?? null)) {
                return Result::success($intent['result_payload'], 'Bukti pembayaran supplier sudah diproses.');
            }

            if (($intent['status'] ?? null) !== 'prepared'
                || ! $this->intents->claimForFinalize($intentId, $actorId)) {
                return $this->fail('FINALIZE_NOT_AVAILABLE');
            }

            $claimed = $this->intents->findByIdForActor($intentId, $actorId);
            $paymentId = $this->paymentId($claimed);
            $objects = $claimed === null ? $this->fail('FINALIZE_CLAIM_LOST')
                : $this->objects->verifyAndPromote($claimed, $paymentId);

            if ($objects->isFailure() || ! is_array($objects->data())) {
                $this->intents->releaseFinalizeClaim($intentId, $actorId);

                return $objects;
            }

            $promoted = $objects->data()['files'] ?? [];
            $result = $this->transaction->run($claimed, $promoted, $actorId);

            if ($result->isFailure()) {
                $this->objects->cleanup($intentId, $promoted);
                $this->intents->releaseFinalizeClaim($intentId, $actorId);
            }

            return $result;
        } catch (Throwable $exception) {
            $this->objects->cleanup($intentId, is_array($promoted) ? $promoted : []);
            $this->intents->releaseFinalizeClaim($intentId, $actorId);

            throw $exception;
        }
    }

    /** @param array<string,mixed>|null $intent */
    private function paymentId(?array $intent): string
    {
        return ($intent['scope_type'] ?? null) === 'supplier_invoice'
            ? trim((string) ($intent['reserved_supplier_payment_id'] ?? ''))
            : trim((string) ($intent['scope_id'] ?? ''));
    }

    private function fail(string $code): Result
    {
        return Result::failure('Upload bukti pembayaran tidak dapat difinalisasi.', ['upload_intent' => [$code]]);
    }
}
