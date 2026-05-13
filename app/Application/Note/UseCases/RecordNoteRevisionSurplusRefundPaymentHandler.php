<?php

declare(strict_types=1);

namespace App\Application\Note\UseCases;

use App\Core\Shared\Exceptions\DomainException;
use App\Ports\Out\AuditEventWriterPort;
use App\Ports\Out\Note\NoteRevisionSurplusRefundDueSourceReaderPort;
use App\Ports\Out\Note\NoteRevisionSurplusRefundPaymentReaderPort;
use App\Ports\Out\Note\NoteRevisionSurplusRefundPaymentWriterPort;
use App\Ports\Out\TransactionManagerPort;
use Throwable;

final class RecordNoteRevisionSurplusRefundPaymentHandler
{
    public function __construct(
        private readonly NoteRevisionSurplusRefundDueSourceReaderPort $sourceReader,
        private readonly NoteRevisionSurplusRefundPaymentReaderPort $paymentReader,
        private readonly NoteRevisionSurplusRefundPaymentWriterPort $paymentWriter,
        private readonly AuditEventWriterPort $auditWriter,
        private readonly TransactionManagerPort $transactions,
        private readonly RecordNoteRevisionSurplusRefundPaymentGuard $guard,
        private readonly RecordNoteRevisionSurplusRefundPaymentFactory $paymentFactory,
        private readonly RecordNoteRevisionSurplusRefundPaymentAuditEventFactory $auditFactory,
        private readonly RecordNoteRevisionSurplusRefundPaymentResultFactory $resultFactory,
    ) {
    }

    public function handle(
        RecordNoteRevisionSurplusRefundPaymentCommand $command,
    ): RecordNoteRevisionSurplusRefundPaymentResult {
        $started = false;

        try {
            $this->transactions->begin();
            $started = true;

            $reason = $this->guard->assertCommandAllowed($command);
            $source = $this->guard->sourceOrFail(
                $this->sourceReader->findActiveRefundDueByDispositionIdForUpdate(
                    $command->noteRevisionSurplusDispositionId,
                ),
            );

            $existing = $this->paymentReader->findActiveByDispositionIdAndIdempotencyKey(
                $source->dispositionId,
                $command->idempotencyKey,
            );

            if ($existing !== null) {
                $this->guard->assertRepeatedPayloadMatches($command, $existing);
                $this->transactions->commit();

                return $this->resultFactory->success($existing, $source->remainingRefundDueRupiah);
            }

            $this->guard->assertAmountFits($command->amountRupiah, $source);

            $payment = $this->paymentFactory->create($command, $source);

            $this->auditWriter->write($this->auditFactory->create(
                $payment,
                $source,
                $command->actorId,
                $command->actorRole,
                $reason,
                $command->sourceChannel,
                $command->requestId,
                $command->correlationId,
            ));

            $this->paymentWriter->create($payment);
            $this->transactions->commit();

            return $this->resultFactory->success(
                $payment,
                $source->remainingRefundDueRupiah - $payment->amountRupiah,
            );
        } catch (DomainException $e) {
            $this->rollBackIfStarted($started);

            return RecordNoteRevisionSurplusRefundPaymentResult::failure($e->getMessage());
        } catch (Throwable $e) {
            $this->rollBackIfStarted($started);

            throw $e;
        }
    }

    private function rollBackIfStarted(bool $started): void
    {
        if ($started) {
            $this->transactions->rollBack();
        }
    }
}
