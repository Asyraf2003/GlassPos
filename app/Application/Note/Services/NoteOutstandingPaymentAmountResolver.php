<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Application\Note\Services\Concerns\ResolvesNoteOperationalCurrentRevisionSettlement;
use App\Application\Note\Services\CurrentRevision\CurrentRevisionRowSettlementProjector;
use App\Application\Shared\DTO\Result;
use App\Ports\Out\Note\NoteReaderPort;

final class NoteOutstandingPaymentAmountResolver
{
    use ResolvesNoteOperationalCurrentRevisionSettlement;

    public function __construct(
        private readonly NoteReaderPort $notes,
        private readonly NoteLegacyPaymentSettlementReader $legacySettlement,
        private readonly ?NoteCurrentRevisionResolver $currentRevision = null,
        private readonly ?CurrentRevisionRowSettlementProjector $currentRevisionSettlements = null,
    ) {}

    public function resolveFull(string $noteId): Result
    {
        $note = $this->notes->getById(trim($noteId));
        if ($note === null) {
            return Result::failure('Nota tidak ditemukan.', ['payment' => ['PAYMENT_INVALID_TARGET']]);
        }

        $settlement = $this->currentRevisionSettlement($note) ?? $this->legacySettlement->resolve($note);
        $outstanding = $settlement['outstanding_rupiah'];
        if ($outstanding <= 0) {
            return Result::failure('Nota sudah lunas.', ['payment' => ['PAYMENT_ALREADY_PAID']]);
        }

        return Result::success([
            'amount_rupiah' => $outstanding,
            'grand_total_rupiah' => $settlement['gross_total_rupiah'],
            'net_paid_rupiah' => $settlement['net_paid_rupiah'],
            'outstanding_rupiah' => $outstanding,
            'explanation' => $this->explanation(
                $settlement['gross_total_rupiah'],
                $settlement['net_paid_rupiah'],
                $outstanding,
            ),
        ]);
    }

    public function resolvePartial(string $noteId, int $amountRupiah): Result
    {
        $full = $this->resolveFull($noteId);
        if ($full->isFailure()) {
            return $full;
        }
        if ($amountRupiah <= 0) {
            return Result::failure('Nominal pembayaran sebagian harus lebih dari 0.', ['payment' => ['INVALID_PARTIAL_AMOUNT']]);
        }

        $outstanding = (int) ($full->data()['outstanding_rupiah'] ?? 0);
        if ($amountRupiah >= $outstanding) {
            return Result::failure('Nominal pembayaran sebagian harus lebih kecil dari sisa tagihan.', ['payment' => ['INVALID_PARTIAL_AMOUNT']]);
        }

        return Result::success([
            'amount_rupiah' => $amountRupiah,
            'grand_total_rupiah' => (int) ($full->data()['grand_total_rupiah'] ?? 0),
            'net_paid_rupiah' => (int) ($full->data()['net_paid_rupiah'] ?? 0),
            'outstanding_rupiah' => $outstanding,
            'explanation' => $full->data()['explanation'] ?? [],
        ]);
    }

    private function explanation(int $grandTotal, int $netPaid, int $outstanding): array
    {
        return [
            'basis' => 'backend_outstanding_settlement',
            'gross_total_rupiah' => $grandTotal,
            'net_paid_rupiah' => $netPaid,
            'outstanding_rupiah' => $outstanding,
        ];
    }
}
