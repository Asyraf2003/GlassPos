<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Admin\EmployeeDebt;

use App\Adapters\In\Http\Requests\EmployeeFinance\RecordEmployeeDebtRequest;
use App\Application\EmployeeFinance\UseCases\RecordEmployeeDebtHandler;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Throwable;

final class StoreEmployeeDebtController extends Controller
{
    public function __invoke(
        RecordEmployeeDebtRequest $request,
        RecordEmployeeDebtHandler $useCase,
    ): RedirectResponse {
        $data = $request->validated();
        $actorId = $request->user()?->getAuthIdentifier();

        try {
            $useCase->handle(
                (string) $data['employee_id'],
                (int) $data['debt_amount'],
                isset($data['notes']) && $data['notes'] !== '' ? (string) $data['notes'] : null,
                $actorId !== null ? (string) $actorId : null,
            );
        } catch (Throwable $e) {
            $message = trim($e->getMessage()) !== '' ? $e->getMessage() : 'Data hutang karyawan gagal dibuat.';

            return back()
                ->withErrors(['employee_debt' => $message])
                ->withInput();
        }

        return redirect()
            ->route('admin.employee-debts.index')
            ->with('success', 'Data hutang karyawan berhasil dibuat.');
    }
}
