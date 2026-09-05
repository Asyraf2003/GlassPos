<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Payment\Services;

use App\Application\Payment\DTO\PayableNoteComponent;
use App\Application\Payment\Services\AllocatePaymentAcrossComponents;
use App\Application\Payment\Services\ReversedRefundedStoreStockPartPaymentGuard;
use App\Core\Payment\PaymentComponentAllocation\PaymentComponentAllocation;
use App\Core\Payment\PaymentComponentAllocation\PaymentComponentType;
use App\Core\Shared\ValueObjects\Money;
use App\Ports\Out\Inventory\InventoryMovementReaderPort;
use App\Ports\Out\Payment\PaymentComponentAllocationReaderPort;
use App\Ports\Out\Payment\RefundComponentAllocationReaderPort;
use App\Ports\Out\UuidPort;
use PHPUnit\Framework\TestCase;

final class AllocatePaymentAcrossComponentsTest extends TestCase
{
    public function test_it_prioritizes_external_purchase_then_store_product_before_service_fee(): void
    {
        $refunds = new class implements RefundComponentAllocationReaderPort
        {
            public function getTotalRefundedAmountByNoteId(string $noteId): Money
            {
                return Money::zero();
            }

            public function getTotalRefundedAmountByCustomerPaymentIdAndNoteId(string $customerPaymentId, string $noteId): Money
            {
                return Money::zero();
            }

            public function getTotalRefundedAmountByWorkItemId(string $workItemId): Money
            {
                return Money::zero();
            }

            public function listByNoteId(string $noteId): array
            {
                return [];
            }
        };

        $inventoryMovements = new class implements InventoryMovementReaderPort
        {
            public function getAll(): array
            {
                return [];
            }

            public function getBySource(string $sourceType, string $sourceId): array
            {
                return [];
            }
        };

        $service = new AllocatePaymentAcrossComponents(
            new class implements PaymentComponentAllocationReaderPort
            {
                public function getTotalAllocatedAmountByNoteId(string $noteId): Money
                {
                    return Money::zero();
                }

                public function getTotalAllocatedAmountByCustomerPaymentIdAndNoteId(string $customerPaymentId, string $noteId): Money
                {
                    return Money::zero();
                }

                public function getTotalAllocatedAmountByWorkItemId(string $workItemId): Money
                {
                    return Money::zero();
                }

                public function listByNoteId(string $noteId): array
                {
                    return [];
                }
            },
            $refunds,
            new ReversedRefundedStoreStockPartPaymentGuard($refunds, $inventoryMovements),
            new class implements UuidPort
            {
                private int $i = 0;

                public function generate(): string
                {
                    return 'alloc-'.++$this->i;
                }
            },
        );

        $allocations = $service->allocate('payment-1', 'note-1', Money::fromInt(7000), [
            new PayableNoteComponent('wi-2', PaymentComponentType::SERVICE_FEE, 'wi-2', Money::fromInt(5000), 2),
            new PayableNoteComponent('wi-1', PaymentComponentType::PRODUCT_ONLY_WORK_ITEM, 'wi-1', Money::fromInt(5000), 1),
            new PayableNoteComponent('wi-3', PaymentComponentType::SERVICE_EXTERNAL_PURCHASE_PART, 'ext-1', Money::fromInt(2000), 3),
        ]);

        $this->assertCount(2, $allocations);
        $this->assertContainsOnlyInstancesOf(PaymentComponentAllocation::class, $allocations);

        $this->assertSame(PaymentComponentType::SERVICE_EXTERNAL_PURCHASE_PART, $allocations[0]->componentType());
        $this->assertSame(2000, $allocations[0]->allocatedAmountRupiah()->amount());

        $this->assertSame(PaymentComponentType::PRODUCT_ONLY_WORK_ITEM, $allocations[1]->componentType());
        $this->assertSame(5000, $allocations[1]->allocatedAmountRupiah()->amount());
    }
}
