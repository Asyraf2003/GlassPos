<?php

declare(strict_types=1);

namespace App\Application\Payment\Services;

use App\Core\Payment\PaymentComponentAllocation\PaymentComponentAllocation;
use App\Ports\Out\Note\NoteReaderPort;
use Illuminate\Support\Facades\DB;

final class LegacyPaymentComponentAllocationSynthesizer
{
    public function __construct(
        private readonly NoteReaderPort $notes,
        private readonly LegacyPaymentComponentAllocationBuilder $builder,
    ) {
    }

    /** @return list<PaymentComponentAllocation> */
    public function forNote(string $noteId): array
    {
        $noteId = trim($noteId);
        $note = $this->notes->getById($noteId);

        if ($note === null) {
            return [];
        }

        $legacyAllocations = $this->legacyAllocations($noteId);
        if ($legacyAllocations === []) {
            return [];
        }

        return $this->builder->build($noteId, $note->workItems(), $legacyAllocations);
    }

    /**
     * @param list<string> $selectedRowIds
     * @return list<PaymentComponentAllocation>
     */
    public function forPayment(string $customerPaymentId, string $noteId, array $selectedRowIds = []): array
    {
        $paymentId = trim($customerPaymentId);
        $selectedIds = PaymentComponentSelectionIds::normalize($selectedRowIds);

        $allocations = array_filter(
            $this->forNote($noteId),
            static function (PaymentComponentAllocation $allocation) use ($paymentId, $selectedIds): bool {
                return $allocation->customerPaymentId() === $paymentId
                    && PaymentComponentSelectionIds::matches($allocation, $selectedIds)
                    && RefundComponentTypePolicy::isDefaultRefundable($allocation->componentType());
            },
        );

        usort(
            $allocations,
            static fn (PaymentComponentAllocation $left, PaymentComponentAllocation $right): int =>
                $right->allocationPriority() <=> $left->allocationPriority(),
        );

        return $allocations;
    }

    /** @return list<object> */
    private function legacyAllocations(string $noteId): array
    {
        $componentPaymentIds = DB::table('payment_component_allocations')
            ->where('note_id', $noteId)
            ->select('customer_payment_id');

        return DB::table('payment_allocations')
            ->where('note_id', $noteId)
            ->whereNotIn('customer_payment_id', $componentPaymentIds)
            ->orderBy('id')
            ->get(['id', 'customer_payment_id', 'amount_rupiah'])
            ->all();
    }
}
