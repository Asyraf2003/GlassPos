<?php

declare(strict_types=1);

namespace App\Application\Reporting\DTO;

use App\Application\Reporting\DTO\Concerns\TransactionCashLedgerPerNoteRowSourceAccessors;

final class TransactionCashLedgerPerNoteRow
{
    use TransactionCashLedgerPerNoteRowSourceAccessors;
    public function __construct(
        private readonly string $noteId,
        private readonly string $eventDate,
        private readonly string $eventType,
        private readonly string $direction,
        private readonly int $eventAmountRupiah,
        private readonly ?string $paymentMethod,
        private readonly ?int $cashAmountPaidRupiah,
        private readonly ?int $cashAmountReceivedRupiah,
        private readonly ?int $cashChangeRupiah,
        private readonly ?string $customerPaymentId,
        private readonly ?string $refundId,
        private readonly string $sourceTable,
        private readonly string $sourceId,
        private readonly ?string $sourceDispositionId,
    ) {
    }

    public function noteId(): string
    {
        return $this->noteId;
    }

    public function eventDate(): string
    {
        return $this->eventDate;
    }

    public function eventType(): string
    {
        return $this->eventType;
    }

    public function direction(): string
    {
        return $this->direction;
    }

    public function eventAmountRupiah(): int
    {
        return $this->eventAmountRupiah;
    }

    public function paymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    public function cashAmountPaidRupiah(): ?int
    {
        return $this->cashAmountPaidRupiah;
    }

    public function cashAmountReceivedRupiah(): ?int
    {
        return $this->cashAmountReceivedRupiah;
    }

    public function cashChangeRupiah(): ?int
    {
        return $this->cashChangeRupiah;
    }

    public function customerPaymentId(): ?string
    {
        return $this->customerPaymentId;
    }

    public function refundId(): ?string
    {
        return $this->refundId;
    }

    /**
     * @return array{
     *   note_id:string,
     *   event_date:string,
     *   event_type:string,
     *   direction:string,
     *   event_amount_rupiah:int,
     *   payment_method:?string,
     *   cash_amount_paid_rupiah:?int,
     *   cash_amount_received_rupiah:?int,
     *   cash_change_rupiah:?int,
     *   customer_payment_id:?string,
     *   refund_id:?string,
     *   source_table:string,
     *   source_id:string,
     *   source_disposition_id:?string
     * }
     */
    public function toArray(): array
    {
        return [
            'note_id' => $this->noteId(),
            'event_date' => $this->eventDate(),
            'event_type' => $this->eventType(),
            'direction' => $this->direction(),
            'event_amount_rupiah' => $this->eventAmountRupiah(),
            'payment_method' => $this->paymentMethod(),
            'cash_amount_paid_rupiah' => $this->cashAmountPaidRupiah(),
            'cash_amount_received_rupiah' => $this->cashAmountReceivedRupiah(),
            'cash_change_rupiah' => $this->cashChangeRupiah(),
            'customer_payment_id' => $this->customerPaymentId(),
            'refund_id' => $this->refundId(),
            'source_table' => $this->sourceTable(),
            'source_id' => $this->sourceId(),
            'source_disposition_id' => $this->sourceDispositionId(),
        ];
    }
}
