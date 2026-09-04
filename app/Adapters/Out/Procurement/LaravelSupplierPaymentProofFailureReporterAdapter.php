<?php

declare(strict_types=1);

namespace App\Adapters\Out\Procurement;

use App\Ports\Out\Procurement\SupplierPaymentProofFailureCode;
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

    public function report(
        string $stage,
        SupplierPaymentProofFailureCode $failureCode,
        ?Throwable $exception = null,
        array $context = [],
    ): void {
        try {
            $payload = [
                'stage' => preg_replace('/[^a-z0-9._-]/i', '_', $stage) ?? 'unknown',
                'failure_code' => $failureCode->value,
                'runtime' => [
                    'php_sapi' => PHP_SAPI,
                    'php_version' => PHP_VERSION,
                    'process_id' => getmypid(),
                    'app_environment' => app()->environment(),
                    'config_cached' => app()->configurationIsCached(),
                ],
                'context' => array_intersect_key($context, array_flip(self::SAFE_CONTEXT_KEYS)),
            ];

            if ($exception !== null) {
                $payload['exception_class'] = $exception::class;
                $payload['exception_code'] = (string) $exception->getCode();
                $payload['exception_message'] = SupplierPaymentProofFailureMessageSanitizer::sanitize(
                    $exception->getMessage(),
                    $this->configuredSecrets(),
                );
                $payload['exception_source'] = basename($exception->getFile()).':'.$exception->getLine();
            }

            Log::error('supplier_payment_proof_direct_upload_failure', $payload);
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
