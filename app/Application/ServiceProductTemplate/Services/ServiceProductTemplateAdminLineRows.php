<?php

declare(strict_types=1);

namespace App\Application\ServiceProductTemplate\Services;

use Illuminate\Support\Facades\DB;

final class ServiceProductTemplateAdminLineRows
{
    /** @return list<array{product_id:string,qty:int,sort_order:int}> */
    public function forTemplate(string $templateId, string $legacyProductId): array
    {
        $rows = DB::table('service_product_template_lines')
            ->where('service_product_template_id', trim($templateId))
            ->orderBy('sort_order')
            ->get(['product_id', 'qty', 'sort_order']);

        if ($rows->isEmpty()) {
            return [[
                'product_id' => trim($legacyProductId),
                'qty' => 1,
                'sort_order' => 0,
            ]];
        }

        return array_map(static fn (object $row): array => [
            'product_id' => (string) $row->product_id,
            'qty' => (int) $row->qty,
            'sort_order' => (int) $row->sort_order,
        ], $rows->all());
    }
}
