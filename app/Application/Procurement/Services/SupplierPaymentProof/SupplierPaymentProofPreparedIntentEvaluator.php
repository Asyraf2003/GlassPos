<?php

declare(strict_types=1);

namespace App\Application\Procurement\Services\SupplierPaymentProof;

use App\Application\Shared\DTO\Result;
use App\Ports\Out\ClockPort;
use DateTimeImmutable;
use Throwable;

final class SupplierPaymentProofPreparedIntentEvaluator
{
    public function __construct(private readonly ClockPort $clock) {}

    /** @param array<string,mixed> $intent */
    public function evaluate(array $intent, string $requestHash): Result
    {
        if (! hash_equals((string) ($intent['request_hash'] ?? ''), $requestHash)) {
            return Result::failure('Kunci idempotensi sudah dipakai untuk payload berbeda.', [
                'idempotency_key' => ['SUPPLIER_PAYMENT_PROOF_UPLOAD_IDEMPOTENCY_CONFLICT'],
            ]);
        }

        $status = (string) ($intent['status'] ?? '');

        if ($status === 'finalized' && is_array($intent['result_payload'] ?? null)) {
            return Result::success(['replay_result' => $intent['result_payload']]);
        }

        if ($status !== 'prepared' || $this->isExpired($intent['expires_at'] ?? null)) {
            return Result::failure('Upload bukti pembayaran tidak lagi dapat digunakan.', [
                'upload_intent' => ['SUPPLIER_PAYMENT_PROOF_UPLOAD_NOT_PREPARED'],
            ]);
        }

        return Result::success(['intent' => $intent]);
    }

    private function isExpired(mixed $expiresAt): bool
    {
        try {
            return new DateTimeImmutable((string) $expiresAt) <= $this->clock->now();
        } catch (Throwable) {
            return true;
        }
    }
}
