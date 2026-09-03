<?php

declare(strict_types=1);

namespace App\Application\Procurement\Services\SupplierPaymentProof;

use App\Application\Shared\DTO\Result;
use App\Core\Procurement\SupplierPaymentProof\SupplierPaymentProofUploadLimits;
use App\Ports\Out\Procurement\SupplierPaymentProofObjectStoragePort;
use App\Ports\Out\Procurement\SupplierPaymentProofUploadIntentPort;

final class SupplierPaymentProofFinalizationObjectSet
{
    public function __construct(
        private readonly SupplierPaymentProofObjectStoragePort $storage,
        private readonly SupplierPaymentProofUploadIntentPort $intents,
    ) {}

    /** @param array<string,mixed> $intent */
    public function verifyAndPromote(array $intent, string $paymentId): Result
    {
        $files = is_array($intent['files'] ?? null) ? $intent['files'] : [];

        if (count($files) < 1 || count($files) > SupplierPaymentProofUploadLimits::MAX_FILES) {
            return $this->fail('VERIFY_FAILED');
        }

        $verified = [];

        foreach ($files as $file) {
            $actual = $this->storage->verifyStaging((string) $intent['id'], $file);

            if ($actual === null) {
                return $this->fail('VERIFY_FAILED');
            }

            $verified[] = $actual;
        }

        $promoted = [];

        foreach ($verified as $file) {
            $final = $this->storage->promote((string) $intent['id'], $paymentId, $file);

            if ($final === null) {
                $this->cleanup((string) $intent['id'], $promoted);

                return $this->fail('PROMOTION_FAILED');
            }

            $promoted[] = $final;

            if (! $this->record($intent, $file, $final)) {
                $this->cleanup((string) $intent['id'], $promoted);

                return $this->fail('PROMOTION_FAILED');
            }
        }

        return Result::success(['files' => $promoted]);
    }

    /** @param list<array<string,mixed>> $files */
    public function cleanup(string $intentId, array $files): bool
    {
        $paths = array_values(array_filter(array_map(
            static fn (array $file): string => trim((string) ($file['storage_path'] ?? '')),
            $files,
        )));
        $deleted = $this->storage->deleteMany($paths);

        if ($deleted) {
            $this->intents->clearVerifiedFiles($intentId);
        }

        return $deleted;
    }

    /** @param array<string,mixed> $intent @param array<string,mixed> $file @param array<string,mixed> $final */
    private function record(array $intent, array $file, array $final): bool
    {
        return $this->intents->recordVerifiedFile(
            (string) $intent['id'],
            (string) $file['id'],
            (string) $final['storage_path'],
            (string) $final['mime_type'],
            (int) $final['file_size_bytes'],
        );
    }

    private function fail(string $code): Result
    {
        return Result::failure('Bukti pembayaran yang diunggah tidak dapat diverifikasi.', [
            'upload_intent' => [$code],
        ]);
    }
}
