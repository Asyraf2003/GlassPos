<?php

// @audit-skip: line-limit
declare(strict_types=1);

namespace App\Adapters\Out\EmployeeFinance;

use App\Application\EmployeeFinance\DTO\PayrollTableQuery;
use App\Ports\Out\EmployeeFinance\PayrollTableReaderPort;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class DatabasePayrollTableReaderAdapter implements PayrollTableReaderPort
{
    public function search(PayrollTableQuery $query): array
    {
        $builder = DB::table('payroll_disbursements')
            ->join('employees', 'employees.id', '=', 'payroll_disbursements.employee_id')
            ->leftJoin(
                'payroll_disbursement_reversals',
                'payroll_disbursements.id',
                '=',
                'payroll_disbursement_reversals.payroll_disbursement_id'
            )
            ->select([
                'payroll_disbursements.id',
                'payroll_disbursements.employee_id',
                'employees.employee_name as employee_name',
                'payroll_disbursements.amount',
                'payroll_disbursements.disbursement_date',
                'payroll_disbursements.mode',
                'payroll_disbursements.notes',
                'payroll_disbursement_reversals.id as reversal_id',
                'payroll_disbursement_reversals.reason as reversal_reason',
                'payroll_disbursement_reversals.created_at as reversal_created_at',
            ]);

        if ($query->q() !== null) {
            foreach (preg_split('/\s+/', $query->q()) ?: [] as $term) {
                $builder->where(function ($where) use ($term): void {
                    $like = '%'.$term.'%';
                    $where->where('employees.employee_name', 'like', $like)
                        ->orWhere('payroll_disbursements.notes', 'like', $like)
                        ->orWhere('payroll_disbursements.mode', 'like', $like)
                        ->orWhere(DB::raw('DATE(payroll_disbursements.disbursement_date)'), 'like', $like)
                        ->orWhere('payroll_disbursement_reversals.reason', 'like', $like);
                });
            }
        }

        if ($query->mode() !== null) {
            $builder->where('payroll_disbursements.mode', $query->mode());
        }
        if ($query->status() === 'active') {
            $builder->whereNull('payroll_disbursement_reversals.id');
        }
        if ($query->status() === 'reversed') {
            $builder->whereNotNull('payroll_disbursement_reversals.id');
        }
        if ($query->dateFrom() !== null) {
            $builder->whereDate('payroll_disbursements.disbursement_date', '>=', $query->dateFrom());
        }
        if ($query->dateTo() !== null) {
            $builder->whereDate('payroll_disbursements.disbursement_date', '<=', $query->dateTo());
        }

        $column = match ($query->sortBy()) {
            'employee_name' => 'employees.employee_name',
            'amount' => 'payroll_disbursements.amount',
            'mode' => 'payroll_disbursements.mode',
            default => 'payroll_disbursements.disbursement_date',
        };

        if ($query->sortBy() === 'relevance' && $query->q() !== null) {
            $term = mb_strtolower($query->q(), 'UTF-8');
            $builder->orderByRaw('CASE WHEN LOWER(employees.employee_name) = ? THEN 0 WHEN LOWER(employees.employee_name) LIKE ? THEN 1 WHEN LOWER(payroll_disbursements.mode) = ? THEN 2 WHEN LOWER(payroll_disbursements.notes) LIKE ? THEN 3 ELSE 4 END', [$term, $term.'%', $term, '%'.$term.'%']);
        } elseif ($query->sortBy() === 'status') {
            $builder->orderByRaw('CASE WHEN payroll_disbursement_reversals.id IS NULL THEN 0 ELSE 1 END '.$query->sortDir());
        } else {
            $builder->orderBy($column, $query->sortDir());
        }

        $paginator = $builder->orderByDesc('payroll_disbursements.created_at')
            ->orderBy('payroll_disbursements.id')
            ->paginate($query->perPage(), ['*'], 'page', $query->page());

        return [
            'rows' => collect($paginator->items())->map(function (object $row): array {
                $isReversed = $row->reversal_id !== null;

                return [
                    'id' => (string) $row->id,
                    'employee_id' => (string) $row->employee_id,
                    'employee_name' => (string) $row->employee_name,
                    'amount' => (int) $row->amount,
                    'amount_formatted' => number_format((int) $row->amount, 0, ',', '.'),
                    'disbursement_date' => Carbon::parse((string) $row->disbursement_date)->format('Y-m-d'),
                    'mode_value' => (string) $row->mode,
                    'mode_label' => $this->modeLabel((string) $row->mode),
                    'notes' => $row->notes !== null ? (string) $row->notes : null,
                    'is_reversed' => $isReversed,
                    'reversal_reason' => $row->reversal_reason !== null ? (string) $row->reversal_reason : null,
                    'reversal_created_at' => $row->reversal_created_at !== null
                        ? Carbon::parse((string) $row->reversal_created_at)->format('Y-m-d H:i')
                        : null,
                ];
            })->values()->all(),
            'meta' => [
                'page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'sort_by' => $query->sortBy(),
                'sort_dir' => $query->sortDir(),
                'filters' => ['q' => $query->q(), 'mode' => $query->mode(), 'status' => $query->status(), 'date_from' => $query->dateFrom(), 'date_to' => $query->dateTo()],
            ],
        ];
    }

    private function modeLabel(string $value): string
    {
        return match ($value) {
            'daily' => 'Harian',
            'weekly' => 'Mingguan',
            'monthly' => 'Bulanan',
            default => ucfirst($value),
        };
    }
}
