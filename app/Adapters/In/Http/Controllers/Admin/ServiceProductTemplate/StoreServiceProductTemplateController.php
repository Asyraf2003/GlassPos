<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Admin\ServiceProductTemplate;

use App\Adapters\In\Http\Controllers\Admin\ServiceProductTemplate\Concerns\ValidatesServiceProductTemplateForm;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class StoreServiceProductTemplateController extends Controller
{
    use ValidatesServiceProductTemplateForm;

    public function __invoke(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        if ($this->activeTemplateExists((string) $data['product_id'])) {
            return back()
                ->withErrors(['product_id' => 'Produk ini sudah punya paket aktif. Nonaktifkan paket lama dulu.'])
                ->withInput();
        }

        $servicePrice = (int) $data['default_service_price_rupiah'];
        $packageTotal = $this->nullableInt($data['default_package_total_rupiah'] ?? null);
        $minimumTotal = $this->productPrice((string) $data['product_id']) + $servicePrice;

        if ($packageTotal !== null && $packageTotal < $minimumTotal) {
            return back()
                ->withErrors(['default_package_total_rupiah' => $this->minimumTotalMessage($minimumTotal)])
                ->withInput();
        }

        DB::table('service_product_templates')->insert([
            'id' => (string) Str::uuid(),
            'product_id' => (string) $data['product_id'],
            'service_catalog_item_id' => (string) $data['service_catalog_item_id'],
            'default_service_price_rupiah' => $servicePrice,
            'default_package_total_rupiah' => $packageTotal,
            'is_active' => true,
            'sort_order' => (int) $data['sort_order'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()
            ->route('admin.service-product-templates.index')
            ->with('success', 'Template jasa + produk berhasil dibuat.');
    }
}
