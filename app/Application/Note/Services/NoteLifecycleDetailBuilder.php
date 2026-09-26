<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Core\Shared\Exceptions\DomainException;
use App\Ports\Out\Note\NoteCorrectionHistoryReaderPort;
use App\Ports\Out\Note\NoteReaderPort;
use App\Ports\Out\Note\NoteRevisionReaderPort;

final class NoteLifecycleDetailBuilder
{
    public function __construct(
        private readonly NoteReaderPort $notes,
        private readonly NoteRevisionReaderPort $revisions,
        private readonly NoteCorrectionHistoryReaderPort $history,
        private readonly NoteCancellationEligibility $eligibility,
        private readonly NoteLifecycleViewAccess $access,
    ) {}

    public function build(string $noteId, string $actorId): array
    {
        $view = ['mode' => 'blocked', 'message' => 'Aksi transaksi tidak tersedia untuk akses ini.'];
        $note = $this->notes->getById($noteId);
        if ($note === null || ! $this->access->allowed($note, $actorId)) {
            return $view;
        }
        $revision = $this->revisions->findCurrentByRootId($noteId);
        if ($revision === null) {
            return ['mode' => 'blocked', 'message' => 'Riwayat revisi belum tersedia. Muat ulang transaksi.'];
        }
        $view = ['mode' => 'cancel', 'message' => '', 'base_revision_id' => $revision->id(),
            'source_revision_id' => $revision->id(), 'source_total' => $revision->grandTotalRupiah(),
            'revision_number' => $revision->revisionNumber(), 'stock_units' => 0, 'cancellation_event_id' => ''];
        foreach ($revision->lines() as $line) {
            $view['stock_units'] += array_sum(array_column($line->payload()['store_stock_lines'] ?? [], 'qty'));
        }
        if ($note->isCancelled()) {
            foreach ($revision->lines() as $line) {
                if ($line->transactionType() === 'service_with_external_purchase') {
                    return ['mode' => 'blocked', 'message' => 'Riwayat pembelian luar belum didukung untuk pemulihan.'];
                }
            }
            foreach ($this->history->findLatestNoteCorrections($noteId) as $event) {
                if (($event['mutation_type'] ?? '') === 'note_cancelled'
                    && $this->history->isUnrestoredCancellation($noteId, $event['event_id'])) {
                    return [...$view, 'mode' => 'restore', 'cancellation_event_id' => $event['event_id']];
                }
            }

            return ['mode' => 'blocked', 'message' => 'Riwayat pembatalan belum dapat dipastikan. Pemulihan tidak tersedia.'];
        }
        try {
            $this->eligibility->assertEligible($note);
        } catch (DomainException $e) {
            return ['mode' => $e->getMessage() === 'REFUND_REQUIRED' ? 'refund' : 'blocked', 'message' => match ($e->getMessage()) {
                'REFUND_REQUIRED' => 'Pembayaran sudah tercatat. Lanjutkan melalui Refund: pilih rincian lunas yang ingin dikembalikan pada tabel, lalu Pengembalian Dana Rincian Terpilih. Rincian yang sudah direfund tidak dapat diulang.',
                'EXTERNAL_REFUND_REQUIRED' => 'Pembatalan dan refund pembelian luar belum didukung pada alur ini. Transaksi tidak diubah.',
                default => 'Riwayat penyelesaian belum dapat dipastikan. Pembatalan tidak tersedia.',
            }];
        }

        return $view;
    }
}
