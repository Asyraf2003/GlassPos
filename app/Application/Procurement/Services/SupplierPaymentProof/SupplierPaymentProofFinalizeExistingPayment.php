<?php

declare(strict_types=1);

namespace App\Application\Procurement\Services\SupplierPaymentProof;

use App\Application\Procurement\Services\SupplierInvoiceListProjectionService;
use App\Application\Procurement\Services\SupplierPaymentProofAttachmentFactory;
use App\Application\Procurement\UseCases\AttachSupplierPaymentProofResultBuilder;
use App\Application\Shared\DTO\Result;
use App\Ports\Out\AuditLogPort;
use App\Ports\Out\Procurement\SupplierPaymentProofAttachmentWriterPort;
use App\Ports\Out\Procurement\SupplierPaymentReaderPort;
use App\Ports\Out\Procurement\SupplierPaymentWriterPort;

final class SupplierPaymentProofFinalizeExistingPayment
{
    public function __construct(
        private readonly SupplierPaymentReaderPort $payments,
        private readonly SupplierPaymentWriterPort $paymentWriter,
        private readonly SupplierPaymentProofAttachmentWriterPort $attachments,
        private readonly SupplierPaymentProofAttachmentFactory $factory,
        private readonly SupplierInvoiceListProjectionService $projection,
        private readonly AuditLogPort $audit,
        private readonly AttachSupplierPaymentProofResultBuilder $results,
    ) {}

    /** @param list<array<string,mixed>> $files */
    public function record(string $paymentId, array $files, string $actorId): Result
    {
        $payment = $this->payments->getByIdForUpdate($paymentId);

        if ($payment === null) {
            return Result::failure('Pembayaran supplier tidak ditemukan.', [
                'supplier_payment' => ['SUPPLIER_PAYMENT_NOT_FOUND'],
            ]);
        }

        [$records, $paths] = $this->factory->makeMany($payment->id(), $files, $actorId);
        $this->attachments->createMany($records);
        $payment->markProofUploaded();
        $this->paymentWriter->update($payment);
        $this->audit->record('supplier_payment_proof_attached', [
            'supplier_payment_id' => $payment->id(),
            'supplier_invoice_id' => $payment->supplierInvoiceId(),
            'proof_status' => $payment->proofStatus(),
            'attachment_count' => count($records),
            'attachment_storage_paths' => $paths,
            'performed_by_actor_id' => $actorId,
        ]);
        $this->projection->syncInvoice($payment->supplierInvoiceId());

        return $this->results->success($payment, $records, $paths);
    }
}
