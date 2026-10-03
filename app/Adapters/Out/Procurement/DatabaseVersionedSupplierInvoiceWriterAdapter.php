<?php

declare(strict_types=1);

namespace App\Adapters\Out\Procurement;

use App\Adapters\Out\Procurement\Concerns\LoadsCurrentSupplierInvoiceWriteSnapshot;
use App\Adapters\Out\Procurement\Concerns\PersistsVersionedSupplierInvoiceWrites;
use App\Adapters\Out\Procurement\Concerns\RecordsSupplierInvoiceHistory;
use App\Adapters\Out\Procurement\Concerns\SupplierInvoiceWritePayloads;
use App\Application\Procurement\Context\SupplierInvoiceChangeContext;
use App\Core\Procurement\SupplierInvoice\SupplierInvoice;
use App\Ports\Out\ClockPort;
use App\Ports\Out\Procurement\SupplierInvoiceLifecyclePort;
use App\Ports\Out\Procurement\SupplierInvoiceReaderPort;
use App\Ports\Out\Procurement\SupplierInvoiceWriterPort;
use App\Ports\Out\UuidPort;

final class DatabaseVersionedSupplierInvoiceWriterAdapter implements SupplierInvoiceWriterPort, SupplierInvoiceLifecyclePort
{
    use LoadsCurrentSupplierInvoiceWriteSnapshot;
    use PersistsVersionedSupplierInvoiceWrites;
    use RecordsSupplierInvoiceHistory;
    use SupplierInvoiceWritePayloads;

    public function __construct(
        private readonly UuidPort $uuid,
        private readonly SupplierInvoiceReaderPort $reader,
        private readonly ClockPort $clock,
        private readonly SupplierInvoiceChangeContext $changeContext,
    ) {
    }

    public function create(SupplierInvoice $supplierInvoice): void
    {
        $this->persistCreatedInvoice($supplierInvoice);
    }

    public function update(SupplierInvoice $supplierInvoice): void
    {
        $this->persistUpdatedInvoice($supplierInvoice);
    }
}
