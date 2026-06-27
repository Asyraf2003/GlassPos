<?php

declare(strict_types=1);

namespace App\Application\Payment\UseCases;

use App\Application\Shared\DTO\Result;
use App\Core\Payment\CustomerPayment\CustomerPayment;
use App\Core\Shared\Exceptions\DomainException;
use App\Core\Shared\ValueObjects\Money;
use App\Ports\Out\Payment\CustomerPaymentWriterPort;
use App\Ports\Out\UuidPort;
use DateTimeImmutable;

final class RecordCustomerPaymentHandler
{
    public function __construct(
        private readonly CustomerPaymentWriterPort $customerPayments,
        private readonly UuidPort $uuid,
    ) {
    }

    public function handle(
        int $amountRupiah,
        string $paidAt,
    ): Result {
        try {
            $customerPayment = CustomerPayment::create(
                $this->uuid->generate(),
                Money::fromInt($amountRupiah),
                $this->parsePaidAt($paidAt),
            );
        } catch (DomainException $e) {
            return Result::failure(
                $e->getMessage(),
                ['payment' => ['INVALID_CUSTOMER_PAYMENT']]
            );
        }

        $this->customerPayments->create($customerPayment);

        return Result::success(
            [
                'payment' => [
                    'id' => $customerPayment->id(),
                    'amount_rupiah' => $customerPayment->amountRupiah()->amount(),
                    'paid_at' => $customerPayment->paidAt()->format('Y-m-d'),
                ],
            ],
            'Pembayaran pelanggan berhasil dicatat.'
        );
    }

    private function parsePaidAt(string $paidAt): DateTimeImmutable
    {
        $normalized = trim($paidAt);
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $normalized);

        if ($parsed === false || $parsed->format('Y-m-d') !== $normalized) {
            throw new DomainException('Paid at pada customer payment wajib berupa tanggal yang valid dengan format Y-m-d.');
        }

        return $parsed;
    }
}
