<?php

declare(strict_types=1);

namespace App\Ports\Out\Reporting;

interface SupplierPayableReportingSourceReaderPort
{
    /**
     * Null bounds mean all shipment dates; otherwise both bounds are required.
     * Active payments are current, regardless of their payment date.
     *
     * @return list<array{
     *   supplier_invoice_id:string,
     *   supplier_id:string,
     *   shipment_date:string,
     *   due_date:string,
     *   grand_total_rupiah:int,
     *   total_paid_rupiah:int,
     *   receipt_count:int,
     *   total_received_qty:int
     * }>
     */
    public function getSupplierPayableSummaryRows(
        ?string $fromShipmentDate,
        ?string $toShipmentDate,
    ): array;

    /**
     * @return array{
     *   total_rows:int,
     *   grand_total_rupiah:int,
     *   total_paid_rupiah:int,
     *   outstanding_rupiah:int
     * }
     */
    public function getSupplierPayableSummaryReconciliation(
        ?string $fromShipmentDate,
        ?string $toShipmentDate,
    ): array;
}
