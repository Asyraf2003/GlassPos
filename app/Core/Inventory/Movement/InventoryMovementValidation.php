<?php

declare(strict_types=1);

namespace App\Core\Inventory\Movement;

use App\Core\Shared\Exceptions\DomainException;
use App\Core\Shared\ValueObjects\Money;

trait InventoryMovementValidation
{
    private static function assertValid(
        string $id, string $pId, string $mType, string $sType, string $sId, int $qty, Money $unitCost
    ): void {
        if (trim($id) === '') throw new DomainException('ID wajib ada.');
        if (trim($pId) === '') throw new DomainException('Product ID wajib ada.');
        if (trim($mType) === '') throw new DomainException('Movement type wajib ada.');
        if (trim($sType) === '') throw new DomainException('Source type wajib ada.');
        if (trim($sId) === '') throw new DomainException('Source ID wajib ada.');
        if ($qty === 0 && $mType !== 'cost_revaluation') throw new DomainException('Qty delta tidak boleh nol.');
        if ($qty !== 0 && $mType === 'cost_revaluation') throw new DomainException('Cost revaluation tidak boleh mengubah qty.');
        if ($unitCost->isNegative()) throw new DomainException('Unit cost tidak boleh negatif.');
    }
}
