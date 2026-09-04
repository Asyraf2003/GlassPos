<?php

declare(strict_types=1);

namespace App\Adapters\Out\Procurement;

use App\Ports\Out\Procurement\SupplierPaymentProofFailureReporterPort;
use Illuminate\Support\Facades\Log;
use Throwable;

final class LaravelSupplierPaymentProofFailureReporterAdapter implements SupplierPaymentProofFailureReporterPort
{
    private const SAFE_CONTEXT_KEYS = [
        'upload_intent_id',
        'scope_type',
        'scope_id',
        'file_count',
        'file_ordinal',
        'disk',
        'driver',
        'bucket',
        'endpoint_host',
        'key_configured',
        'secret_configured',
        'filesystem_adapter',
    ];

    public function report(string $stage, Throwable $exception, array $context = []): void
    {
        try {
            Log::error('supplier_payment_proof_direct_upload_failure', [
                'stage' => preg_replace('/[^a-z0-9._-]/i', '_', $stage) ?? 'unknown',
                'exception_class' => $exception::class,
                'exception_code' => (string) $exception->getCode(),
                'exception_message' => SupplierPaymentProofFailureMessageSanitizer::sanitize(
                    $exception->getMessage(),
                    $this->configuredSecrets(),
                ),
                'exception_source' => basename($exception->getFile()).':'.$exception->getLine(),
                'runtime' => [
                    'php_sapi' => PHP_SAPI,
                    'php_version' => PHP_VERSION,
                    'process_id' => getmypid(),
                    'app_environment' => app()->environment(),
                    'config_cached' => app()->configurationIsCached(),
                ],
                'context' => array_intersect_key($context, array_flip(self::SAFE_CONTEXT_KEYS)),
            ]);
        } catch (Throwable) {
        }
    }

    /** @return list<string> */
    private function configuredSecrets(): array
    {
        return [
            (string) config('filesystems.disks.r2_private.key', ''),
            (string) config('filesystems.disks.r2_private.secret', ''),
        ];
    }
}
