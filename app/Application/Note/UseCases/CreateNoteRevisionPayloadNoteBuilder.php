<?php

declare(strict_types=1);

namespace App\Application\Note\UseCases;

use App\Application\Note\Services\CreateTransactionWorkspaceWorkItemPayloadMapper;
use App\Application\Note\Services\RevisionWorkspace\RevisionSnapshotStoreStockLineTrustMarker;
use App\Application\Note\Services\WorkItemFactory;
use App\Core\Note\Note\Note;
use App\Core\Note\Revision\NoteRevision;
use App\Core\Note\WorkItem\WorkItem;
use App\Core\Shared\Exceptions\DomainException;
use App\Core\Shared\ValueObjects\Money;

final class CreateNoteRevisionPayloadNoteBuilder
{
    public function __construct(
        private readonly CreateTransactionWorkspaceWorkItemPayloadMapper $mapper,
        private readonly WorkItemFactory $factory,
        private readonly RevisionSnapshotStoreStockLineTrustMarker $snapshotTrust,
    ) {
    }

    /**
     * @param array{
     *   note: array<string, mixed>,
     *   items: list<array<string, mixed>>,
     *   reason?: string,
     *   inline_payment?: array<string, mixed>
     * } $payload
     */
    public function build(string $noteRootId, array $payload, ?NoteRevision $currentRevision = null): Note
    {
        $noteData = (array) ($payload['note'] ?? []);
        $items = $this->snapshotTrust->mark(
            array_values((array) ($payload['items'] ?? [])),
            $currentRevision,
        );
        $workItems = $this->buildWorkItems($noteRootId, $items);

        if ($workItems === []) {
            throw new DomainException('Minimal satu item valid wajib ada untuk membuat revisi.');
        }

        $total = array_reduce(
            $workItems,
            fn (int $carry, WorkItem $item): int => $carry + $item->subtotalRupiah()->amount(),
            0,
        );

        return Note::rehydrate(
            $noteRootId,
            (string) ($noteData['customer_name'] ?? ''),
            isset($noteData['customer_phone']) ? (string) $noteData['customer_phone'] : null,
            new \DateTimeImmutable((string) ($noteData['transaction_date'] ?? '')),
            Money::fromInt($total),
            $workItems,
            Note::STATE_OPEN,
        );
    }

    /**
     * @param list<array<string, mixed>> $itemsData
     * @return list<WorkItem>
     */
    private function buildWorkItems(string $noteRootId, array $itemsData): array
    {
        $workItems = [];
        $lineNo = 1;

        foreach ($itemsData as $item) {
            if (! is_array($item)) {
                continue;
            }

            [$type, $service, $external, $store] = $this->mapper->map($item);

            $workItems[] = $this->factory->build(
                $noteRootId,
                $lineNo,
                $type,
                $service,
                $external,
                $store,
            );

            $lineNo++;
        }

        return $workItems;
    }
}
