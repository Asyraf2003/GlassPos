<?php

declare(strict_types=1);

namespace App\Application\Procurement\Services\SupplierPaymentProof;

use App\Application\Shared\DTO\Result;
use App\Ports\Out\Procurement\SupplierPaymentProofUploadIntentPort;
use App\Ports\Out\TransactionManagerPort;
use RuntimeException;
use Throwable;

final class SupplierPaymentProofFinalizeTransaction
{
    public function __construct(
        private readonly TransactionManagerPort $transactions,
        private readonly SupplierPaymentProofUploadIntentPort $intents,
        private readonly SupplierPaymentProofFinalizeExistingPayment $existingPayment,
        private readonly SupplierPaymentProofFinalizeInvoice $invoice,
    ) {}

    /** @param array<string,mixed> $intent @param list<array<string,mixed>> $files */
    public function run(array $intent, array $files, string $actorId): Result
    {
        $this->transactions->begin();

        try {
            $locked = $this->intents->findByIdForActorForUpdate((string) $intent['id'], $actorId);

            if ($locked === null || ($locked['status'] ?? null) !== 'finalizing') {
                $this->transactions->rollBack();

                return Result::failure('Finalisasi bukti pembayaran sedang diproses.', [
                    'upload_intent' => ['FINALIZE_CLAIM_LOST'],
                ]);
            }

            $result = $locked['scope_type'] === 'supplier_payment'
                ? $this->existingPayment->record((string) $locked['scope_id'], $files, $actorId)
                : $this->invoice->record($locked, $files, $actorId);

            if ($result->isFailure()) {
                $this->transactions->rollBack();

                return $result;
            }

            if (! is_array($result->data())
                || ! $this->intents->markFinalized((string) $locked['id'], $actorId, $result->data())) {
                throw new RuntimeException('Unable to persist supplier proof finalization result.');
            }

            $this->transactions->commit();

            return $result;
        } catch (Throwable $exception) {
            $this->rollBackQuietly();
            throw $exception;
        }
    }

    private function rollBackQuietly(): void
    {
        try {
            $this->transactions->rollBack();
        } catch (Throwable) {
        }
    }
}
