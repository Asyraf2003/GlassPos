<?php

declare(strict_types=1);

namespace App\Application\Procurement\Services\SupplierPaymentProof;

use App\Application\Procurement\Services\SupplierPaymentProofAttachmentFactory;
use App\Application\Shared\DTO\Result;
use App\Core\Procurement\SupplierInvoice\SupplierInvoice;

final class SupplierPaymentProofFinalizeInvoice
{
    public function __construct(
        private readonly SupplierInvoicePaymentProofPreflight $preflight,
        private readonly SupplierPaymentProofAttachmentFactory $attachments,
        private readonly SupplierInvoicePaymentProofRecorder $recorder,
    ) {}

    /** @param array<string,mixed> $intent @param list<array<string,mixed>> $files */
    public function record(array $intent, array $files, string $actorId): Result
    {
        $prepared = $this->preflight->prepare((string) $intent['scope_id']);
        $paymentId = trim((string) ($intent['reserved_supplier_payment_id'] ?? ''));

        if ($prepared->isFailure() || $paymentId === '') {
            return $prepared->isFailure() ? $prepared : Result::failure(
                'ID pembayaran supplier tidak tersedia.',
                ['supplier_payment' => ['RESERVED_PAYMENT_ID_MISSING']],
            );
        }

        /** @var array{invoice:SupplierInvoice,outstanding_rupiah:int} $data */
        $data = $prepared->data();
        [$records, $paths] = $this->attachments->makeMany($paymentId, $files, $actorId);

        return $this->recorder->record(
            $data['invoice'],
            $paymentId,
            $data['outstanding_rupiah'],
            $records,
            $paths,
            $actorId,
        );
    }
}
