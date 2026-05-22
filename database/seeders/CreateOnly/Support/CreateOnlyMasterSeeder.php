<?php

declare(strict_types=1);

namespace Database\Seeders\CreateOnly\Support;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

abstract class CreateOnlyMasterSeeder extends Seeder
{
    protected function assertLocalOrTesting(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException(static::class . ' is only allowed in local/testing environments.');
        }
    }

    protected function createOnly(string $table, string $key, mixed $value, array $row): bool
    {
        if (DB::table($table)->where($key, '=', $value)->exists()) {
            return false;
        }

        DB::table($table)->insert($this->filterExistingColumns($table, $row));

        return true;
    }

    protected function seedSuppliers(string $prefix, int $count): void
    {
        for ($i = 1; $i <= $count; $i++) {
            $name = sprintf('Supplier Demo %s %03d', strtoupper($prefix), $i);
            $id = $this->id('sup', $prefix, $i);

            $this->createOnly('suppliers', 'id', $id, [
                'id' => $id,
                'nama_pt_pengirim' => $name,
                'nama_pt_pengirim_normalized' => $this->normalize($name),
                'deleted_at' => null,
                'deleted_by_actor_id' => null,
                'delete_reason' => null,
            ]);
        }
    }

    protected function seedProducts(string $prefix, int $count): void
    {
        for ($i = 1; $i <= $count; $i++) {
            $id = $this->id('prod', $prefix, $i);
            $name = sprintf('Barang Demo %s %03d', strtoupper($prefix), $i);
            $brand = sprintf('Merek %s %02d', strtoupper($prefix), (($i - 1) % 20) + 1);
            $size = (($i - 1) % 10) + 1;
            $price = 15000 + ($i * 2500);

            $this->createOnly('products', 'id', $id, [
                'id' => $id,
                'kode_barang' => sprintf('%s-P%04d', strtoupper($prefix), $i),
                'nama_barang' => $name,
                'merek' => $brand,
                'ukuran' => $size,
                'harga_jual' => $price,
                'deleted_at' => null,
                'deleted_by_actor_id' => null,
                'delete_reason' => null,
                'nama_barang_normalized' => $this->normalize($name),
                'merek_normalized' => $this->normalize($brand),
                'reorder_point_qty' => 5 + ($i % 5),
                'critical_threshold_qty' => 2 + ($i % 3),
            ]);
        }
    }

    protected function seedEmployees(string $prefix, int $count): void
    {
        for ($i = 1; $i <= $count; $i++) {
            $id = $this->employeeId($prefix, $i);
            $name = sprintf('Karyawan Demo %s %03d', strtoupper($prefix), $i);

            $this->createOnly('employees', 'id', $id, [
                'id' => $id,

                // Final employee master v2 columns.
                'employee_name' => $name,
                'salary_basis_type' => 'monthly',
                'default_salary_amount' => 2500000 + ($i * 50000),
                'employment_status' => 'active',
                'started_at' => now()->toDateString(),
                'ended_at' => null,

                // Legacy columns are included only for pre-v2 migrated local DBs.
                // filterExistingColumns() removes them when final schema already dropped them.
                'name' => $name,
                'phone' => sprintf('0800%08d', $i),
                'base_salary' => 2500000 + ($i * 50000),
                'pay_period' => 'monthly',
                'status' => 'active',

                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    protected function seedExpenseCategories(string $prefix): void
    {
        $rows = [
            ['code' => strtoupper($prefix) . '-OPERASIONAL', 'name' => 'Operasional'],
            ['code' => strtoupper($prefix) . '-LISTRIK', 'name' => 'Listrik'],
            ['code' => strtoupper($prefix) . '-INTERNET', 'name' => 'Internet'],
            ['code' => strtoupper($prefix) . '-MAINTENANCE', 'name' => 'Maintenance'],
            ['code' => strtoupper($prefix) . '-LAINNYA', 'name' => 'Lainnya'],
        ];

        foreach ($rows as $index => $row) {
            $id = $this->id('exp-cat', $prefix, $index + 1);

            $this->createOnly('expense_categories', 'id', $id, [
                'id' => $id,
                'code' => $row['code'],
                'name' => $row['name'],
                'description' => 'Demo create-only expense category.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function filterExistingColumns(string $table, array $row): array
    {
        $columns = array_flip(Schema::getColumnListing($table));

        return array_intersect_key($row, $columns);
    }

    private function normalize(string $value): string
    {
        $value = preg_replace('/\s+/', ' ', trim($value)) ?? trim($value);

        return mb_strtolower($value);
    }

    private function id(string $entity, string $prefix, int $number): string
    {
        return sprintf('%s-%s-%03d', $entity, $prefix, $number);
    }

    private function employeeId(string $prefix, int $number): string
    {
        $group = match ($prefix) {
            'basic' => '0001',
            'week' => '0002',
            'year' => '0003',
            default => '0009',
        };

        return sprintf('00000000-0000-4000-%s-%012d', $group, $number);
    }
}
