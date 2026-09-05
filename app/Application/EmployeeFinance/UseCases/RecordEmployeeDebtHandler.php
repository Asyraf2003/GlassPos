<?php

declare(strict_types=1);

namespace App\Application\EmployeeFinance\UseCases;

use App\Core\EmployeeFinance\EmployeeDebt\EmployeeDebt;
use App\Core\Shared\ValueObjects\Money;
use App\Ports\Out\AuditLogPort;
use App\Ports\Out\EmployeeFinance\EmployeeDebtWriterPort;
use App\Ports\Out\EmployeeFinance\EmployeeReaderPort;
use App\Ports\Out\TransactionManagerPort;
use App\Ports\Out\UuidPort;
use InvalidArgumentException;
use Throwable;

class RecordEmployeeDebtHandler
{
    public function __construct(
        private EmployeeReaderPort $employeeReader,
        private EmployeeDebtWriterPort $debtWriter,
        private UuidPort $uuidPort,
        private TransactionManagerPort $transactionManager,
        private AuditLogPort $auditLog,
    ) {
    }

    public function handle(
        string $employeeId,
        int $debtAmount,
        ?string $notes = null,
        ?string $performedByActorId = null,
    ): string {
        $this->transactionManager->begin();

        try {
            $employee = $this->employeeReader->findById($employeeId);

            if (! $employee) {
                throw new InvalidArgumentException('Karyawan tidak ditemukan.');
            }

            $id = $this->uuidPort->generate();
            $debt = EmployeeDebt::record($id, $employeeId, Money::fromInt($debtAmount), $notes);
            $this->debtWriter->save($debt);
            $this->auditLog->record('employee_debt_recorded', [
                'employee_debt_id' => $id,
                'employee_id' => $employeeId,
                'total_debt' => $debtAmount,
                'notes' => $notes,
                'performed_by_actor_id' => $performedByActorId,
            ]);
            $this->transactionManager->commit();

            return $id;
        } catch (Throwable $e) {
            $this->transactionManager->rollBack();
            throw $e;
        }
    }
}
