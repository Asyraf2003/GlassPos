<?php

declare(strict_types=1);

namespace App\Application\Expense\UseCases;

use App\Application\Audit\DTO\AuditEventSnapshotWrite;
use App\Application\Audit\DTO\AuditEventWrite;
use App\Application\Shared\DTO\Result;
use App\Ports\Out\AuditEventWriterPort;
use App\Ports\Out\ClockPort;
use App\Ports\Out\TransactionManagerPort;
use App\Ports\Out\UuidPort;
use Throwable;

final class RecordAuditedOperationalExpenseHandler
{
    public function __construct(
        private readonly RecordOperationalExpenseHandler $record,
        private readonly AuditEventWriterPort $audit,
        private readonly ClockPort $clock,
        private readonly UuidPort $uuid,
        private readonly TransactionManagerPort $transactions,
    ) {}

    public function handle(
        string $categoryId,
        int $amountRupiah,
        string $expenseDate,
        string $description,
        string $paymentMethod,
        ?string $actorId = null,
        ?string $sourceChannel = null,
    ): Result {
        $this->transactions->begin();

        try {
            $result = $this->record->handle(
                $categoryId,
                $amountRupiah,
                $expenseDate,
                $description,
                $paymentMethod,
            );

            if ($result->isFailure()) {
                $this->transactions->rollBack();

                return $result;
            }

            $data = $result->data();
            $expense = is_array($data) && is_array($data['expense'] ?? null) ? $data['expense'] : [];
            $expenseId = trim((string) ($expense['id'] ?? ''));

            if ($expenseId === '') {
                throw new \RuntimeException('Operational expense create result does not contain expense id.');
            }

            $this->audit->write(new AuditEventWrite(
                id: $this->uuid->generate(),
                boundedContext: 'expense',
                aggregateType: 'operational_expense',
                aggregateId: $expenseId,
                eventName: 'operational_expense_created',
                actorId: $this->nullable($actorId),
                actorRole: null,
                reason: null,
                sourceChannel: $this->nullable($sourceChannel),
                requestId: null,
                correlationId: null,
                occurredAt: $this->clock->now(),
                metadata: ['operational_expense_id' => $expenseId],
                snapshots: [new AuditEventSnapshotWrite('after', $expense)],
            ));

            $this->transactions->commit();

            return $result;
        } catch (Throwable $e) {
            $this->transactions->rollBack();
            throw $e;
        }
    }

    private function nullable(?string $value): ?string
    {
        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}
