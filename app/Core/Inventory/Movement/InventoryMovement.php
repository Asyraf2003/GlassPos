<?php

declare(strict_types=1);

namespace App\Core\Inventory\Movement;

use App\Core\Shared\ValueObjects\Money;
use DateTimeImmutable;

final class InventoryMovement
{
    use InventoryMovementState;
    use InventoryMovementValidation;

    public static function create(
        string $id, string $pId, string $mType, string $sType, string $sId,
        DateTimeImmutable $date, int $qty, Money $unitCost
    ): self {
        self::assertValid($id, $pId, $mType, $sType, $sId, $qty, $unitCost);

        return new self(
            $id, trim($pId), trim($mType), trim($sType), trim($sId),
            $date, $qty, $unitCost, $unitCost->multiply($qty)
        );
    }

    public static function createValueOnly(
        string $id, string $pId, string $mType, string $sType, string $sId,
        DateTimeImmutable $date, Money $totalCost
    ): self {
        self::assertValid($id, $pId, $mType, $sType, $sId, 0, Money::zero());

        return new self(
            $id, trim($pId), trim($mType), trim($sType), trim($sId),
            $date, 0, Money::zero(), $totalCost
        );
    }

    public static function rehydrate(
        string $id, string $pId, string $mType, string $sType, string $sId,
        DateTimeImmutable $date, int $qty, Money $unitCost, ?Money $totalCost = null
    ): self {
        self::assertValid($id, $pId, $mType, $sType, $sId, $qty, $unitCost);

        return new self(
            $id, trim($pId), trim($mType), trim($sType), trim($sId),
            $date, $qty, $unitCost, $totalCost ?? $unitCost->multiply($qty)
        );
    }
}
