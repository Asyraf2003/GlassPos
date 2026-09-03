<?php

declare(strict_types=1);

namespace App\Application\Procurement\UseCases;

use App\Ports\Out\ClockPort;
use App\Ports\Out\Procurement\SupplierPaymentProofObjectStoragePort;
use App\Ports\Out\Procurement\SupplierPaymentProofUploadCleanupPort;

final class CleanupSupplierPaymentProofUploadsHandler
{
    public function __construct(
        private readonly SupplierPaymentProofUploadCleanupPort $cleanup,
        private readonly SupplierPaymentProofObjectStoragePort $storage,
        private readonly ClockPort $clock,
    ) {}

    /** @return array{examined:int,expired:int,failed:int} */
    public function handle(int $limit = 100, int $staleMinutes = 30): array
    {
        $now = $this->clock->now();
        $staleAt = $now->modify('-'.max(5, $staleMinutes).' minutes');
        $candidates = $this->cleanup->findExpiredOrStale($now, $staleAt, $limit);
        $expired = 0;
        $failed = 0;

        foreach ($candidates as $intent) {
            if (! $this->cleanup->claimForCleanup((string) $intent['id'], $now, $staleAt)) {
                continue;
            }

            if ($this->storage->cleanupIntent($intent)
                && $this->cleanup->markCleanupCompleted((string) $intent['id'])) {
                $expired++;
            } else {
                $this->cleanup->releaseCleanupClaim((string) $intent['id']);
                $failed++;
            }
        }

        return ['examined' => count($candidates), 'expired' => $expired, 'failed' => $failed];
    }
}
