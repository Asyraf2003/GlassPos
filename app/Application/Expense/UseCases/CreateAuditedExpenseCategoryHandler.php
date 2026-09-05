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
use RuntimeException;
use Throwable;

final class CreateAuditedExpenseCategoryHandler
{
    public function __construct(
        private readonly CreateExpenseCategoryHandler $create,
        private readonly AuditEventWriterPort $audit,
        private readonly ClockPort $clock,
        private readonly UuidPort $uuid,
        private readonly TransactionManagerPort $transactions,
    ) {}

    public function handle(
        string $code,
        string $name,
        ?string $description = null,
        ?string $actorId = null,
        ?string $sourceChannel = null,
    ): Result {
        $this->transactions->begin();

        try {
            $result = $this->create->handle($code, $name, $description);

            if ($result->isFailure()) {
                $this->transactions->rollBack();
                return $result;
            }

            $data = $result->data();
            $category = is_array($data) && is_array($data['expense_category'] ?? null)
                ? $data['expense_category']
                : [];
            $categoryId = trim((string) ($category['id'] ?? ''));

            if ($categoryId === '') {
                throw new RuntimeException('Expense category create result does not contain category id.');
            }

            $this->audit->write(new AuditEventWrite(
                id: $this->uuid->generate(),
                boundedContext: 'expense',
                aggregateType: 'expense_category',
                aggregateId: $categoryId,
                eventName: 'expense_category_created',
                actorId: $this->nullable($actorId),
                actorRole: null,
                reason: null,
                sourceChannel: $this->nullable($sourceChannel),
                requestId: null,
                correlationId: null,
                occurredAt: $this->clock->now(),
                metadata: ['category_id' => $categoryId],
                snapshots: [new AuditEventSnapshotWrite('after', $category)],
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
