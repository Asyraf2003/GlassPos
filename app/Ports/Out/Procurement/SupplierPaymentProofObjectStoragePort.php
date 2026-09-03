<?php

declare(strict_types=1);

namespace App\Ports\Out\Procurement;

interface SupplierPaymentProofObjectStoragePort
{
    /**
     * @param  array<string,mixed>  $intentFile
     * @return array<string,mixed>|null
     */
    public function verifyStaging(string $uploadIntentId, array $intentFile): ?array;

    /**
     * @param  array<string,mixed>  $verifiedFile
     * @return array<string,mixed>|null
     */
    public function promote(string $uploadIntentId, string $supplierPaymentId, array $verifiedFile): ?array;

    /** @param list<string> $paths */
    public function deleteMany(array $paths): bool;

    /** @param array<string,mixed> $intent */
    public function cleanupIntent(array $intent): bool;
}
