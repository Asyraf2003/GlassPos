<?php

declare(strict_types=1);

namespace App\Application\ServiceProductTemplate\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ServiceProductTemplateAdminLineInput
{
    /**
     * @param array<string, mixed> $data
     * @return list<array{product_id:string,qty:int,sort_order:int}>
     */
    public function fromData(array $data): array
    {
        $inputLines = is_array($data['product_lines'] ?? null) ? $data['product_lines'] : [];
        $lines = [[
            'product_id' => trim((string) $data['product_id']),
            'qty' => 1,
            'sort_order' => 0,
        ]];

        foreach ([1, 2] as $index) {
            $line = is_array($inputLines[$index] ?? null) ? $inputLines[$index] : [];
            $productId = trim((string) ($line['product_id'] ?? ''));

            if ($productId !== '') {
                $lines[] = [
                    'product_id' => $productId,
                    'qty' => 1,
                    'sort_order' => $index,
                ];
            }
        }

        $this->assertDistinct($lines);

        return $lines;
    }

    /** @param list<array{product_id:string,qty:int,sort_order:int}> $lines */
    public function total(array $lines): int
    {
        return array_reduce(
            $lines,
            fn (int $sum, array $line): int => $sum + ($this->productPrice($line['product_id']) * $line['qty']),
            0,
        );
    }

    private function productPrice(string $productId): int
    {
        return (int) DB::table('products')
            ->where('id', trim($productId))
            ->whereNull('deleted_at')
            ->value('harga_jual');
    }

    /** @param list<array{product_id:string,qty:int,sort_order:int}> $lines */
    private function assertDistinct(array $lines): void
    {
        $ids = array_map(static fn (array $line): string => $line['product_id'], $lines);

        if (count($ids) !== count(array_unique($ids))) {
            throw ValidationException::withMessages(['product_lines' => 'Produk paket tidak boleh duplikat.']);
        }
    }
}
