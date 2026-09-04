<?php

declare(strict_types=1);

namespace App\Application\Procurement\Services\SupplierPaymentProof;

use App\Application\Shared\DTO\Result;
use App\Ports\Out\Procurement\SupplierPaymentProofDirectUploadPort;
use App\Ports\Out\Procurement\SupplierPaymentProofFailureCode;
use App\Ports\Out\Procurement\SupplierPaymentProofFailureReporterPort;

final class SupplierPaymentProofPrepareResponse
{
    public function __construct(
        private readonly SupplierPaymentProofDirectUploadPort $uploads,
        private readonly SupplierPaymentProofFailureReporterPort $failures,
    ) {}

    /** @param array<string,mixed> $intent */
    public function make(array $intent): Result
    {
        $files = array_map(static fn (array $file): array => [
            'storage_path' => (string) $file['staging_path'],
            'original_filename' => (string) $file['original_filename'],
            'mime_type' => (string) $file['declared_mime_type'],
            'file_size_bytes' => (int) $file['declared_size_bytes'],
        ], is_array($intent['files'] ?? null) ? $intent['files'] : []);

        $preparation = $this->uploads->prepareMany((string) $intent['id'], $files);

        if (! $preparation->isSuccess()) {
            return SupplierPaymentProofPublicError::PRESIGN_FAILED->result();
        }

        $prepared = $preparation->files();

        if (count($prepared) !== count($files)) {
            $this->failures->report(
                'prepare.response.contract',
                SupplierPaymentProofFailureCode::RESULT_COUNT_MISMATCH,
                null,
                [
                    'upload_intent_id' => (string) $intent['id'],
                    'file_count' => count($files),
                ],
            );

            return SupplierPaymentProofPublicError::PRESIGN_FAILED->result();
        }

        return Result::success([
            'upload_intent_id' => (string) $intent['id'],
            'status' => 'prepared',
            'expires_at' => (string) $intent['expires_at'],
            'files' => $prepared,
        ]);
    }
}
