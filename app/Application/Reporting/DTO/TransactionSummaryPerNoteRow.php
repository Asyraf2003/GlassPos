<?php

declare(strict_types=1);

namespace App\Application\Reporting\DTO;

final class TransactionSummaryPerNoteRow
{
    public function __construct(
        private readonly string $noteId,
        private readonly string $transactionDate,
        private readonly string $customerName,
        private readonly int $grossTransactionRupiah,
        private readonly int $allocatedPaymentRupiah,
        private readonly int $refundedRupiah,
        private readonly int $refundDueRupiah,
        private readonly string $paymentStatusLabel,
        private readonly ?int $outstandingRupiah = null,
    ) {
    }

    public function noteId(): string
    {
        return $this->noteId;
    }

    public function transactionDate(): string
    {
        return $this->transactionDate;
    }

    public function customerName(): string
    {
        return $this->customerName;
    }

    public function grossTransactionRupiah(): int
    {
        return $this->grossTransactionRupiah;
    }

    public function allocatedPaymentRupiah(): int
    {
        return $this->allocatedPaymentRupiah;
    }

    public function refundedRupiah(): int
    {
        return $this->refundedRupiah;
    }

    public function refundDueRupiah(): int
    {
        return $this->refundDueRupiah;
    }

    public function netCashCollectedRupiah(): int
    {
        return $this->allocatedPaymentRupiah - $this->refundedRupiah;
    }

    public function outstandingRupiah(): int
    {
        if ($this->outstandingRupiah !== null) {
            return max($this->outstandingRupiah, 0);
        }

        return max($this->grossTransactionRupiah - $this->allocatedPaymentRupiah + $this->refundedRupiah, 0);
    }

    public function paymentStatusLabel(): string
    {
        return $this->paymentStatusLabel;
    }

    /**
     * @return array{
     *   note_id:string,
     *   transaction_date:string,
     *   customer_name:string,
     *   gross_transaction_rupiah:int,
     *   allocated_payment_rupiah:int,
     *   refunded_rupiah:int,
     *   refund_due_rupiah:int,
     *   net_cash_collected_rupiah:int,
     *   outstanding_rupiah:int,
     *   payment_status_label:string
     * }
     */
    public function toArray(): array
    {
        return [
            'note_id' => $this->noteId(),
            'transaction_date' => $this->transactionDate(),
            'customer_name' => $this->customerName(),
            'gross_transaction_rupiah' => $this->grossTransactionRupiah(),
            'allocated_payment_rupiah' => $this->allocatedPaymentRupiah(),
            'refunded_rupiah' => $this->refundedRupiah(),
            'refund_due_rupiah' => $this->refundDueRupiah(),
            'net_cash_collected_rupiah' => $this->netCashCollectedRupiah(),
            'outstanding_rupiah' => $this->outstandingRupiah(),
            'payment_status_label' => $this->paymentStatusLabel(),
        ];
    }
}
