<?php

declare(strict_types=1);

namespace App\Ports\Out\Procurement;

interface SupplierPaymentProofDirectUploadPort
{
    /**
     * @param list<array{
     * storage_path:string,
     * original_filename:string,
     * mime_type:string,
     * file_size_bytes:int
     * }> $files
     */
    public function prepareMany(
        string $uploadIntentId,
        array $files,
        int $expiresInSeconds = 900,
    ): SupplierPaymentProofDirectUploadPreparation;
}
