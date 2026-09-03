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
     * @return list<array{
     * storage_path:string,
     * original_filename:string,
     * mime_type:string,
     * file_size_bytes:int,
     * upload_url:string,
     * headers:array<string,string>
     * }>
     */
    public function prepareMany(string $uploadIntentId, array $files, int $expiresInSeconds = 900): array;
}
