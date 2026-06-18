<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Admin\ServiceProductTemplate;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class StoreServiceProductTemplateController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        if ($this->activeTemplateExists((string) $data['product_id'])) {
            return back()
                ->withErrors(['product_id' => 'Produk ini sudah punya paket aktif. Nonaktifkan paket lama dulu.'])
                ->withInput();
        }

        $productPrice = $this->productPrice((string) $data['product_id']);
        $servicePrice = $this->servicePrice((string) $data['service_catalog_item_id']);
        $packageTotal = (int) $data['default_package_total_rupiah'];
        $minimumTotal = $productPrice + $servicePrice;

        if ($packageTotal < $minimumTotal) {
            return back()
                ->withErrors([
                    'default_package_total_rupiah' => sprintf(
                        'Total paket minimal %s karena harga produk + jasa adalah batas bawah.',
                        number_format($minimumTotal, 0, ',', '.')
                    ),
                ])
                ->withInput();
        }

        DB::table('service_product_templates')->insert([
            'id' => (string) Str::uuid(),
            'product_id' => (string) $data['product_id'],
            'service_catalog_item_id' => (string) $data['service_catalog_item_id'],
            'default_service_price_rupiah' => $servicePrice,
            'default_package_total_rupiah' => $packageTotal,
            'is_active' => true,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()
            ->route('admin.service-product-templates.index')
            ->with('success', 'Paket service berhasil dibuat.');
    }

    /**
     * @return array<string, mixed>
     */
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
            'default_package_total_rupiah' => ['required', 'integer', 'min:1'],
        ]);
    }

    private function productPrice(string $productId): int
    {
        return (int) DB::table('products')
            ->where('id', trim($productId))
            ->whereNull('deleted_at')
            ->value('harga_jual');
    }

    private function servicePrice(string $serviceCatalogItemId): int
    {
        return (int) DB::table('service_catalog_items')
            ->where('id', trim($serviceCatalogItemId))
            ->where('is_active', true)
            ->value('default_price_rupiah');
    }

    private function activeTemplateExists(string $productId): bool
    {
        return DB::table('service_product_templates')
            ->where('product_id', trim($productId))
            ->where('is_active', true)
            ->exists();
    }
}
