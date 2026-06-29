<?php

declare(strict_types=1);

namespace App\Adapters\Out\Procurement;

use App\Adapters\Out\Procurement\Concerns\ProcurementInvoiceDetailLinesQuery;
use App\Adapters\Out\Procurement\Concerns\ProcurementInvoiceDetailPayload;
use App\Adapters\Out\Procurement\Concerns\ProcurementInvoiceDetailSummaryQuery;
use App\Adapters\Out\Procurement\Concerns\ProcurementInvoiceDetailVersionTimelineQuery;
use App\Ports\Out\Procurement\ProcurementInvoiceDetailReaderPort;

final class DatabaseProcurementInvoiceDetailReaderAdapter implements ProcurementInvoiceDetailReaderPort
{
    use ProcurementInvoiceDetailLinesQuery;
    use ProcurementInvoiceDetailPayload;
    use ProcurementInvoiceDetailSummaryQuery;
    use ProcurementInvoiceDetailVersionTimelineQuery;

    public function getById(string $supplierInvoiceId): ?array
    {
        $summary = $this->getSummaryRow($supplierInvoiceId);

        if ($summary === null) {
            return null;
        }

        return $this->toDetailPayload(
            $summary,
            $this->getLineRows($supplierInvoiceId),
            $this->getVersionTimelineRows($supplierInvoiceId),
        );
    }
}
