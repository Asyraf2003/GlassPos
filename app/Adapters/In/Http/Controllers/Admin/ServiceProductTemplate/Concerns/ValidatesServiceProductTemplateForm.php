<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Admin\ServiceProductTemplate\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

trait ValidatesServiceProductTemplateForm
{
    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'product_id' => [
                'required',
                'string',
                Rule::exists('products', 'id')->whereNull('deleted_at'),
            ],
            'service_catalog_item_id' => [
                'required',
                'string',
                Rule::exists('service_catalog_items', 'id')->where('is_active', true),
            ],
            'default_service_price_rupiah' => ['required', 'integer', 'min:1'],
            'default_package_total_rupiah' => ['nullable', 'integer', 'min:1'],
            'product_lines' => ['nullable', 'array', 'max:3'],
            'product_lines.*.product_id' => [
                'nullable',
                'string',
                Rule::exists('products', 'id')->whereNull('deleted_at'),
            ],
            'product_lines.*.qty' => ['nullable', 'integer', 'min:1'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ]);
    }

    private function productPrice(string $productId): int
    {
        return (int) DB::table('products')
            ->where('id', trim($productId))
            ->whereNull('deleted_at')
            ->value('harga_jual');
    }

    private function activeTemplateExists(string $productId, ?string $exceptTemplateId = null): bool
    {
        $query = DB::table('service_product_templates')
            ->where('product_id', trim($productId))
            ->where('is_active', true);

        if ($exceptTemplateId !== null && trim($exceptTemplateId) !== '') {
            $query->where('id', '!=', trim($exceptTemplateId));
        }

        return $query->exists();
    }

    /**
     * @param array<string, mixed> $data
     * @return list<array{product_id:string,qty:int,sort_order:int}>
     */
    private function productLines(array $data): array
    {
        $lines = [[
            'product_id' => trim((string) $data['product_id']),
            'qty' => 1,
            'sort_order' => 0,
        ]];

        foreach ([1, 2] as $index) {
            $line = is_array($data['product_lines'][$index] ?? null) ? $data['product_lines'][$index] : [];
            $productId = trim((string) ($line['product_id'] ?? ''));

            if ($productId !== '') {
                $lines[] = ['product_id' => $productId, 'qty' => (int) ($line['qty'] ?? 1), 'sort_order' => $index];
            }
        }

        $this->assertDistinctProductLines($lines);

        return $lines;
    }

    /** @param list<array{product_id:string,qty:int,sort_order:int}> $lines */
    private function productLinesTotal(array $lines): int
    {
        return array_reduce($lines, fn (int $sum, array $line): int => (
            $sum + ($this->productPrice($line['product_id']) * $line['qty'])
        ), 0);
    }

    /** @param list<array{product_id:string,qty:int,sort_order:int}> $lines */
    private function assertDistinctProductLines(array $lines): void
    {
        $ids = array_map(static fn (array $line): string => $line['product_id'], $lines);

        if (count($ids) !== count(array_unique($ids))) {
            throw ValidationException::withMessages(['product_lines' => 'Produk paket tidak boleh duplikat.']);
        }
    }

    private function nullableInt(mixed $value): ?int
    {
        return $value !== null && $value !== '' ? (int) $value : null;
    }

    private function minimumTotalMessage(int $minimumTotal): string
    {
        return sprintf(
            'Total paket minimal %s karena harga produk + jasa adalah batas bawah.',
            number_format($minimumTotal, 0, ',', '.')
        );
    }
}
