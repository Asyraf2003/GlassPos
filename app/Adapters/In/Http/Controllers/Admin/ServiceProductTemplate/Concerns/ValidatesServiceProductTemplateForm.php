<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Admin\ServiceProductTemplate\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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
            'product_lines' => ['nullable', 'array', 'max:3'],
            'product_lines.*.product_id' => [
                'nullable',
                'string',
                Rule::exists('products', 'id')->whereNull('deleted_at'),
            ],
        ]);
    }

    private function activeTemplateExists(
        string $productId,
        string $serviceCatalogItemId,
        ?string $exceptTemplateId = null
    ): bool {
        $query = DB::table('service_product_templates')
            ->where('product_id', trim($productId))
            ->where('service_catalog_item_id', trim($serviceCatalogItemId))
            ->where('is_active', true);

        if ($exceptTemplateId !== null && trim($exceptTemplateId) !== '') {
            $query->where('id', '!=', trim($exceptTemplateId));
        }

        return $query->exists();
    }

    private function serviceDefaultPriceRupiah(string $serviceCatalogItemId): int
    {
        return (int) DB::table('service_catalog_items')
            ->where('id', trim($serviceCatalogItemId))
            ->where('is_active', true)
            ->value('default_price_rupiah');
    }
}
