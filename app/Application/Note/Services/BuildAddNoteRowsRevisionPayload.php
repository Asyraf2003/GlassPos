<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Application\Note\Services\CurrentRevision\CurrentRevisionRowSettlementProjector;
use App\Core\Shared\Exceptions\DomainException;
use App\Ports\Out\Note\NoteRevisionReaderPort;
use App\Ports\Out\Note\NoteReaderPort;

final class BuildAddNoteRowsRevisionPayload
{
    public function __construct(
        private readonly NoteRevisionReaderPort $revisions,
        private readonly NoteReaderPort $notes,
        private readonly NoteRevisionWorkspaceExistingItemMapper $items,
        private readonly CurrentRevisionRowSettlementProjector $settlements,
        private readonly EditTransactionWorkspaceEditableLineFilter $editable,
        private readonly NoteProductSaleOnlyLineTotalResolver $prices,
    ) {}

    public function build(string $noteId, string $baseRevisionId, array $rows): array
    {
        $base = $this->revisions->findById($baseRevisionId);
        if ($base === null || $base->noteRootId() !== $noteId) {
            throw new DomainException('STALE_REVISION: Nota telah berubah. Muat ulang editor sebelum menyimpan.');
        }
        $lines = $this->editable->filter($base->lines(), $this->settlements->build($noteId, $base->lines()));
        $items = $this->items->mapLines($lines);
        foreach ($rows as $row) {
            if ($row['line_type'] === 'product') {
                $qty = (int) $row['qty'];
                $total = $this->prices->resolve((string) $row['product_id'], $qty)
                    ?? throw new DomainException('Produk pada baris nota tidak ditemukan.');
                $items[] = ['entry_mode' => 'product', 'product_lines' => [[
                    'product_id' => $row['product_id'], 'qty' => $qty, 'unit_price_rupiah' => intdiv($total, $qty),
                ]]];
            } else {
                $items[] = ['entry_mode' => 'service', 'part_source' => 'none', 'service' => [
                    'name' => $row['service_name'], 'price_rupiah' => (int) $row['service_price_rupiah'],
                ]];
            }
        }
        return ['base_revision_id' => $baseRevisionId, 'reason' => 'Tambah rincian nota',
            'note' => ['customer_name' => $base->customerName(), 'customer_phone' => $base->customerPhone(),
                'transaction_date' => $base->transactionDate()->format('Y-m-d'),
                'operational_note' => $this->notes->getById($noteId)?->operationalNote()],
            'items' => $items, 'inline_payment' => ['decision' => 'skip']];
    }
}
