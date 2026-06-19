<?php

declare(strict_types=1);

namespace App\Application\Procurement\Services\SupplierPaymentProof;

use App\Application\Shared\DTO\Result;
use App\Core\Procurement\SupplierInvoice\SupplierInvoice;
use App\Ports\Out\TransactionManagerPort;
use Throwable;

final class UploadSupplierInvoicePaymentProofOperation
{
    public function __construct(
        private readonly SupplierInvoicePaymentProofPreflight $preflight,
        private readonly SupplierInvoicePaymentProofFileSet $proofFiles,
        private readonly SupplierInvoicePaymentProofRecorder $recorder,
        private readonly TransactionManagerPort $transactions,
    ) {
    }

    /**
     * @param list<array{source_path:string,original_filename:string,mime_type:string,file_size_bytes:int}> $files
     */
    public function execute(string $supplierInvoiceId, array $files, string $actorId): Result
    {
        $storedPaths = [];

        try {
            $this->transactions->begin();

            $prepared = $this->preflight->prepare($supplierInvoiceId);

            if ($prepared->isFailure()) {
                $this->transactions->rollBack();

                return $prepared;
            }

            $paymentId = $this->recorder->newPaymentId();
            $stored = $this->proofFiles->store($paymentId, $files, $actorId);

            if ($stored->isFailure()) {
                $this->transactions->rollBack();

                return $stored;
            }

            /** @var array{invoice:SupplierInvoice,outstanding_rupiah:int} $preparedData */
            $preparedData = $prepared->data();
            /** @var array{attachments:list<object>,stored_paths:list<string>} $storedData */
            $storedData = $stored->data();
            $storedPaths = $storedData['stored_paths'];

            $result = $this->recorder->record(
                $preparedData['invoice'],
                $paymentId,
                $preparedData['outstanding_rupiah'],
                $storedData['attachments'],
                $storedPaths,
                $actorId
            );

            $this->transactions->commit();

            return $result;
        } catch (Throwable $e) {
            $this->rollBackQuietly();
            $this->proofFiles->deleteMany($storedPaths);

            throw $e;
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
