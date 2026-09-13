<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Application\Shared\DTO\Result;
use App\Core\Payment\CustomerPayment\CustomerPayment;

final class NotePaymentAmountResolver
{
    public function __construct(
        private readonly NoteOutstandingPaymentAmountResolver $outstanding,
    ) {}

    public function resolve(
        string $noteId,
        string $paymentMethod,
        int $requestedAmountRupiah,
        ?int $amountReceivedRupiah,
    ): Result {
        $outstandingResult = $this->outstanding->resolveFull($noteId);
        if ($outstandingResult->isFailure()) {
            return $outstandingResult;
        }

        $outstanding = (int) ($outstandingResult->data()['outstanding_rupiah'] ?? 0);
        if ($outstanding <= 0) {
            return Result::failure('Nota sudah tidak memiliki sisa tagihan.', ['payment' => ['INVALID_PAYMENT_AMOUNT']]);
        }

        $isCash = $paymentMethod === CustomerPayment::METHOD_CASH;
        // An omitted amount is the full-payment preset; tender never defines partial intent.
        $settlementIntent = $requestedAmountRupiah > 0 ? $requestedAmountRupiah : $outstanding;

        if ($settlementIntent > $outstanding) {
            return Result::failure('Nominal pembayaran melebihi sisa tagihan.', ['payment' => ['INVALID_PAYMENT_AMOUNT']]);
        }

        if ($isCash && (int) $amountReceivedRupiah < $settlementIntent) {
            return Result::failure('Uang masuk kurang dari nominal pembayaran.', ['payment' => ['INVALID_PAYMENT_AMOUNT']]);
        }

        return Result::success([
            'amount_rupiah' => $settlementIntent,
            'outstanding_rupiah' => $outstanding,
        ]);
    }
}
