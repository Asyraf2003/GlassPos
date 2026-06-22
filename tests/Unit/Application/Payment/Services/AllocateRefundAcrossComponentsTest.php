<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Payment\Services;

use App\Application\Payment\Services\AllocateRefundAcrossComponents;
use App\Application\Payment\Services\LegacyPaymentComponentAllocationBuilder;
use App\Application\Payment\Services\LegacyPaymentComponentAllocationSynthesizer;
use App\Core\Note\Note\Note;
use App\Core\Payment\PaymentComponentAllocation\PaymentComponentAllocation;
use App\Core\Payment\PaymentComponentAllocation\PaymentComponentType;
use App\Core\Payment\RefundComponentAllocation\RefundComponentAllocation;
use App\Core\Shared\ValueObjects\Money;
use App\Ports\Out\Note\NoteReaderPort;
use App\Ports\Out\Payment\PaymentComponentAllocationReaderPort;
use App\Ports\Out\Payment\RefundComponentAllocationReaderPort;
use App\Ports\Out\UuidPort;
use PHPUnit\Framework\TestCase;

final class AllocateRefundAcrossComponentsTest extends TestCase
{
    public function test_it_refunds_default_refundable_components_only(): void
    {
        $service = new AllocateRefundAcrossComponents(
            new class () implements PaymentComponentAllocationReaderPort {
                public function getTotalAllocatedAmountByNoteId(string $noteId): Money { return Money::zero(); }
                public function getTotalAllocatedAmountByCustomerPaymentIdAndNoteId(string $customerPaymentId, string $noteId): Money { return Money::zero(); }
                public function getTotalAllocatedAmountByWorkItemId(string $workItemId): Money { return Money::zero(); }
                public function listByNoteId(string $noteId): array
                {
                    return [
                        PaymentComponentAllocation::rehydrate('a1', 'pay-1', 'note-1', 'wi-1', PaymentComponentType::PRODUCT_ONLY_WORK_ITEM, 'wi-1', Money::fromInt(5000), Money::fromInt(5000), 1),
                        PaymentComponentAllocation::rehydrate('a2', 'pay-1', 'note-1', 'wi-2', PaymentComponentType::SERVICE_FEE, 'wi-2', Money::fromInt(4000), Money::fromInt(4000), 4),
                    ];
                }
            },
            new class () implements RefundComponentAllocationReaderPort {
                public function getTotalRefundedAmountByNoteId(string $noteId): Money { return Money::zero(); }
                public function getTotalRefundedAmountByCustomerPaymentIdAndNoteId(string $customerPaymentId, string $noteId): Money { return Money::zero(); }
                public function getTotalRefundedAmountByWorkItemId(string $workItemId): Money { return Money::zero(); }
                public function listByNoteId(string $noteId): array { return []; }
            },
            new LegacyPaymentComponentAllocationSynthesizer(
                new class () implements NoteReaderPort {
                    public function countAll(): int { return 0; }
                    public function getById(string $id): ?Note { return null; }
                    public function getByIdForUpdate(string $id): ?Note { return null; }
                },
                new LegacyPaymentComponentAllocationBuilder(),
            ),
            new class () implements UuidPort {
                private int $i = 0;
                public function generate(): string { return 'r-' . ++$this->i; }
            },
        );

        $allocations = $service->allocate('refund-1', 'pay-1', 'note-1', Money::fromInt(4000));

        $this->assertCount(1, $allocations);
        $this->assertContainsOnlyInstancesOf(RefundComponentAllocation::class, $allocations);
        $this->assertSame(PaymentComponentType::PRODUCT_ONLY_WORK_ITEM, $allocations[0]->componentType());
        $this->assertSame(4000, $allocations[0]->refundedAmountRupiah()->amount());
    }
}
