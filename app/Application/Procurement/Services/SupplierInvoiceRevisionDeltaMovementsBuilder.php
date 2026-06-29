<?php

declare(strict_types=1);

namespace App\Application\Procurement\Services;

use App\Core\Inventory\Movement\InventoryMovement;
use App\Core\Procurement\SupplierInvoice\SupplierInvoice;
use DateTimeImmutable;

final class SupplierInvoiceRevisionDeltaMovementsBuilder
{
    public function __construct(
        private readonly SupplierInvoiceRevisionMovementFactory $movements,
        private readonly SupplierInvoiceRevisionLineMapFactory $lineMaps,
        private readonly SupplierInvoiceRevisionPairedLineDeltaResolver $pairedDeltaResolver,
        private readonly SupplierInvoiceRevisionPreviousLineResolver $previousLineResolver,
    ) {
    }

    /**
     * @param list<array<string, mixed>> $requestLines
     * @return list<InventoryMovement>
     */
    public function build(
        SupplierInvoice $current,
        SupplierInvoice $updated,
        array $requestLines,
        DateTimeImmutable $movementDate,
    ): array {
        $oldLinesById = $this->lineMaps->oldLinesById($current);
        $newLinesByLineNo = $this->lineMaps->newLinesByLineNo($updated);
        $referencedOldIds = [];
        $deltaMovements = [];

        foreach ($requestLines as $requestLine) {
            if (! is_array($requestLine)) {
                continue;
            }

            $newLine = $newLinesByLineNo[(int) ($requestLine['line_no'] ?? 0)] ?? null;
            if ($newLine === null) {
                continue;
            }

            $previousLineId = $this->previousLineResolver->resolve(
                $requestLine,
                $newLine,
                $oldLinesById,
                $referencedOldIds,
            );

            if ($previousLineId !== null) {
                $referencedOldIds[$previousLineId] = true;

                array_push(
                    $deltaMovements,
                    ...$this->pairedDeltaResolver->resolve(
                        $oldLinesById[$previousLineId],
                        $newLine,
                        $movementDate,
                    )
                );

                continue;
            }

            $deltaMovements[] = $this->movements->stockIn($newLine, $movementDate, $newLine->qtyPcs());
        }

        foreach ($oldLinesById as $oldLineId => $oldLine) {
            if (! isset($referencedOldIds[$oldLineId])) {
                $deltaMovements[] = $this->movements->stockOut($oldLine, $movementDate, $oldLine->qtyPcs());
            }
        }

        return $deltaMovements;
    }
}
