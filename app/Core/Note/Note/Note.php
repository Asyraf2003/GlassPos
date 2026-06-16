<?php

declare(strict_types=1);

namespace App\Core\Note\Note;

use App\Core\Note\WorkItem\WorkItem;
use App\Core\Shared\Exceptions\DomainException;
use App\Core\Shared\ValueObjects\Money;
use DateTimeImmutable;

final class Note
{
    public const STATE_OPEN = 'open';
    public const STATE_CLOSED = 'closed';
    public const STATE_REFUNDED = 'refunded';

    use NoteState;
    use NoteValidation;
    use NoteMutations;
    use NoteNormalization;
    use NoteOperationalStateMutations;

    public static function create(
        string $id,
        string $name,
        ?string $customerPhone,
        DateTimeImmutable $date,
        ?string $operationalNote = null,
    ): self
    {
        self::assertValidIdentity($id, $name);

        return new self(
            trim($id),
            trim($name),
            self::normalizeCustomerPhone($customerPhone),
            $date,
            NoteDueDateCalculator::calculate($date),
            self::normalizeOperationalNote($operationalNote),
            [],
            Money::zero(),
            self::STATE_OPEN,
            null,
            null,
            null,
            null,
        );
    }

    /** @param list<WorkItem> $workItems */
    public static function rehydrate(
        string $id,
        string $name,
        ?string $customerPhone,
        DateTimeImmutable $date,
        Money $total,
        array $workItems = [],
        string $noteState = self::STATE_OPEN,
        ?DateTimeImmutable $closedAt = null,
        ?string $closedByActorId = null,
        ?DateTimeImmutable $reopenedAt = null,
        ?string $reopenedByActorId = null,
        ?DateTimeImmutable $dueDate = null,
        ?string $operationalNote = null,
    ): self {
        self::assertValidIdentity($id, $name);
        self::assertValidWorkItems($workItems);
        self::assertValidOperationalState($noteState);
        $total->ensureNotNegative('Total note tidak boleh negatif.');

        if ($workItems !== [] && !self::calculateTotalFromWorkItems($workItems)->equals($total)) {
            throw new DomainException('Total note tidak konsisten dengan subtotal work item.');
        }

        return new self(
            trim($id),
            trim($name),
            self::normalizeCustomerPhone($customerPhone),
            $date,
            $dueDate ?? NoteDueDateCalculator::calculate($date),
            self::normalizeOperationalNote($operationalNote),
            array_values($workItems),
            $total,
            trim($noteState),
            $closedAt,
            self::normalizeActorId($closedByActorId),
            $reopenedAt,
            self::normalizeActorId($reopenedByActorId),
        );
    }

}
