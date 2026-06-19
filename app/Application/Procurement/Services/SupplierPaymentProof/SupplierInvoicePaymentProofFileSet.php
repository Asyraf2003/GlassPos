<?php

declare(strict_types=1);

namespace App\Application\Procurement\Services\SupplierPaymentProof;

use App\Application\Procurement\Services\SupplierPaymentProofAttachmentFactory;
use App\Application\Shared\DTO\Result;
use App\Ports\Out\Procurement\SupplierPaymentProofFileStoragePort;

final class SupplierInvoicePaymentProofFileSet
{
    public function __construct(
        private readonly SupplierPaymentProofFileStoragePort $files,
        private readonly SupplierPaymentProofAttachmentFactory $attachments,
    ) {
    }

    /**
     * @param list<array{source_path:string,original_filename:string,mime_type:string,file_size_bytes:int}> $proofFiles
     */
    public function store(string $paymentId, array $proofFiles, string $actorId): Result
    {
        $storedProofFiles = $this->files->storeMany($paymentId, $proofFiles);

        if ($storedProofFiles === []) {
            return Result::failure('Bukti pembayaran supplier gagal diunggah.', [
                'supplier_payment_proof' => ['SUPPLIER_PAYMENT_PROOF_UPLOAD_FAILED'],
            ]);
        }

        [$attachmentRecords, $storedPaths] = $this->attachments->makeMany(
            $paymentId,
            $storedProofFiles,
            $actorId
        );

        return Result::success([
            'attachments' => $attachmentRecords,
            'stored_paths' => $storedPaths,
        ]);
    }

    /** @param list<string> $storedPaths */
    public function deleteMany(array $storedPaths): void
    {
        $this->files->deleteMany($storedPaths);
    }
}
