<?php

declare(strict_types=1);

namespace App\Adapters\Out\Procurement;

use App\Core\Procurement\SupplierPaymentProof\SupplierPaymentProofMimeTypes;

final class SupplierPaymentProofIntentObjectPaths
{
    /** @param array<string,mixed> $intent @return list<string> */
    public static function all(array $intent): array
    {
        $paymentId = ($intent['scope_type'] ?? null) === 'supplier_invoice'
            ? (string) ($intent['reserved_supplier_payment_id'] ?? '')
            : (string) ($intent['scope_id'] ?? '');
        $paths = [];

        foreach (is_array($intent['files'] ?? null) ? $intent['files'] : [] as $file) {
            $paths[] = (string) ($file['staging_path'] ?? '');
            $paths[] = (string) ($file['final_storage_path'] ?? '');

            foreach (SupplierPaymentProofMimeTypes::all() as $mimeType) {
                $paths[] = SupplierPaymentProofFinalPathFactory::make(
                    $paymentId,
                    (string) ($file['id'] ?? ''),
                    $mimeType,
                );
            }
        }

        return array_values(array_unique(array_filter(array_map('trim', $paths))));
    }
}
