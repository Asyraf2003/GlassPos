<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Application\Shared\DTO\Result;
use App\Core\Note\Domain\Entities\Note;

final class CreateTransactionWorkspaceResultBuilder
{
    public function __construct(
        private readonly CreateTransactionWorkspaceSuccessMessageBuilder $messages,
    ) {
    }

    /**
     * @param array<string, mixed> $paymentSummary
     */
    public function build(Note $note, array $paymentSummary): Result
    {
        return Result::success(
            [
                'note' => [
                    'id' => $note->id(),
                    'customer_name' => $note->customerName(),
                    'transaction_date' => $note->transactionDate()->format('Y-m-d'),
                    'total_rupiah' => $note->totalRupiah()->amount(),
                ],
                'inline_payment' => $paymentSummary,
            ],
            $this->messages->build($paymentSummary)
        );
    }
}
