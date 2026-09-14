<?php

declare(strict_types=1);

namespace App\Application\Note\Services\Concerns;

use App\Core\Note\Note\Note;

trait ResolvesNoteOperationalCurrentRevisionSettlement
{
    /** @return array{gross_total_rupiah:int,net_paid_rupiah:int,outstanding_rupiah:int}|null */
    private function currentRevisionSettlement(Note $note): ?array
    {
        if ($this->currentRevision === null || $this->currentRevisionSettlements === null) {
            return null;
        }

        if (! $this->currentRevision->hasCurrentRevision($note->id())) {
            return null;
        }

        $revision = $this->currentRevision->resolveOrFail($note->id());

        if ($revision->grandTotalRupiah() <= 0) {
            return [
                'gross_total_rupiah' => 0,
                'net_paid_rupiah' => 0,
                'outstanding_rupiah' => 0,
            ];
        }

        $settlements = $this->currentRevisionSettlements->build(
            $revision->noteRootId(),
            $revision->lines(),
        );

        $netPaid = 0;
        $outstanding = 0;

        foreach ($revision->lines() as $line) {
            $key = $line->workItemRootId() ?? $line->id();
            $settlement = $settlements[$key] ?? [];

            $netPaid += (int) ($settlement['net_paid_rupiah'] ?? 0);
            $outstanding += (int) (
                $settlement['outstanding_rupiah']
                ?? $line->subtotalRupiah()
            );
        }

        $outstanding = min($outstanding, max($revision->grandTotalRupiah() - $netPaid, 0));

        return [
            'gross_total_rupiah' => $revision->grandTotalRupiah(),
            'net_paid_rupiah' => $netPaid,
            'outstanding_rupiah' => $outstanding,
        ];
    }
}
