<?php

declare(strict_types=1);

namespace App\Adapters\Out\Procurement;

use App\Ports\Out\Procurement\SupplierPaymentProofUploadCleanupPort;
use DateTimeImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class DatabaseSupplierPaymentProofUploadCleanupAdapter implements SupplierPaymentProofUploadCleanupPort
{
    private const INTENTS = 'supplier_payment_proof_upload_intents';

    public function __construct(private readonly DatabaseSupplierPaymentProofUploadIntentHydrator $hydrator) {}

    public function findExpiredOrStale(
        DateTimeImmutable $expiredAt,
        DateTimeImmutable $staleFinalizingAt,
        int $limit,
    ): array {
        return $this->candidates($expiredAt, $staleFinalizingAt)
            ->orderBy('updated_at')
            ->limit(max(1, min($limit, 500)))
            ->get()
            ->map(fn (object $row): array => $this->hydrator->hydrate($row))
            ->all();
    }

    public function claimForCleanup(
        string $uploadIntentId,
        DateTimeImmutable $expiredAt,
        DateTimeImmutable $staleFinalizingAt,
    ): bool {
        return $this->candidates($expiredAt, $staleFinalizingAt)
            ->where('id', $uploadIntentId)
            ->update([
                'status' => 'expired',
                'locked_at' => now(),
                'updated_at' => now(),
            ]) === 1;
    }

    public function releaseCleanupClaim(string $uploadIntentId): void
    {
        DB::table(self::INTENTS)
            ->where('id', $uploadIntentId)
            ->where('status', 'expired')
            ->whereNull('result_payload_json')
            ->update(['locked_at' => null, 'updated_at' => now()]);
    }

    public function markCleanupCompleted(string $uploadIntentId): bool
    {
        return DB::table(self::INTENTS)
            ->where('id', $uploadIntentId)
            ->where('status', 'expired')
            ->whereNotNull('locked_at')
            ->update([
                'result_payload_json' => json_encode(['cleanup_completed' => true], JSON_THROW_ON_ERROR),
                'locked_at' => null,
                'updated_at' => now(),
            ]) === 1;
    }

    private function candidates(DateTimeImmutable $expiredAt, DateTimeImmutable $staleFinalizingAt): Builder
    {
        return DB::table(self::INTENTS)->where(function ($query) use ($expiredAt, $staleFinalizingAt): void {
            $query->where(function ($prepared) use ($expiredAt): void {
                $prepared->where('status', 'prepared')->where('expires_at', '<=', $expiredAt);
            })->orWhere(function ($finalizing) use ($staleFinalizingAt): void {
                $finalizing->where('status', 'finalizing')->where('locked_at', '<=', $staleFinalizingAt);
            })->orWhere(function ($expired) use ($staleFinalizingAt): void {
                $expired->where('status', 'expired')
                    ->whereNull('result_payload_json')
                    ->where(function ($lease) use ($staleFinalizingAt): void {
                        $lease->whereNull('locked_at')->orWhere('locked_at', '<=', $staleFinalizingAt);
                    });
            });
        });
    }
}
