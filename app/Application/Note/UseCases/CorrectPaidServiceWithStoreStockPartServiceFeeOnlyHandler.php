<?php

declare(strict_types=1);

namespace App\Application\Note\UseCases;

use App\Application\Note\Policies\NotePaidStatusPolicy;
use App\Application\Note\Services\NoteCorrectionSnapshotBuilder;
use App\Application\Note\Services\PersistNoteMutationTimeline;
use App\Application\Note\Services\StoreStockLinesSubtotal;
use App\Application\Shared\DTO\Result;
use App\Core\Note\WorkItem\ServiceDetail;
use App\Core\Note\WorkItem\WorkItem;
use App\Core\Shared\Exceptions\DomainException;
use App\Core\Shared\ValueObjects\Money;
use App\Ports\Out\AuditLogPort;
use App\Ports\Out\ClockPort;
use App\Ports\Out\Note\NoteReaderPort;
use App\Ports\Out\Note\NoteWriterPort;
use App\Ports\Out\Note\WorkItemWriterPort;
use App\Ports\Out\Payment\CustomerRefundReaderPort;
use App\Ports\Out\Payment\PaymentAllocationReaderPort;
use App\Ports\Out\TransactionManagerPort;
use Throwable;

final class CorrectPaidServiceWithStoreStockPartServiceFeeOnlyHandler
{
    use CorrectPaidServiceOnlySupportTrait;

    public function __construct(
        private readonly NoteReaderPort $notes,
        private readonly WorkItemWriterPort $workItems,
        private readonly NoteWriterPort $noteWriter,
        private readonly TransactionManagerPort $transactions,
        private readonly NotePaidStatusPolicy $paidStatus,
        private readonly NoteCorrectionSnapshotBuilder $snapshots,
        private readonly PersistNoteMutationTimeline $timeline,
        private readonly PaymentAllocationReaderPort $allocations,
        private readonly CustomerRefundReaderPort $refunds,
        private readonly ClockPort $clock,
        private readonly AuditLogPort $audit,
    ) {
    }

    public function handle(string $noteId, int $lineNo, string $serviceName, int $servicePriceRupiah, string $partSource, string $reason, string $performedByActorId): Result
    {
        $started = false;

        try {
            if ($lineNo <= 0) throw new DomainException('Line number harus > 0.');
            if (trim($reason) === '') return Result::failure('Alasan correction wajib diisi.', ['correction' => ['AUDIT_REASON_REQUIRED']]);
            if (trim($performedByActorId) === '') throw new DomainException('Actor correction wajib ada.');

            $this->transactions->begin();
            $started = true;

            $note = $this->notes->getById(trim($noteId)) ?? throw new DomainException('Note tidak ditemukan.');
            $this->paidStatus->assertPaidForCorrection($note);

            $target = $this->findWorkItem($note, $lineNo);
            if ($target->transactionType() !== WorkItem::TYPE_SERVICE_WITH_STORE_STOCK_PART) throw new DomainException('Correction slice ini hanya mendukung work item service_with_store_stock_part.');

            $before = $this->snapshots->build($note);
            $currentServiceDetail = $target->serviceDetail();
            $packageBaseServicePrice = $currentServiceDetail?->packageBaseServicePriceRupiah();

            if ($packageBaseServicePrice !== null && $servicePriceRupiah < $packageBaseServicePrice->amount()) {
                throw new DomainException('Harga jasa package hasil correction tidak boleh lebih kecil dari base/default service price.');
            }

            $detail = ServiceDetail::create($serviceName, Money::fromInt($servicePriceRupiah), $partSource);
            $subtotal = $detail->servicePriceRupiah()->add(StoreStockLinesSubtotal::sum($target->storeStockLines()));
            $corrected = WorkItem::rehydrate($target->id(), $target->noteId(), $target->lineNo(), $target->transactionType(), $target->status(), $subtotal, $detail, [], $target->storeStockLines());
            $newTotal = $note->totalRupiah()->subtract($target->subtotalRupiah())->add($corrected->subtotalRupiah());
            $newTotal->ensureNotNegative('Total note hasil correction tidak boleh negatif.');

            $note->syncTotalRupiah($newTotal);
            $this->workItems->updateServiceWithStoreStockPartServiceFeeOnly($corrected);
            $this->noteWriter->updateTotal($note);

            $afterNote = $this->notes->getById($note->id()) ?? throw new DomainException('Note tidak ditemukan setelah correction.');
            $after = $this->snapshots->build($afterNote);
            $refundReq = $this->calculateRefundRequired($this->allocations, $this->refunds, $note->id(), $afterNote->totalRupiah());

            $this->timeline->record($note->id(), 'paid_service_with_store_stock_part_service_fee_only_corrected', $performedByActorId, 'admin', $reason, $this->clock->now(), $before, $after, null, null, ['refund_required_rupiah' => $refundReq]);
            $this->audit->record('paid_service_with_store_stock_part_service_fee_only_corrected', $this->formatAuditPayload($performedByActorId, $note->id(), $lineNo, $reason, $refundReq, $before, $after));

            $this->transactions->commit();
            return Result::success($this->formatSuccessPayload($afterNote, $corrected, $refundReq), 'Correction service_with_store_stock_part service fee only berhasil disimpan.');
        } catch (DomainException $e) {
            if ($started) $this->transactions->rollBack();
            return Result::failure($e->getMessage(), ['work_item' => ['INVALID_WORK_ITEM_STATE']]);
        } catch (Throwable $e) {
            if ($started) $this->transactions->rollBack();
            throw $e;
        }
    }
}
