<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Core\Note\Note\Note;

final class NoteDetailRevisionViewDataBuilder
{
    public function __construct(
        private readonly NoteCurrentRevisionResolver $revisions,
        private readonly NoteDetailRevisionTimelineBuilder $timeline,
    ) {
    }

    /**
     * @return array{
     *   current_revision_id: ?string,
     *   customer_name: string,
     *   customer_phone: ?string,
     *   transaction_date: string,
     *   revision_timeline: array
     * }
     */
    public function build(Note $note): array
    {
        if (! $this->revisions->hasRevision($note->id())) {
            return [
                'current_revision_id' => null,
                'customer_name' => $note->customerName(),
                'customer_phone' => $note->customerPhone(),
                'transaction_date' => $note->transactionDate()->format('Y-m-d'),
                'revision_timeline' => [],
            ];
        }

        $current = $this->revisions->resolveOrFail($note->id());

        return [
            'current_revision_id' => $current->id(),
            'customer_name' => $current->customerName(),
            'customer_phone' => $current->customerPhone(),
            'transaction_date' => $current->transactionDate()->format('Y-m-d'),
            'revision_timeline' => $this->timeline->build(
                $current,
                $this->revisions->timeline($note->id()),
            ),
        ];
    }
}
