<?php

declare(strict_types=1);

namespace App\Application\Procurement\Services\SupplierPaymentProof;

use App\Application\Shared\DTO\Result;
use App\Ports\Out\ClockPort;
use App\Ports\Out\Procurement\SupplierPaymentProofUploadIntentPort;
use App\Ports\Out\UuidPort;

final class SupplierPaymentProofPrepareIntentCreator
{
    public function __construct(
        private readonly SupplierPaymentProofUploadIntentPort $intents,
        private readonly UuidPort $uuid,
        private readonly ClockPort $clock,
    ) {}

    /** @param array<string,mixed> $request */
    public function create(array $request): Result
    {
        $intentId = $this->uuid->generate();
        $reservedPaymentId = $request['scope_type'] === 'supplier_invoice' ? $this->uuid->generate() : null;
        $files = [];

        foreach ($request['files'] as $ordinal => $file) {
            $fileId = $this->uuid->generate();
            $files[] = [
                'id' => $fileId,
                'ordinal' => $ordinal + 1,
                'staging_path' => 'supplier-payment-proof-uploads/'.$intentId.'/'.$fileId.'.upload',
                'original_filename' => $file['original_filename'],
                'declared_mime_type' => $file['mime_type'],
                'declared_size_bytes' => $file['file_size_bytes'],
            ];
        }

        $created = $this->intents->createPrepared(
            $intentId,
            $request['actor_id'],
            $request['scope_type'],
            $request['scope_id'],
            $reservedPaymentId,
            $request['idempotency_key'],
            $request['request_hash'],
            $this->clock->now()->modify('+15 minutes'),
            $files,
        );

        $intent = $created ? $this->intents->findByIdForActor($intentId, $request['actor_id']) : null;

        return $intent === null
            ? Result::failure('Upload bukti pembayaran gagal disiapkan.', ['upload_intent' => ['PREPARE_FAILED']])
            : Result::success(['intent' => $intent]);
    }
}
