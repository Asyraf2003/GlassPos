<?php

declare(strict_types=1);

namespace App\Application\Procurement\Services\SupplierPaymentProof;

use App\Application\Procurement\Services\SupplierInvoiceListProjectionService;
use App\Application\Shared\DTO\Result;
use App\Core\Procurement\SupplierPayment\SupplierPayment;
use App\Core\Procurement\SupplierInvoice\SupplierInvoice;
use App\Core\Shared\ValueObjects\Money;
use App\Ports\Out\AuditLogPort;
use App\Ports\Out\Procurement\SupplierPaymentProofAttachmentWriterPort;
use App\Ports\Out\Procurement\SupplierPaymentWriterPort;
use App\Ports\Out\UuidPort;
use DateTimeImmutable;

final class SupplierInvoicePaymentProofRecorder
{
    public function __construct(
        private readonly SupplierPaymentWriterPort $payments,
        private readonly SupplierPaymentProofAttachmentWriterPort $attachments,
        private readonly SupplierInvoiceListProjectionService $projection,
        private readonly AuditLogPort $audit,
        private readonly UuidPort $uuid,
    ) {
    }

    public function newPaymentId(): string
    {
        return $this->uuid->generate();
    }

    /** @param list<object> $attachmentRecords @param list<string> $storedPaths */
    public function record(
        SupplierInvoice $invoice,
        string $paymentId,
        int $amountRupiah,
        array $attachmentRecords,
        array $storedPaths,
        string $actorId,
    ): Result {
        $payment = SupplierPayment::create(
            $paymentId,
            $invoice->id(),
            Money::fromInt($amountRupiah),
            new DateTimeImmutable('now'),
            SupplierPayment::PROOF_STATUS_PENDING,
            null
        );

        $this->payments->create($payment);
        $this->attachments->createMany($attachmentRecords);
        $payment->markProofUploaded();
        $this->payments->update($payment);

        $this->audit->record('supplier_invoice_payment_proof_uploaded', [
            'supplier_invoice_id' => $invoice->id(),
            'supplier_payment_id' => $paymentId,
            'amount_rupiah' => $amountRupiah,
            'proof_status' => $payment->proofStatus(),
            'attachment_count' => count($attachmentRecords),
            'attachment_storage_paths' => $storedPaths,
            'performed_by_actor_id' => $actorId,
        ]);

        $this->projection->syncInvoice($invoice->id());

        return Result::success([
            'supplier_invoice_id' => $invoice->id(),
            'supplier_payment_id' => $paymentId,
            'amount_rupiah' => $amountRupiah,
            'outstanding_rupiah' => 0,
            'proof_status' => $payment->proofStatus(),
            'attachment_count' => count($attachmentRecords),
        ], 'Bukti pembayaran supplier berhasil diunggah.');
    }
}
