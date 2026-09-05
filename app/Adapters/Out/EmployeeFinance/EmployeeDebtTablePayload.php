<?php

declare(strict_types=1);

namespace App\Adapters\Out\EmployeeFinance;

use App\Application\EmployeeFinance\DTO\EmployeeDebtTableQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

final class EmployeeDebtTablePayload
{
    public function fromPaginator(LengthAwarePaginator $paginator, EmployeeDebtTableQuery $query): array
    {
        return [
            'rows' => collect($paginator->items())->map(fn (object $row): array => [
                'employee_id' => (string) $row->employee_id,
                'employee_name' => (string) $row->employee_name,
                'debt_detail_id' => $row->debt_detail_id !== null ? (string) $row->debt_detail_id : null,
                'latest_unpaid_debt_id' => $row->latest_unpaid_debt_id !== null ? (string) $row->latest_unpaid_debt_id : null,
                'total_debt_records' => (int) $row->total_debt_records,
                'total_debt_amount_formatted' => number_format((int) $row->total_debt_amount, 0, ',', '.'),
                'total_remaining_balance_formatted' => number_format((int) $row->total_remaining_balance, 0, ',', '.'),
                'active_debt_count' => (int) $row->active_debt_count,
                'paid_debt_count' => (int) $row->paid_debt_count,
                'status_label' => ((int) $row->active_debt_count) > 0 ? 'Aktif' : 'Lunas',
                'latest_recorded_at' => Carbon::parse((string) $row->latest_recorded_at)->format('Y-m-d'),
            ])->values()->all(),
            'meta' => [
                'page' => $paginator->currentPage(), 'per_page' => $paginator->perPage(),
                'total' => $paginator->total(), 'last_page' => $paginator->lastPage(),
                'sort_by' => $query->sortBy(), 'sort_dir' => $query->sortDir(),
                'filters' => ['q' => $query->q(), 'status' => $query->status()],
            ],
        ];
    }
}
