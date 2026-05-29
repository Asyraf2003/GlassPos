<?php

declare(strict_types=1);

namespace App\Application\Payment\UseCases;

use App\Application\Note\Services\NoteHistoryProjectionService;
use App\Application\Payment\Services\AllocatePaymentErrorClassifier;
use App\Application\Payment\Services\PaymentTransactionRetryRunner;
use App\Application\Payment\Services\RecordAndAllocateNotePaymentOperation;
use App\Application\Shared\DTO\Result;
use App\Core\Payment\CustomerPayment\CustomerPayment;
use App\Core\Shared\Exceptions\DomainException;
use App\Ports\Out\AuditLogPort;
use Throwable;

final class RecordAndAllocateNotePaymentHandler
{
    public function __construct(
        private readonly RecordAndAllocateNotePaymentOperation $operation,
        private readonly PaymentTransactionRetryRunner $transactions,
        private readonly AllocatePaymentErrorClassifier $errors,
        private readonly AuditLogPort $audit,
        private readonly NoteHistoryProjectionService $projection,
    ) {
    }

    /**
     * @param list<string> $selectedRowIds
     */
    public function handle(
        string $noteId,
        int $amountRupiah,
        string $paidAt,
        array $selectedRowIds = [],
        string $paymentMethod = CustomerPayment::METHOD_UNKNOWN,
        ?int $amountReceivedRupiah = null,
    ): Result {
        try {
            return $this->transactions->run(function () use (
                $noteId,
                $amountRupiah,
                $paidAt,
                $selectedRowIds,
                $paymentMethod,
                $amountReceivedRupiah,
            ): Result {
                $recorded = $this->operation->execute(
                    $noteId,
                    $amountRupiah,
                    $paidAt,
                    $selectedRowIds,
                    $paymentMethod,
                    $amountReceivedRupiah,
                );

                $this->audit->record('payment_allocated', [
                    'payment_id' => $recorded->payment()->id(),
                    'note_id' => trim($noteId),
                    'amount' => $amountRupiah,
                    'payment_method' => $recorded->payment()->paymentMethod(),
                    'amount_received' => $amountReceivedRupiah,
                    'change' => $this->changeAmount(
                        $recorded->payment()->paymentMethod(),
                        $amountRupiah,
                        $amountReceivedRupiah,
                    ),
                    'allocation_count' => $recorded->allocationCount(),
                    'selected_row_ids' => $selectedRowIds,
                ]);

                $this->projection->syncNote(trim($noteId));

                return Result::success([
                    'payment_id' => $recorded->payment()->id(),
                    'allocation_count' => $recorded->allocationCount(),
                ], 'Pembayaran berhasil dicatat.');
            });
        } catch (DomainException $e) {
            return $this->errors->classify($e);
        } catch (Throwable $e) {
            throw $e;
        }
    }

    private function changeAmount(string $paymentMethod, int $amountRupiah, ?int $amountReceivedRupiah): ?int
    {
        if ($paymentMethod !== CustomerPayment::METHOD_CASH || $amountReceivedRupiah === null) {
            return null;
        }

        return max($amountReceivedRupiah - $amountRupiah, 0);
    }
}
