<?php

declare(strict_types=1);

namespace App\Application\Procurement\Services;

use App\Core\Procurement\SupplierInvoice\SupplierInvoiceLine;

final class SupplierInvoiceRevisionPreviousLineResolver
{
    /**
     * @param array<string, mixed> $requestLine
     * @param array<string, SupplierInvoiceLine> $oldLinesById
     * @param array<string, bool> $referencedOldIds
     */
    public function resolve(
        array $requestLine,
        SupplierInvoiceLine $newLine,
        array $oldLinesById,
        array $referencedOldIds,
    ): ?string {
        $previousLineId = $this->previousLineId($requestLine);

        if ($previousLineId !== '' && isset($oldLinesById[$previousLineId]) && ! isset($referencedOldIds[$previousLineId])) {
            return $previousLineId;
        }

        return $this->fallbackPreviousLineId($newLine, $oldLinesById, $referencedOldIds);
    }

    /**
     * @param array<string, SupplierInvoiceLine> $oldLinesById
     * @param array<string, bool> $referencedOldIds
     */
    private function fallbackPreviousLineId(
        SupplierInvoiceLine $newLine,
        array $oldLinesById,
        array $referencedOldIds,
    ): ?string {
        $matchedOldLineId = null;

        foreach ($oldLinesById as $oldLineId => $oldLine) {
            if (isset($referencedOldIds[$oldLineId])) {
                continue;
            }

            if (! $this->isSafeFallbackPair($oldLine, $newLine)) {
                continue;
            }

            if ($matchedOldLineId !== null) {
                return null;
            }

            $matchedOldLineId = $oldLineId;
        }

        return $matchedOldLineId;
    }

    private function isSafeFallbackPair(SupplierInvoiceLine $oldLine, SupplierInvoiceLine $newLine): bool
    {
        return $oldLine->lineNo() === $newLine->lineNo()
            && $oldLine->productId() === $newLine->productId()
            && $oldLine->qtyPcs() === $newLine->qtyPcs();
    }

    /**
     * @param array<string, mixed> $requestLine
     */
    private function previousLineId(array $requestLine): string
    {
        $value = $requestLine['previous_line_id'] ?? null;

        return is_string($value) ? trim($value) : '';
    }
}
