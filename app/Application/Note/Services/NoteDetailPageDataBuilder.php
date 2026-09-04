<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Ports\Out\Note\NoteReaderPort;

final class NoteDetailPageDataBuilder
{
    public function __construct(
        private readonly NoteReaderPort $notes,
        private readonly NoteDetailOperationalPayloadBuilder $operationals,
        private readonly NotePaymentStatusResolver $paymentStatuses,
        private readonly NoteRefundPaymentOptionsBuilder $refundPaymentOptions,
        private readonly NoteProductOptionsBuilder $products,
        private readonly NoteCorrectionHistoryBuilder $history,
        private readonly NoteWorkspacePanelDataBuilder $workspacePanel,
        private readonly NoteBillingProjectionBuilder $billingProjection,
        private readonly NoteDetailRevisionViewDataBuilder $revisionView,
        private readonly NoteRevisionSurplusDispositionActionViewDataBuilder $surplusDispositions,
        private readonly NoteSurplusDispositionAuditTimelineBuilder $surplusDispositionAuditTimeline,
        private readonly NotePaymentTimelineBuilder $paymentTimeline,
        private readonly NoteDetailNotePayloadBuilder $notePayloads,
    ) {}

    public function build(string $noteId): ?array
    {
        $note = $this->notes->getById(trim($noteId));
        if ($note === null) {
            return null;
        }

        $workspacePanel = $this->workspacePanel->build($noteId);
        if ($workspacePanel === null) {
            return null;
        }

        $operational = $this->operationals->build((array) ($workspacePanel['note_totals'] ?? []));
        $billingRows = $this->billingProjection->buildFromWorkspaceRows($workspacePanel['rows']);
        $history = $this->history->build($note->id());
        $refundOptions = $this->refundPaymentOptions->build($note->id());
        $revisionView = $this->revisionView->build($note);
        $surplusDisposition = $this->surplusDispositions->build($note->id());
        $surplusDispositionAuditTimeline = $this->surplusDispositionAuditTimeline->build($note->id());
        $paymentTimeline = $this->paymentTimeline->build(
            $note->id(),
            (int) $operational['grand_total_rupiah'],
        );

        $refundRows = array_values(array_filter(
            $workspacePanel['rows'],
            static fn (array $row): bool => (bool) ($row['can_refund'] ?? false)
        ));

        $hasOutstanding = count(array_filter(
            $billingRows,
            static fn (array $row): bool => (int) ($row['outstanding_rupiah'] ?? 0) > 0
        )) > 0;

        return [
            'pageTitle' => 'Detail Nota',
            'workspace_panel' => $workspacePanel,
            'note' => $this->notePayloads->build(
                [
                    'id' => $note->id(),
                    'customer_name' => $revisionView['customer_name'],
                    'customer_phone' => $revisionView['customer_phone'],
                    'transaction_date' => $revisionView['transaction_date'],
                    'operational_note' => $note->operationalNote(),
                    'note_state' => $note->noteState(),
                    'payment_status' => $this->paymentStatuses->resolve(
                        (int) $operational['grand_total_rupiah'],
                        (int) $operational['net_paid_rupiah'],
                    ),
                ],
                $workspacePanel,
                $operational,
                $refundOptions,
                $refundRows,
                $billingRows,
                $revisionView['revision_timeline'],
                $surplusDisposition,
                $surplusDispositionAuditTimeline,
                $paymentTimeline,
                $history,
                $note->isOpen(),
                $note->isClosed(),
                $note->isRefunded(),
                $hasOutstanding
            ),
            'productOptions' => $this->products->build(),
        ];
    }
}
