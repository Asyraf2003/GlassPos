<?php

declare(strict_types=1);

namespace App\Adapters\Out\Procurement;

use App\Core\Procurement\SupplierPaymentProof\SupplierPaymentProofMimeTypes;

final class SupplierPaymentProofFinalPathFactory
{
    public static function make(string $paymentId, string $fileId, string $mimeType): string
    {
        $extension = SupplierPaymentProofMimeTypes::extension($mimeType);
        $directory = SupplierPaymentProofStoragePathGuard::directory($paymentId);

        if ($extension === null || $directory === '' || trim($fileId) === '') {
            return '';
        }

        $filename = hash('sha256', 'supplier-payment-proof:'.trim($fileId)).'.'.$extension;

        return $directory.'/'.$filename;
    }
}
