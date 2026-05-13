<?php

declare(strict_types=1);

namespace App\Adapters\Out\Note;

use App\Application\Note\DTO\NoteRevisionSettlement;
use App\Application\Note\DTO\NoteRevisionSurplusDisposition;
use App\Application\Note\DTO\NoteRevisionSurplusPending;
use App\Ports\Out\Note\NoteRevisionSurplusDispositionReaderPort;
use App\Ports\Out\Note\NoteRevisionSurplusDispositionWriterPort;
use Illuminate\Support\Facades\DB;

final class DatabaseNoteRevisionSurplusDispositionAdapter implements
    NoteRevisionSurplusDispositionReaderPort,
    NoteRevisionSurplusDispositionWriterPort
{
    public function create(NoteRevisionSurplusDisposition $disposition): void
    {
        DB::table('note_revision_surplus_dispositions')->insert([
            'id' => $disposition->id,
            'note_revision_settlement_id' => $disposition->noteRevisionSettlementId,
            'note_root_id' => $disposition->noteRootId,
            'note_revision_id' => $disposition->noteRevisionId,
            'disposition_type' => $disposition->dispositionType,
            'amount_rupiah' => $disposition->amountRupiah,
            'before_pending_rupiah' => $disposition->beforePendingRupiah,
            'after_pending_rupiah' => $disposition->afterPendingRupiah,
            'status' => $disposition->status,
            'occurred_at' => $disposition->occurredAt->format('Y-m-d H:i:s'),
            'created_at' => $disposition->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => null,
            'audit_event_id' => $disposition->auditEventId,
        ]);
    }

    public function findPendingBySettlementId(string $settlementId): ?NoteRevisionSurplusPending
    {
        $settlementId = trim($settlementId);

        if ($settlementId === '') {
            return null;
        }

        $settlement = DB::table('note_revision_settlements')
            ->where('id', $settlementId)
            ->where('settlement_status', NoteRevisionSettlement::STATUS_OVERPAID_PENDING)
            ->first();

        if ($settlement === null) {
            return null;
        }

        $activeDispositionRupiah = (int) DB::table('note_revision_surplus_dispositions')
            ->where('note_revision_settlement_id', $settlementId)
            ->where('status', NoteRevisionSurplusDisposition::STATUS_ACTIVE)
            ->sum('amount_rupiah');

        return NoteRevisionSurplusPending::create(
            (string) $settlement->id,
            (string) $settlement->note_root_id,
            (string) $settlement->note_revision_id,
            (int) $settlement->surplus_rupiah,
            $activeDispositionRupiah,
        );
    }
}
