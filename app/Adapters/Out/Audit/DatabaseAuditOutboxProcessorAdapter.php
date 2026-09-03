<?php

declare(strict_types=1);

namespace App\Adapters\Out\Audit;

use App\Application\Audit\Support\AuditOutboxStatus;
use App\Ports\Out\Audit\AuditOutboxProcessorPort;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

final class DatabaseAuditOutboxProcessorAdapter implements AuditOutboxProcessorPort
{
    public function eligible(int $limit, bool $retryFailed, int $maxAttempts, DateTimeImmutable $now): array
    {
        return DB::table('audit_outbox')
            ->where(static function ($query) use ($retryFailed): void {
                $query->where('status', AuditOutboxStatus::PENDING);

                if ($retryFailed) {
                    $query->orWhere('status', AuditOutboxStatus::FAILED);
                }
            })
            ->where('attempts', '<', max(1, $maxAttempts))
            ->where(static function ($query) use ($now): void {
                $query->whereNull('available_at')->orWhere('available_at', '<=', $now);
            })
            ->orderBy('created_at')
            ->limit(max(1, $limit))
            ->get()
            ->all();
    }

    public function claim(string $rowId, string $status, DateTimeImmutable $now): ?object
    {
        $claimed = DB::table('audit_outbox')
            ->where('id', $rowId)
            ->where('status', $status)
            ->update([
                'status' => AuditOutboxStatus::PROCESSING,
                'locked_at' => $now,
                'updated_at' => $now,
            ]);

        return $claimed === 1 ? DB::table('audit_outbox')->where('id', $rowId)->first() : null;
    }

    public function markProcessed(string $rowId, DateTimeImmutable $now): void
    {
        DB::table('audit_outbox')->where('id', $rowId)->update([
            'status' => AuditOutboxStatus::PROCESSED,
            'locked_at' => null,
            'processed_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function recordFailure(string $rowId, string $message, int $maxAttempts, DateTimeImmutable $now): void
    {
        $row = DB::table('audit_outbox')->where('id', $rowId)->first();

        if ($row === null) {
            return;
        }

        $attempts = ((int) $row->attempts) + 1;
        DB::table('audit_outbox')->where('id', $rowId)->update([
            'status' => $attempts >= max(1, $maxAttempts) ? AuditOutboxStatus::FAILED : AuditOutboxStatus::PENDING,
            'attempts' => $attempts,
            'last_error' => substr($message, 0, 1000),
            'locked_at' => null,
            'updated_at' => $now,
        ]);
    }
}
