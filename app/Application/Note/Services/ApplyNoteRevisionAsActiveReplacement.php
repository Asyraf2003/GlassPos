<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Application\Note\Services\RevisionWorkspace\RevisionSnapshotStoreStockLineTrustMarker;
use App\Core\Note\Note\Note;
use App\Core\Note\Revision\NoteRevision;
use App\Ports\Out\Note\NoteWriterPort;

final class ApplyNoteRevisionAsActiveReplacement
{
    public function __construct(
        private readonly NoteWriterPort $notes,
        private readonly UpdateTransactionWorkspaceWorkItemPersister $workItems,
        private readonly NoteReplacementPaymentAllocationReconciler $payments,
        private readonly RevisionSnapshotStoreStockLineTrustMarker $snapshotTrust,
    ) {}

    public function apply(Note $root, Note $replacement, mixed $items, ?NoteRevision $trustedSnapshot = null): void
    {
        $paymentAmounts = $this->payments->captureAllocatedAmounts($root->id());

        $root->updateHeader(
            $replacement->customerName(),
            $replacement->customerPhone(),
            $replacement->transactionDate(),
            $replacement->operationalNote(),
        );

        $trustedItems = $this->snapshotTrust->mark(
            is_array($items) ? array_values($items) : [],
            $trustedSnapshot,
            $root->workItems(),
        );

        $this->notes->updateHeader($root);
        $this->payments->deleteExisting($root->id());
        $this->workItems->persist($root, $trustedItems, $root->transactionDate());
        $this->notes->updateTotal($root);
        $this->payments->rebuild($root, $paymentAmounts);
    }
}
