<?php

declare(strict_types=1);

namespace App\Adapters\Out\EmployeeFinance;

use App\Application\EmployeeFinance\DTO\EmployeeDebtTableQuery;
use App\Ports\Out\EmployeeFinance\EmployeeDebtTableReaderPort;
use Illuminate\Support\Facades\DB;

final class DatabaseEmployeeDebtListPageQuery implements EmployeeDebtTableReaderPort
{
    public function __construct(private readonly EmployeeDebtTablePayload $payload = new EmployeeDebtTablePayload) {}

    public function search(EmployeeDebtTableQuery $query): array
    {
        $builder = DB::table('employee_debts')
            ->join('employees', 'employees.id', '=', 'employee_debts.employee_id')
            ->select([
                'employee_debts.employee_id',
                'employees.employee_name as employee_name',
            ])
            ->selectSub(
                DB::table('employee_debts as debt_target')
                    ->select('debt_target.id')
                    ->whereColumn('debt_target.employee_id', 'employee_debts.employee_id')
                    ->orderByRaw("CASE WHEN debt_target.status = 'unpaid' THEN 0 ELSE 1 END")
                    ->orderByDesc('debt_target.created_at')
                    ->orderByDesc('debt_target.id')
                    ->limit(1),
                'debt_detail_id'
            )
            ->selectSub(
                DB::table('employee_debts as latest_unpaid_debts')
                    ->select('latest_unpaid_debts.id')
                    ->whereColumn('latest_unpaid_debts.employee_id', 'employee_debts.employee_id')
                    ->where('latest_unpaid_debts.status', 'unpaid')
                    ->orderByDesc('latest_unpaid_debts.created_at')
                    ->orderByDesc('latest_unpaid_debts.id')
                    ->limit(1),
                'latest_unpaid_debt_id'
            )
            ->selectRaw('COUNT(*) as total_debt_records')
            ->selectRaw('SUM(employee_debts.total_debt) as total_debt_amount')
            ->selectRaw('SUM(employee_debts.remaining_balance) as total_remaining_balance')
            ->selectRaw("SUM(CASE WHEN employee_debts.status = 'unpaid' THEN 1 ELSE 0 END) as active_debt_count")
            ->selectRaw("SUM(CASE WHEN employee_debts.status = 'paid' THEN 1 ELSE 0 END) as paid_debt_count")
            ->selectRaw('MAX(employee_debts.created_at) as latest_recorded_at');

        if ($query->q() !== null) {
            foreach (preg_split('/\s+/', $query->q()) ?: [] as $term) {
                $builder->where('employees.employee_name', 'like', '%'.$term.'%');
            }
        }

        $column = match ($query->sortBy()) {
            'employee_name' => 'employee_name',
            'total_debt_records' => 'total_debt_records',
            'total_debt_amount' => 'total_debt_amount',
            'total_remaining_balance' => 'total_remaining_balance',
            'status' => 'active_debt_count',
            default => 'latest_recorded_at',
        };

        $builder->groupBy('employee_debts.employee_id', 'employees.employee_name');

        if ($query->status() === 'active') {
            $builder->havingRaw("SUM(CASE WHEN employee_debts.status = 'unpaid' THEN 1 ELSE 0 END) > 0");
        }
        if ($query->status() === 'paid') {
            $builder->havingRaw("SUM(CASE WHEN employee_debts.status = 'unpaid' THEN 1 ELSE 0 END) = 0");
        }

        $paginator = $builder
            ->orderBy($column, $query->sortDir())
            ->orderBy('employee_name')
            ->orderBy('employee_debts.employee_id')
            ->paginate($query->perPage(), ['*'], 'page', $query->page());

        return $this->payload->fromPaginator($paginator, $query);
    }
}
