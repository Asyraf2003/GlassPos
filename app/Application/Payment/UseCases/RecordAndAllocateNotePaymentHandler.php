<?php

declare(strict_types=1);

namespace App\Application\Payment\UseCases;

use App\Application\Note\Services\NoteHistoryProjectionService;
use App\Application\Payment\Services\AllocatePaymentErrorClassifier;
use App\Application\Payment\Services\PaymentTransactionRetryRunner;
use App\Application\Payment\Services\RecordAndAllocateNotePaymentAuditPayloadBuilder;
use App\Application\Payment\Services\RecordAndAllocateNotePaymentDurableAudit;
use App\Application\Payment\Services\RecordAndAllocateNotePaymentOperation;
use App\Application\Payment\Services\RecordNotePaymentIdempotencyService;
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
        private readonly RecordNotePaymentIdempotencyService $idempotency,
        private readonly RecordAndAllocateNotePaymentAuditPayloadBuilder $auditPayloads,
        private readonly RecordAndAllocateNotePaymentDurableAudit $durableAudit,
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
        ?array $idempotencyPayload = null,
    ): Result {
        try {
            return $this->transactions->run(function () use (
                $noteId,
                $amountRupiah,
                $paidAt,
                $selectedRowIds,
                $paymentMethod,
                $amountReceivedRupiah,
                $idempotencyPayload,
            ): Result {
                if ($idempotencyPayload !== null) {
                    $this->idempotency->start($idempotencyPayload);
                }

                $recorded = $this->operation->execute(
                    $noteId,
                    $amountRupiah,
                    $paidAt,
                    $selectedRowIds,
                    $paymentMethod,
                    $amountReceivedRupiah,
                );

                $this->audit->record('payment_allocated', $this->auditPayloads->build(
                    $recorded,
                    $noteId,
                    $amountRupiah,
                    $amountReceivedRupiah,
                    $selectedRowIds,
                ));

                $this->projection->syncNote(trim($noteId));

                $result = Result::success([
                    'payment_id' => $recorded->payment()->id(),
                    'allocation_count' => $recorded->allocationCount(),
                ], 'Pembayaran berhasil dicatat.');

                if ($idempotencyPayload !== null) {
                    $this->idempotency->succeed($idempotencyPayload, trim($noteId), $result);
                }
                $this->durableAudit->record($recorded, $noteId, $selectedRowIds, $idempotencyPayload['_actor_id'] ?? null);
                return $result;
            });
        } catch (DomainException $e) {
            return $this->errors->classify($e);
        } catch (Throwable $e) {
            throw $e;
        }
    }
}
