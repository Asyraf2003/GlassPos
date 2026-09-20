<?php

declare(strict_types=1);

namespace App\Application\Payment\DTO;

use App\Core\Payment\CustomerPayment\CustomerPayment;
use App\Core\Payment\CustomerPayment\CustomerPaymentCashDetail;

final class RecordedNotePayment
{
    public function __construct(
        private readonly CustomerPayment $payment,
        private readonly int $allocationCount,
        private readonly ?CustomerPaymentCashDetail $cashDetail = null,
    ) {
    }

    public function payment(): CustomerPayment
    {
        return $this->payment;
    }

    public function allocationCount(): int
    {
        return $this->allocationCount;
    }

    public function cashDetail(): ?CustomerPaymentCashDetail
    {
        return $this->cashDetail;
    }
}
