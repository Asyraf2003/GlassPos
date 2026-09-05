<?php

declare(strict_types=1);

namespace App\Adapters\Out\EmployeeFinance;

use App\Application\EmployeeFinance\DTO\EmployeeTableQuery;
use App\Ports\Out\EmployeeFinance\EmployeeTableReaderPort;
use Illuminate\Support\Facades\DB;

final class DatabaseEmployeeTableReaderAdapter implements EmployeeTableReaderPort
{
    public function __construct(private readonly EmployeeTablePayload $payload = new EmployeeTablePayload) {}

    public function search(EmployeeTableQuery $query): array
    {
        $builder = DB::table('employees')
            ->select([
                'employees.id',
                'employees.employee_name',
                'employees.phone',
                'employees.salary_basis_type',
                'employees.default_salary_amount',
                'employees.employment_status',
            ])
            ->selectSub(
                DB::table('employee_debts as debt_target')
                    ->select('debt_target.id')
                    ->whereColumn('debt_target.employee_id', 'employees.id')
                    ->orderByRaw("CASE WHEN debt_target.status = 'unpaid' THEN 0 ELSE 1 END")
                    ->orderByDesc('debt_target.created_at')
                    ->orderByDesc('debt_target.id')
                    ->limit(1),
                'debt_detail_id'
            );

        if ($query->q() !== null) {
            foreach (preg_split('/\s+/', $query->q()) ?: [] as $term) {
                $builder->where(function ($where) use ($term): void {
                    $like = '%'.$term.'%';

                    $where->where('employee_name', 'like', $like)
                        ->orWhere('phone', 'like', $like)
                        ->orWhere('salary_basis_type', 'like', $like)
                        ->orWhere('employment_status', 'like', $like);
                });
            }
        }

        if ($query->employmentStatus() !== null) {
            $builder->where('employment_status', $query->employmentStatus());
        }
        if ($query->salaryBasisType() !== null) {
            $builder->where('salary_basis_type', $query->salaryBasisType());
        }

        if ($query->sortBy() === 'relevance' && $query->q() !== null) {
            $term = mb_strtolower($query->q(), 'UTF-8');
            $builder->orderByRaw(
                'CASE WHEN LOWER(employee_name) = ? THEN 0 WHEN LOWER(employee_name) LIKE ? THEN 1 WHEN phone = ? THEN 2 WHEN phone LIKE ? THEN 3 ELSE 4 END',
                [$term, $term.'%', $query->q(), $query->q().'%'],
            )->orderBy('employee_name');
        } else {
            $builder->orderBy($query->sortBy(), $query->sortDir());
        }

        $paginator = $builder->orderBy('employees.id')
            ->paginate($query->perPage(), ['*'], 'page', $query->page());

        return $this->payload->fromPaginator($paginator, $query);
    }
}
