<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Application\Note\Services\CurrentRevision\CurrentRevisionRowSettlementProjector;
use App\Core\Note\Note\Note;
use App\Core\Note\WorkItem\ServiceDetail;
use App\Core\Note\WorkItem\WorkItem;
use App\Core\Shared\Exceptions\DomainException;

final class BuildPaidServiceCorrectionRevisionPayload
{
    public function __construct(
        private readonly NoteCurrentRevisionResolver $current,
        private readonly CurrentRevisionRowSettlementProjector $settlements,
        private readonly EditTransactionWorkspaceEditableLineFilter $editable,
        private readonly NoteRevisionWorkspaceExistingItemMapper $items,
    ) {}

    /** @return array{payload:array<string,mixed>,target_index:int} */
    public function build(Note $note, WorkItem $target, ServiceDetail $detail, string $reason): array
    {
        $revision = $this->current->resolveOrFail($note->id());
        $lines = $this->editable->filter($revision->lines(), $this->settlements->build($note->id(), $revision->lines()));
        $items = $this->items->mapLines($lines);
        $targetIndex = null;
        foreach ($lines as $index => $line) {
            if ($line->workItemRootId() === $target->id()) {
                $targetIndex = $index;
            }
        }
        if ($targetIndex === null) {
            throw new DomainException('Target correction bukan line aktif yang dapat diedit.');
        }
        $items[$targetIndex]['service']['name'] = $detail->serviceName();
        $items[$targetIndex]['service']['price_rupiah'] = $detail->servicePriceRupiah()->amount();
        $items[$targetIndex]['part_source'] = $detail->partSource();

        return [
            'target_index' => $targetIndex,
            'payload' => [
                'reason' => $reason,
                'note' => [
                    'customer_name' => $note->customerName(),
                    'customer_phone' => $note->customerPhone(),
                    'transaction_date' => $note->transactionDate()->format('Y-m-d'),
                    'operational_note' => $note->operationalNote(),
                ],
                'items' => $items,
                'inline_payment' => ['decision' => 'skip'],
            ],
        ];
    }
}
