<?php

declare(strict_types=1);

namespace App\Ports\Out\Procurement;

final class SupplierPaymentProofDirectUploadPreparation
{
    /**
     * @param list<array{
     * storage_path:string,
     * original_filename:string,
     * mime_type:string,
     * file_size_bytes:int,
     * upload_url:string,
     * headers:array<string,string>
     * }> $files
     */
    private function __construct(
        private readonly array $files,
        private readonly ?SupplierPaymentProofFailureCode $failureCode,
    ) {}

    /** @param list<array<string,mixed>> $files */
    public static function success(array $files): self
    {
        return new self($files, null);
    }

    public static function failure(SupplierPaymentProofFailureCode $failureCode): self
    {
        return new self([], $failureCode);
    }

    public function isSuccess(): bool
    {
        return $this->failureCode === null;
    }

    /** @return list<array<string,mixed>> */
    public function files(): array
    {
        return $this->files;
    }

    public function failureCode(): ?SupplierPaymentProofFailureCode
    {
        return $this->failureCode;
    }
}
