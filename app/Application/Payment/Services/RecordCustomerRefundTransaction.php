<?php

declare(strict_types=1);

namespace App\Application\Payment\Services;

use App\Application\Inventory\Services\AutoReverseRefundedStoreStockInventory;
use App\Application\Note\Services\AutoRefundNoteWhenFullyRefunded;
use App\Application\Note\Services\NoteHistoryProjectionService;
use App\Application\Payment\UseCases\RecordCustomerRefundSupportTrait;
use App\Application\Shared\DTO\Result;
use App\Core\Shared\Exceptions\DomainException;
use App\Ports\Out\AuditLogPort;
use Throwable;

final class RecordCustomerRefundTransaction
{
    use RecordCustomerRefundSupportTrait;

    public function __construct(
        private readonly RecordCustomerRefundOperation $operation,
        private readonly PaymentTransactionRetryRunner $transactions,
        private readonly AuditLogPort $audit,
        private readonly AutoRefundNoteWhenFullyRefunded $refundLifecycle,
        private readonly AutoReverseRefundedStoreStockInventory $reverseRefundedInventory,
        private readonly NoteHistoryProjectionService $projection,
    ) {
    }

    /** @param list<string> $selectedRowIds */
    public function run(
        string $customerPaymentId,
        string $noteId,
        int $amountRupiah,
        string $refundedAt,
        string $reason,
        string $performedByActorId,
        array $selectedRowIds = [],
    ): Result {
        try {
            return $this->transactions->run(function () use (
                $customerPaymentId,
                $noteId,
                $amountRupiah,
                $refundedAt,
                $reason,
                $performedByActorId,
                $selectedRowIds,
            ): Result {
                $recorded = $this->operation->execute(
                    $customerPaymentId,
                    $noteId,
                    $amountRupiah,
                    $refundedAt,
                    $reason,
                    $selectedRowIds,
                );

                $refund = $recorded->refund();

                $this->reverseRefundedInventory->execute($refund);
                $this->refundLifecycle->refundIfEligible(
                    $noteId,
                    $performedByActorId,
                    'kasir',
                    $reason,
                    $customerPaymentId,
                    $refund->id(),
                );

                $this->audit->record('customer_refund_recorded', array_merge(
                    $this->formatAuditPayload($refund, $performedByActorId),
                    [
                        'refund_allocation_count' => $recorded->allocationCount(),
                        'selected_row_ids' => $selectedRowIds,
                    ],
                ));

                $this->projection->syncNote($noteId);

                return Result::success(
                    array_merge($this->formatSuccessPayload($refund), [
                        'refund_allocation_count' => $recorded->allocationCount(),
                    ]),
                    'Pengembalian dana pelanggan berhasil dicatat.',
                );
            });
        } catch (DomainException $e) {
            return $this->classify($e);
        } catch (Throwable $e) {
            throw $e;
        }
    }
}
