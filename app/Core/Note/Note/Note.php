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

    public const TAX_MODE_NONE = 'none';
    public const TAX_MODE_PERCENT = 'percent';
    public const TAX_MODE_FIXED = 'fixed';

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
            Money::zero(),
            null,
            self::TAX_MODE_NONE,
            null,
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
        ?Money $subtotalBeforeNoteTaxRupiah = null,
        ?string $noteTaxInput = null,
        string $noteTaxMode = self::TAX_MODE_NONE,
        ?int $noteTaxRateBasisPoints = null,
        ?Money $noteTaxAmountRupiah = null,
    ): self {
        self::assertValidIdentity($id, $name);
        self::assertValidWorkItems($workItems);
        self::assertValidOperationalState($noteState);
        $total->ensureNotNegative('Total note tidak boleh negatif.');

        $noteTaxAmountRupiah ??= Money::zero();
        $subtotalBeforeNoteTaxRupiah ??= $workItems !== []
            ? self::calculateTotalFromWorkItems($workItems)
            : Money::fromInt(max($total->amount() - $noteTaxAmountRupiah->amount(), 0));

        self::assertValidNoteTaxBreakdown(
            $subtotalBeforeNoteTaxRupiah,
            self::normalizeTaxInput($noteTaxInput),
            $noteTaxMode,
            $noteTaxRateBasisPoints,
            $noteTaxAmountRupiah,
        );

        if ($workItems !== [] && ! self::calculateTotalFromWorkItems($workItems)->equals($subtotalBeforeNoteTaxRupiah)) {
            throw new DomainException('Subtotal sebelum pajak note tidak konsisten dengan subtotal work item.');
        }

        if ($total->amount() !== $subtotalBeforeNoteTaxRupiah->amount() + $noteTaxAmountRupiah->amount()) {
            throw new DomainException('Total note tidak konsisten dengan subtotal dan pajak note.');
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
            $subtotalBeforeNoteTaxRupiah,
            self::normalizeTaxInput($noteTaxInput),
            $noteTaxMode,
            $noteTaxRateBasisPoints,
            $noteTaxAmountRupiah,
            trim($noteState),
            $closedAt,
            self::normalizeActorId($closedByActorId),
            $reopenedAt,
            self::normalizeActorId($reopenedByActorId),
        );
    }
}
