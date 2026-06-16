<?php

declare(strict_types=1);

namespace App\Application\Note\UseCases;

use App\Application\Note\Services\CreateTransactionWorkspaceInlinePaymentRecorder;
use App\Application\Note\Services\EditableWorkspaceNoteGuard;
use App\Application\Note\Services\NoteHistoryProjectionService;
use App\Application\Note\Services\UpdateTransactionWorkspaceResultBuilder;
use App\Application\Note\Services\UpdateTransactionWorkspaceWorkItemPersister;
use App\Application\Shared\DTO\Result;
use App\Core\Shared\Exceptions\DomainException;
use App\Ports\Out\AuditLogPort;
use App\Ports\Out\Note\NoteReaderPort;
use App\Ports\Out\Note\NoteWriterPort;
use App\Ports\Out\TransactionManagerPort;
use DateTimeImmutable;
use Throwable;

final class UpdateTransactionWorkspaceHandler
{
    public function __construct(
        private readonly EditableWorkspaceNoteGuard $guard,
        private readonly NoteReaderPort $notes,
        private readonly NoteWriterPort $noteWriter,
        private readonly UpdateTransactionWorkspaceWorkItemPersister $items,
        private readonly CreateTransactionWorkspaceInlinePaymentRecorder $payments,
        private readonly TransactionManagerPort $transactions,
        private readonly AuditLogPort $audit,
        private readonly UpdateTransactionWorkspaceResultBuilder $results,
        private readonly NoteHistoryProjectionService $projection,
    ) {
    }

    /**
     * @param array{
     * note: array<string, mixed>,
     * items: list<array<string, mixed>>,
     * inline_payment: array<string, mixed>
     * } $payload
     */
    public function handle(string $noteId, array $payload): Result
    {
        $started = false;

        try {
            $this->transactions->begin();
            $started = true;

            $this->guard->assertEditable($noteId);
            $note = $this->notes->getById(trim($noteId));

            if ($note === null) {
                return Result::failure('Nota tidak ditemukan.', ['note' => ['NOT_FOUND']]);
            }

            $noteData = (array) ($payload['note'] ?? []);
            $note->updateHeader(
                (string) ($noteData['customer_name'] ?? ''),
                isset($noteData['customer_phone']) ? (string) $noteData['customer_phone'] : null,
                new DateTimeImmutable((string) ($noteData['transaction_date'] ?? '')),
            );

            $this->noteWriter->updateHeader($note);
            $itemsCount = $this->items->persist($note, $payload['items'] ?? [], $note->transactionDate());
            $this->noteWriter->updateTotal($note);

            $paymentSummary = $this->payments->record($note, $payload['inline_payment'] ?? []);

            $this->audit->record(
                'transaction_workspace_updated',
                $this->results->auditPayload($note, $itemsCount, $paymentSummary),
            );

            $this->projection->syncNote($note->id());

            $this->transactions->commit();

            return $this->results->success($note, $paymentSummary);
        } catch (DomainException $e) {
            if ($started) {
                $this->transactions->rollBack();
            }

            return Result::failure($e->getMessage(), ['note' => ['INVALID_WORKSPACE_UPDATE']]);
        } catch (Throwable $e) {
            if ($started) {
                $this->transactions->rollBack();
            }

            throw $e;
        }
    }
}
