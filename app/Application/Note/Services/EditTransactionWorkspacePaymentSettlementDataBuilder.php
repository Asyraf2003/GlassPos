<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

final class EditTransactionWorkspacePaymentSettlementDataBuilder
{
    public function __construct(
        private readonly NoteOutstandingPaymentAmountResolver $paymentSettlement,
    ) {
    }

    /** @return array<string, mixed>|null */
    public function build(string $noteId): ?array
    {
        $result = $this->paymentSettlement->preview($noteId);

        if ($result->isFailure()) {
            return null;
        }

        $data = $result->data();

        return is_array($data) ? $data : null;
    }
}
