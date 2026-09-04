<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Ports\Out\Procurement\SupplierPaymentProofDirectUploadPort;
use App\Ports\Out\Procurement\SupplierPaymentProofDirectUploadPreparation;

final class FakeSupplierPaymentProofDirectUploadPort implements SupplierPaymentProofDirectUploadPort
{
    public function prepareMany(
        string $uploadIntentId,
        array $files,
        int $expiresInSeconds = 900,
    ): SupplierPaymentProofDirectUploadPreparation {
        $prepared = array_map(static fn (array $file): array => [
            ...$file,
            'upload_url' => 'https://private-r2.example.test/'.rawurlencode((string) $file['storage_path']),
            'headers' => ['Content-Type' => (string) $file['mime_type']],
        ], $files);

        return SupplierPaymentProofDirectUploadPreparation::success($prepared);
    }
}
