<?php

declare(strict_types=1);

namespace App\Adapters\Out\EmployeeFinance;

use App\Application\EmployeeFinance\DTO\EmployeeTableQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class EmployeeTablePayload
{
    public function fromPaginator(LengthAwarePaginator $paginator, EmployeeTableQuery $query): array
    {
        return [
            'rows' => collect($paginator->items())->map(fn (object $row): array => [
                'id' => (string) $row->id,
                'employee_name' => (string) $row->employee_name,
                'phone' => $row->phone !== null ? (string) $row->phone : null,
                'salary_basis_type' => (string) $row->salary_basis_type,
                'salary_basis_label' => $this->salaryBasisLabel((string) $row->salary_basis_type),
                'default_salary_amount' => $row->default_salary_amount !== null ? (int) $row->default_salary_amount : null,
                'default_salary_amount_formatted' => $row->default_salary_amount !== null ? number_format((int) $row->default_salary_amount, 0, ',', '.') : null,
                'employment_status' => (string) $row->employment_status,
                'employment_status_label' => (string) $row->employment_status === 'active' ? 'Aktif' : 'Nonaktif',
                'debt_detail_id' => $row->debt_detail_id !== null ? (string) $row->debt_detail_id : null,
            ])->values()->all(),
            'meta' => [
                'page' => $paginator->currentPage(), 'per_page' => $paginator->perPage(),
                'total' => $paginator->total(), 'last_page' => $paginator->lastPage(),
                'sort_by' => $query->sortBy(), 'sort_dir' => $query->sortDir(),
                'filters' => ['q' => $query->q(), 'employment_status' => $query->employmentStatus(), 'salary_basis_type' => $query->salaryBasisType()],
            ],
        ];
    }

    private function salaryBasisLabel(string $value): string
    {
        return match ($value) {
            'daily' => 'Harian', 'weekly' => 'Mingguan', 'monthly' => 'Bulanan', 'manual' => 'Manual', default => ucfirst($value),
        };
    }
}
