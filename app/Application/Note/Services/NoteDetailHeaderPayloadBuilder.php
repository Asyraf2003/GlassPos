<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Core\Note\Note\Note;

final class NoteDetailHeaderPayloadBuilder
{
    public function __construct(private readonly NotePaymentStatusResolver $paymentStatuses) {}

    public function build(Note $note, array $revisionView, array $operational): array
    {
        return [
            'id' => $note->id(),
            'current_revision_id' => $revisionView['current_revision_id'],
            'current_total_rupiah' => $note->totalRupiah()->amount(),
            'customer_name' => $revisionView['customer_name'],
            'customer_phone' => $revisionView['customer_phone'],
            'transaction_date' => $revisionView['transaction_date'],
            'operational_note' => $note->operationalNote(),
            'note_state' => $note->noteState(),
            'payment_status' => $this->paymentStatuses->resolve(
                (int) $operational['grand_total_rupiah'],
                (int) $operational['net_paid_rupiah'],
            ),
        ];
    }
}
