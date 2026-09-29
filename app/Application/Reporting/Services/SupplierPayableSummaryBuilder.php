<?php

declare(strict_types=1);

namespace App\Application\Reporting\Services;

use App\Application\Reporting\DTO\SupplierPayableSummaryRow;

final class SupplierPayableSummaryBuilder
{
    public function __construct(
        private readonly SupplierPayableDueStatusResolver $statusResolver,
    ) {}

    public function build(array $rows, string $referenceDate): array
    {
        return array_map(function (array $row) use ($referenceDate): SupplierPayableSummaryRow {
            $outstanding = $row['grand_total_rupiah'] - $row['total_paid_rupiah'];
            $status = $this->statusResolver->resolve(
                $row['due_date'],
                $outstanding,
                $referenceDate,
            );

            if ($row['voided_as_of'] ?? false) {
                $status = ['due_status' => 'voided', 'due_status_label' => 'Void'];
            }

            return new SupplierPayableSummaryRow(
                $row['supplier_invoice_id'],
                $row['nomor_faktur'] ?? $row['supplier_invoice_id'],
                $row['supplier_id'],
                $row['supplier_name'] ?? $row['supplier_id'],
                $row['shipment_date'],
                $row['due_date'],
                $row['grand_total_rupiah'],
                $row['total_paid_rupiah'],
                $outstanding,
                $row['receipt_count'],
                $row['total_received_qty'],
                $status['due_status'],
                $status['due_status_label'],
            );
        }, $rows);
    }
}
