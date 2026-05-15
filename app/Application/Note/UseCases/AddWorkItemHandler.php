<?php

declare(strict_types=1);

namespace App\Application\Note\UseCases;

use App\Application\Inventory\Services\IssueInventoryOperation;
use App\Application\Note\Policies\NoteAddabilityPolicy;
use App\Application\Note\Services\AddWorkItemErrorClassifier;
use App\Application\Note\Services\WorkItemFactory;
use App\Application\Shared\DTO\Result;
use App\Core\Shared\Exceptions\DomainException;
use App\Ports\Out\AuditLogPort;
use App\Ports\Out\Note\{NoteReaderPort, NoteWriterPort, WorkItemWriterPort};
use App\Ports\Out\TransactionManagerPort;
use Throwable;

final class AddWorkItemHandler
{
    public function __construct(
        private NoteReaderPort $notes,
        private NoteWriterPort $noteWriter,
        private WorkItemWriterPort $workItemWriter,
        private IssueInventoryOperation $issueInventory,
        private TransactionManagerPort $transactions,
        private WorkItemFactory $factory,
        private NoteAddabilityPolicy $addability,
        private AddWorkItemErrorClassifier $errors,
        private AuditLogPort $audit
    ) {}

    public function handle(string $nId, int $lNo, string $type, array $sd, array $ext = [], array $sto = []): Result
    {
        $started = false;
        try {
            $this->transactions->begin(); $started = true;
            $note = $this->notes->getByIdForUpdate(trim($nId)) ?? throw new DomainException('Note tidak ditemukan.');

            $this->addability->assertAllowed($note);
            $workItem = $this->factory->build($note->id(), $lNo, trim($type), $sd, $ext, $sto);
            $note->addWorkItem($workItem);

            $this->workItemWriter->create($workItem);
            foreach ($workItem->storeStockLines() as $line) {
                $this->issueInventory->execute($line->productId(), $line->qty(), $note->transactionDate(), 'work_item_store_stock_line', $line->id());
            }
            $this->noteWriter->updateTotal($note);

            $this->audit->record('work_item_added', [
                'note_id' => $note->id(),
                'work_item_id' => $workItem->id(),
                'type' => $workItem->transactionType(),
                'subtotal' => $workItem->subtotalRupiah()->amount()
            ]);

            $this->transactions->commit();
            return Result::success($this->mapResponse($note, $workItem), 'Work item berhasil ditambahkan.');
        } catch (DomainException $e) {
            if ($started) $this->transactions->rollBack();
            return $this->errors->classify($e);
        } catch (Throwable $e) {
            if ($started) $this->transactions->rollBack();
            throw $e;
        }
    }

    private function mapResponse($note, $workItem): array
    {
        return [
            'note' => ['id' => $note->id(), 'total_rupiah' => $note->totalRupiah()->amount()],
            'work_item' => ['id' => $workItem->id(), 'subtotal_rupiah' => $workItem->subtotalRupiah()->amount()]
        ];
    }
}
