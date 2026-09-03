<?php

declare(strict_types=1);

namespace App\Application\Procurement\UseCases;

use App\Application\Shared\DTO\Result;

final class AttachSupplierPaymentProofResultBuilder
{
    /**
     * @param  list<object>  $attachmentRecords
     * @param  list<string>  $storedPaths
     */
    public function success(object $payment, array $attachmentRecords, array $storedPaths): Result
    {
        return Result::success([
            'supplier_payment_id' => $payment->id(),
            'supplier_invoice_id' => $payment->supplierInvoiceId(),
            'proof_status' => $payment->proofStatus(),
            'attachment_count' => count($attachmentRecords),
            'attachment_storage_paths' => $storedPaths,
        ], 'Bukti pembayaran supplier berhasil diunggah.');
    }
}
