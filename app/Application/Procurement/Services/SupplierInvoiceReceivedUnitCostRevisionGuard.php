<?php

declare(strict_types=1);

namespace App\Application\Procurement\Services;

use App\Core\Procurement\SupplierInvoice\SupplierInvoice;

final class SupplierInvoiceReceivedUnitCostRevisionGuard
{
    /**
     * @param array<int, mixed> $submittedLines
     */
    public function changesReceivedUnitCost(
        SupplierInvoice $current,
        SupplierInvoice $updated,
        array $submittedLines,
    ): bool {
        $currentLinesById = [];

        foreach ($current->lines() as $currentLine) {
            $currentLinesById[$currentLine->id()] = $currentLine;
        }

        foreach ($updated->lines() as $index => $updatedLine) {
            $submittedLine = $submittedLines[$index] ?? null;

            if (! is_array($submittedLine)) {
                continue;
            }

            $previousLineId = trim((string) ($submittedLine['previous_line_id'] ?? ''));

            if ($previousLineId === '' || ! array_key_exists($previousLineId, $currentLinesById)) {
                continue;
            }

            $currentLine = $currentLinesById[$previousLineId];

            if ($currentLine->productId() !== $updatedLine->productId()) {
                continue;
            }

            if ($currentLine->unitCostRupiah()->amount() !== $updatedLine->unitCostRupiah()->amount()) {
                return true;
            }
        }

        return false;
    }
}
