<?php

declare(strict_types=1);

namespace App\Core\Note\Revision;

use App\Core\Note\Revision\Concerns\NoteRevisionAccessors;
use App\Core\Note\Revision\Concerns\NoteRevisionValidation;
use DateTimeImmutable;

final class NoteRevision
{
    use NoteRevisionAccessors;
    use NoteRevisionValidation;

    /**
     * @param list<NoteRevisionLineSnapshot> $lines
     */
    private function __construct(
        private string $id,
        private string $noteRootId,
        private int $revisionNumber,
        private ?string $parentRevisionId,
        private ?string $createdByActorId,
        private ?string $reason,
        private string $customerName,
        private ?string $customerPhone,
        private DateTimeImmutable $transactionDate,
        private int $grandTotalRupiah,
        private array $lines,
        private DateTimeImmutable $createdAt,
    ) {
    }

    /**
     * @param list<NoteRevisionLineSnapshot> $lines
     */
    public static function create(
        string $id,
        string $noteRootId,
        int $revisionNumber,
        ?string $parentRevisionId,
        ?string $createdByActorId,
        ?string $reason,
        string $customerName,
        ?string $customerPhone,
        DateTimeImmutable $transactionDate,
        int $grandTotalRupiah,
        array $lines,
        DateTimeImmutable $createdAt,
    ): self {
        $id = trim($id);
        $noteRootId = trim($noteRootId);
        $customerName = trim($customerName);

        self::assertValidState(
            $id,
            $noteRootId,
            $revisionNumber,
            $customerName,
            $grandTotalRupiah,
            $lines,
        );

        return new self(
            $id,
            $noteRootId,
            $revisionNumber,
            $parentRevisionId !== null && trim($parentRevisionId) !== '' ? trim($parentRevisionId) : null,
            $createdByActorId !== null && trim($createdByActorId) !== '' ? trim($createdByActorId) : null,
            $reason !== null && trim($reason) !== '' ? trim($reason) : null,
            $customerName,
            $customerPhone !== null && trim($customerPhone) !== '' ? trim($customerPhone) : null,
            $transactionDate,
            $grandTotalRupiah,
            array_values($lines),
            $createdAt,
        );
    }
}
