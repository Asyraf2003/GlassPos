<?php

declare(strict_types=1);

namespace App\Adapters\Out\Procurement;

use Illuminate\Filesystem\FilesystemAdapter;

final class SupplierPaymentProofPresignRuntimeContext
{
    /** @return array<string, bool|int|string|null> */
    public static function capture(?FilesystemAdapter $disk, string $intentId, int $fileCount, int $ordinal): array
    {
        $config = config('filesystems.disks.r2_private', []);
        $endpoint = is_array($config) ? (string) ($config['endpoint'] ?? '') : '';

        return [
            'upload_intent_id' => $intentId,
            'file_count' => $fileCount,
            'file_ordinal' => $ordinal,
            'disk' => 'r2_private',
            'driver' => is_array($config) ? (string) ($config['driver'] ?? '') : '',
            'bucket' => is_array($config) ? (string) ($config['bucket'] ?? '') : '',
            'endpoint_host' => parse_url($endpoint, PHP_URL_HOST) ?: null,
            'key_configured' => is_array($config) && trim((string) ($config['key'] ?? '')) !== '',
            'secret_configured' => is_array($config) && trim((string) ($config['secret'] ?? '')) !== '',
            'filesystem_adapter' => $disk === null ? null : $disk->getAdapter()::class,
        ];
    }
}
