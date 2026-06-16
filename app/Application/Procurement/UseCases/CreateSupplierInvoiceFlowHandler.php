<?php

declare(strict_types=1);

namespace App\Application\Procurement\UseCases;

use App\Application\Procurement\Context\SupplierInvoiceChangeContext;
use App\Application\Procurement\Services\SupplierInvoiceListProjectionService;
use App\Application\Shared\DTO\Result;
use App\Core\Shared\Exceptions\DomainException;
use App\Ports\Out\TransactionManagerPort;
use Throwable;

final class CreateSupplierInvoiceFlowHandler
{
    public function __construct(
        private readonly TransactionManagerPort $transactions,
        private readonly CreateSupplierInvoiceFlowOperation $operation,
        private readonly CreateSupplierInvoiceFlowQueryExceptionClassifier $queryErrors,
        private readonly SupplierInvoiceChangeContext $changeContext,
        private readonly SupplierInvoiceListProjectionService $projection,
    ) {
    }

    public function handle(
        string $nomorFaktur,
        string $pt,
        string $tglKirim,
        array $lines,
        bool $autoRec = true,
        ?string $tglTerima = null,
        ?string $performedByActorId = null,
        ?string $performedByActorRole = null,
        string $sourceChannel = 'http',
        null|string|int $taxInput = null,
    ): Result {
        $started = false;

        try {
            $this->transactions->begin();
            $started = true;

            $this->changeContext->set(
                $performedByActorId,
                $performedByActorRole,
                $sourceChannel,
                'supplier_invoice_flow_completed',
            );

            $invoice = $this->operation->execute(
                $nomorFaktur,
                $pt,
                $tglKirim,
                $lines,
                $taxInput,
                $autoRec,
                $tglTerima,
            );

            $this->projection->syncInvoice($invoice->id());

            $this->transactions->commit();

            return Result::success(['id' => $invoice->id()], 'Flow Supplier Invoice Berhasil.');
        } catch (DomainException $e) {
            if ($started) {
                $this->transactions->rollBack();
            }

            return Result::failure($e->getMessage(), ['supplier_invoice' => ['INVALID_SUPPLIER_INVOICE']]);
        } catch (Throwable $e) {
            if ($started) {
                $this->transactions->rollBack();
            }

            $failure = $this->queryErrors->classify($e);

            if ($failure !== null) {
                return $failure;
            }

            throw $e;
        } finally {
            $this->changeContext->clear();
        }
    }
}
