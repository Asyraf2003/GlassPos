<?php

declare(strict_types=1);

namespace App\Application\ServiceProductTemplate\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class ServiceProductTemplateLineWriter
{
    /**
     * @param list<array{product_id:string,qty:int,sort_order:int}> $lines
     */
    public function replace(string $templateId, array $lines): void
    {
        DB::table('service_product_template_lines')
            ->where('service_product_template_id', trim($templateId))
            ->delete();

        DB::table('service_product_template_lines')->insert(array_map(
            static fn (array $line): array => [
                'id' => (string) Str::uuid(),
                'service_product_template_id' => trim($templateId),
                'product_id' => $line['product_id'],
                'qty' => $line['qty'],
                'sort_order' => $line['sort_order'],
                'created_at' => now(),
                'updated_at' => now(),
            ],
            $lines,
        ));
    }
}
