<?php

declare(strict_types=1);

namespace App\Application\Audit\UseCases;

use App\Application\Audit\Services\AuditOutboxEventHydrator;
use App\Ports\Out\Audit\AuditOutboxProcessorPort;
use App\Ports\Out\AuditEventWriterPort;
use App\Ports\Out\ClockPort;
use App\Ports\Out\TransactionManagerPort;
use Throwable;

final class ProcessAuditOutboxHandler
{
    public function __construct(
        private readonly AuditEventWriterPort $materializer,
        private readonly AuditOutboxEventHydrator $hydrator,
        private readonly AuditOutboxProcessorPort $outbox,
        private readonly ClockPort $clock,
        private readonly TransactionManagerPort $transactions,
    ) {}

    public function handle(int $limit, bool $retryFailed, int $maxAttempts): array
    {
        $summary = ['processed' => 0, 'failed' => 0, 'skipped' => 0];

        foreach ($this->outbox->eligible($limit, $retryFailed, $maxAttempts, $this->clock->now()) as $row) {
            try {
                $result = $this->processRow($row);
            } catch (Throwable $e) {
                $this->outbox->recordFailure(
                    (string) $row->id,
                    $e->getMessage(),
                    $maxAttempts,
                    $this->clock->now(),
                );
                $summary['failed']++;

                continue;
            }

            $summary[$result]++;
        }

        return $summary;
    }

    private function processRow(object $row): string
    {
        $this->transactions->begin();

        try {
            $now = $this->clock->now();
            $fresh = $this->outbox->claim((string) $row->id, (string) $row->status, $now);

            if ($fresh === null) {
                $this->transactions->rollBack();

                return 'skipped';
            }

            $this->materializer->write($this->hydrator->fromRow($fresh));
            $this->outbox->markProcessed((string) $fresh->id, $now);
            $this->transactions->commit();

            return 'processed';
        } catch (Throwable $exception) {
            $this->rollBackQuietly();
            throw $exception;
        }
    }

    private function rollBackQuietly(): void
    {
        try {
            $this->transactions->rollBack();
        } catch (Throwable) {
        }
    }
}
