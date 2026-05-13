<?php

declare(strict_types=1);

namespace App\Application\Note\DTO;

use App\Core\Shared\Exceptions\DomainException;

final class NoteRevisionSurplusPending
{
    private function __construct(
        public readonly string $noteRevisionSettlementId,
        public readonly string $noteRootId,
        public readonly string $noteRevisionId,
        public readonly int $surplusRupiah,
        public readonly int $activeDispositionRupiah,
        public readonly int $unresolvedPendingRupiah,
    ) {
    }

    public static function create(
        string $noteRevisionSettlementId,
        string $noteRootId,
        string $noteRevisionId,
        int $surplusRupiah,
        int $activeDispositionRupiah,
    ): self {
        $noteRevisionSettlementId = trim($noteRevisionSettlementId);
        $noteRootId = trim($noteRootId);
        $noteRevisionId = trim($noteRevisionId);

        if ($noteRevisionSettlementId === '' || $noteRootId === '' || $noteRevisionId === '') {
            throw new DomainException('Surplus pending identity wajib diisi.');
        }

        if ($surplusRupiah < 0 || $activeDispositionRupiah < 0 || $activeDispositionRupiah > $surplusRupiah) {
            throw new DomainException('Surplus pending amount tidak valid.');
        }

        return new self(
            $noteRevisionSettlementId,
            $noteRootId,
            $noteRevisionId,
            $surplusRupiah,
            $activeDispositionRupiah,
            $surplusRupiah - $activeDispositionRupiah,
        );
    }
}
