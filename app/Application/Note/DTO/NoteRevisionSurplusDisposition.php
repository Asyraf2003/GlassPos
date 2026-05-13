<?php

declare(strict_types=1);

namespace App\Application\Note\DTO;

use App\Core\Shared\Exceptions\DomainException;
use DateTimeImmutable;

final class NoteRevisionSurplusDisposition
{
    public const TYPE_REFUND_DUE = 'refund_due';
    public const STATUS_ACTIVE = 'active';

    private function __construct(
        public readonly string $id,
        public readonly string $noteRevisionSettlementId,
        public readonly string $noteRootId,
        public readonly string $noteRevisionId,
        public readonly string $dispositionType,
        public readonly int $amountRupiah,
        public readonly int $beforePendingRupiah,
        public readonly int $afterPendingRupiah,
        public readonly string $status,
        public readonly DateTimeImmutable $occurredAt,
        public readonly DateTimeImmutable $createdAt,
        public readonly string $auditEventId,
    ) {
    }

    public static function create(
        string $id,
        string $noteRevisionSettlementId,
        string $noteRootId,
        string $noteRevisionId,
        string $dispositionType,
        int $amountRupiah,
        int $beforePendingRupiah,
        int $afterPendingRupiah,
        string $status,
        DateTimeImmutable $occurredAt,
        DateTimeImmutable $createdAt,
        string $auditEventId,
    ): self {
        $id = trim($id);
        $noteRevisionSettlementId = trim($noteRevisionSettlementId);
        $noteRootId = trim($noteRootId);
        $noteRevisionId = trim($noteRevisionId);
        $dispositionType = trim($dispositionType);
        $status = trim($status);
        $auditEventId = trim($auditEventId);

        if ($id === '' || $noteRevisionSettlementId === '' || $noteRootId === '' || $noteRevisionId === '' || $auditEventId === '') {
            throw new DomainException('Surplus disposition identity wajib diisi.');
        }

        if ($dispositionType !== self::TYPE_REFUND_DUE) {
            throw new DomainException('Surplus disposition type tidak didukung.');
        }

        if ($status !== self::STATUS_ACTIVE) {
            throw new DomainException('Surplus disposition status tidak didukung.');
        }

        if ($amountRupiah <= 0 || $beforePendingRupiah < 0 || $afterPendingRupiah < 0) {
            throw new DomainException('Surplus disposition amount tidak valid.');
        }

        if ($afterPendingRupiah !== $beforePendingRupiah - $amountRupiah) {
            throw new DomainException('Surplus disposition pending amount tidak seimbang.');
        }

        return new self(
            $id,
            $noteRevisionSettlementId,
            $noteRootId,
            $noteRevisionId,
            $dispositionType,
            $amountRupiah,
            $beforePendingRupiah,
            $afterPendingRupiah,
            $status,
            $occurredAt,
            $createdAt,
            $auditEventId,
        );
    }
}
