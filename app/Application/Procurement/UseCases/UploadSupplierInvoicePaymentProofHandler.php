<?php

declare(strict_types=1);

namespace App\Application\Procurement\UseCases;

use App\Application\Procurement\Services\SupplierPaymentProof\UploadSupplierInvoicePaymentProofOperation;
use App\Application\Shared\DTO\Result;
use App\Core\Shared\Exceptions\DomainException;

final class UploadSupplierInvoicePaymentProofHandler
{
    public function __construct(private readonly UploadSupplierInvoicePaymentProofOperation $operation)
    {
    }

    /**
     * @param list<array{source_path:string,original_filename:string,mime_type:string,file_size_bytes:int}> $proofFiles
     */
    public function handle(string $supplierInvoiceId, array $proofFiles, string $performedByActorId): Result
    {
        try {
            $actorId = trim($performedByActorId);

            if ($actorId === '') {
                throw new DomainException('Actor bukti pembayaran supplier wajib ada.');
            }

            if ($proofFiles === []) {
                return $this->fail('Bukti pembayaran wajib diunggah.', 'SUPPLIER_PAYMENT_PROOF_REQUIRED');
            }

            return $this->operation->execute($supplierInvoiceId, $proofFiles, $actorId);
        } catch (DomainException $e) {
            return $this->fail($e->getMessage(), 'INVALID_SUPPLIER_PAYMENT_PROOF');
        }
    }

    private function fail(string $message, string $code): Result
    {
        return Result::failure($message, ['supplier_payment_proof' => [$code]]);
    }
}
