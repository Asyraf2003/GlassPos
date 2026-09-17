<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Application\Note\Policies\NotePaidStatusPolicy;
use App\Application\Note\UseCases\CorrectPaidServiceOnlySupportTrait;
use App\Application\Note\UseCases\CreateNoteRevisionWorkflow;
use App\Core\Note\WorkItem\ServiceDetail;
use App\Core\Note\WorkItem\WorkItem;
use App\Core\Shared\Exceptions\DomainException;
use App\Core\Shared\ValueObjects\Money;
use App\Ports\Out\Note\NoteReaderPort;
use App\Ports\Out\Note\NoteRevisionSettlementReaderPort;

final class CorrectPaidServiceOnlyWorkItemMutation
{
    use CorrectPaidServiceOnlySupportTrait;

    public function __construct(
        private readonly NoteReaderPort $notes,
        private readonly NotePaidStatusPolicy $paidStatus,
        private readonly NoteCorrectionSnapshotBuilder $snapshots,
        private readonly EnsureInitialNoteRevisionExists $bootstrap,
        private readonly BuildPaidServiceCorrectionRevisionPayload $payloads,
        private readonly CreateNoteRevisionWorkflow $revisions,
        private readonly NoteCurrentRevisionResolver $current,
        private readonly NoteRevisionSettlementReaderPort $settlements,
    ) {}

    /** @return array<string, mixed> */
    public function apply(
        string $noteId,
        int $lineNo,
        string $serviceName,
        int $servicePriceRupiah,
        string $partSource,
        string $reason,
        string $actorId,
        string $baseRevisionId = '',
    ): array {
        $note = $this->notes->getByIdForUpdate(trim($noteId)) ?? throw new DomainException('Note tidak ditemukan.');
        if ($baseRevisionId === '' || $this->current->resolveOrFail($note->id())->id() !== $baseRevisionId) {
            throw new DomainException('STALE_REVISION: Nota telah berubah. Muat ulang editor sebelum menyimpan.');
        }
        $this->paidStatus->assertPaidForCorrection($note);
        $target = $this->findWorkItem($note, $lineNo);
        if ($target->transactionType() !== WorkItem::TYPE_SERVICE_ONLY) {
            throw new DomainException('Correction nominal slice ini hanya mendukung work item service_only.');
        }
        $detail = ServiceDetail::create($serviceName, Money::fromInt($servicePriceRupiah), $partSource);
        $before = $this->snapshots->build($note);
        $this->bootstrap->handle($note->id(), $note->id().'-r001', $actorId);
        $draft = $this->payloads->build($note, $target, $detail, $reason);
        $draft['payload']['base_revision_id'] = $baseRevisionId;
        // The correction transaction owns atomicity; reuse the revision workflow and its ledgers.
        $result = $this->revisions->execute($note->id(), $draft['payload'], $actorId, false);
        if ($result->isFailure()) {
            throw new DomainException($result->message() ?? 'Revision correction gagal.');
        }
        $afterNote = $this->notes->getById($note->id()) ?? throw new DomainException('Note tidak ditemukan setelah correction.');
        $revision = $this->current->resolveOrFail($note->id());
        $targetId = $revision->lines()[$draft['target_index']]->workItemRootId();
        $corrected = null;
        foreach ($afterNote->workItems() as $item) {
            if ($item->id() === $targetId) {
                $corrected = $item;
            }
        }
        if ($corrected === null) {
            throw new DomainException('Target replacement correction tidak ditemukan.');
        }
        $settlement = $this->settlements->findByRevisionId($revision->id())
            ?? throw new DomainException('Settlement revision correction tidak ditemukan.');

        return [
            'note' => $note, 'before' => $before, 'after_note' => $afterNote,
            'after' => $this->snapshots->build($afterNote), 'corrected' => $corrected,
            'refund_required_rupiah' => $settlement->surplusRupiah,
        ];
    }
}
