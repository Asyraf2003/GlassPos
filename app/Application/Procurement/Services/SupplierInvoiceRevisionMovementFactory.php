<?php

declare(strict_types=1);

namespace App\Application\Procurement\Services;

use App\Core\Inventory\Movement\InventoryMovement;
use App\Core\Procurement\SupplierInvoice\SupplierInvoiceLine;
use App\Core\Shared\ValueObjects\Money;
use App\Ports\Out\UuidPort;
use DateTimeImmutable;

final class SupplierInvoiceRevisionMovementFactory
{
    public function __construct(
        private readonly UuidPort $uuid,
    ) {
    }

    public function stockIn(SupplierInvoiceLine $line, DateTimeImmutable $movementDate, int $qty): InventoryMovement
    {
        return InventoryMovement::create(
            $this->uuid->generate(),
            $line->productId(),
            'stock_in',
            'supplier_invoice_revision_delta_line',
            $line->id(),
            $movementDate,
            $qty,
            $line->unitCostRupiah(),
        );
    }

    public function stockOut(SupplierInvoiceLine $line, DateTimeImmutable $movementDate, int $qty): InventoryMovement
    {
        return InventoryMovement::create(
            $this->uuid->generate(),
            $line->productId(),
            'stock_out',
            'supplier_invoice_revision_delta_line',
            $line->id(),
            $movementDate,
            -$qty,
            $line->unitCostRupiah(),
        );
    }

    public function costRevaluation(SupplierInvoiceLine $line, DateTimeImmutable $movementDate, int $totalCostDeltaRupiah): InventoryMovement
    {
        return InventoryMovement::createValueOnly(
            $this->uuid->generate(),
            $line->productId(),
            'cost_revaluation',
            'supplier_invoice_cost_revaluation',
            $line->id(),
            $movementDate,
            Money::fromInt($totalCostDeltaRupiah),
        );
    }
}
