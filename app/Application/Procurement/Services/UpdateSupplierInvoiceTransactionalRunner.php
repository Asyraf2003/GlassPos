<?php

declare(strict_types=1);

namespace App\Application\Procurement\Services;

use App\Application\Procurement\Context\SupplierInvoiceChangeContext;
use App\Application\Shared\DTO\Result;
use App\Core\Shared\Exceptions\DomainException;
use App\Ports\Out\TransactionManagerPort;
use Throwable;

final class UpdateSupplierInvoiceTransactionalRunner
{
    public function __construct(
        private readonly TransactionManagerPort $transactions,
        private readonly SupplierInvoiceChangeContext $changeContext,
        private readonly SupplierInvoiceQueryExceptionClassifier $queryErrors,
    ) {
    }

    public function run(
        callable $callback,
        ?string $performedByActorId,
        ?string $performedByActorRole,
        string $sourceChannel,
        ?string $changeReason = null,
    ): Result {
        $started = false;

        try {
            $this->transactions->begin();
            $started = true;

            $this->changeContext->set(
                $performedByActorId,
                $performedByActorRole,
                $sourceChannel,
                $changeReason,
            );

            $result = $callback();

            if ($result->isFailure()) {
                $this->transactions->rollBack();

                return $result;
            }

            $this->transactions->commit();

            return $result;
        } catch (DomainException $e) {
            if ($started) {
                $this->transactions->rollBack();
            }

            return Result::failure(
                $e->getMessage(),
                ['supplier_invoice' => ['INVALID_SUPPLIER_INVOICE']]
            );
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
