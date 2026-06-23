<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Admin\ServiceProductTemplate;

use App\Adapters\In\Http\Controllers\Admin\ServiceProductTemplate\Concerns\ValidatesServiceProductTemplateForm;
use App\Application\ServiceProductTemplate\Services\ServiceProductTemplateAdminLineInput;
use App\Application\ServiceProductTemplate\Services\ServiceProductTemplateLineWriter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class StoreServiceProductTemplateController extends Controller
{
    use ValidatesServiceProductTemplateForm;

    public function __construct(
        private readonly ServiceProductTemplateAdminLineInput $lineInput,
        private readonly ServiceProductTemplateLineWriter $lineWriter,
    ) {
    }

    public function __invoke(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $lines = $this->lineInput->fromData($data);
        $serviceCatalogItemId = (string) $data['service_catalog_item_id'];

        if ($this->activeTemplateExists($lines[0]['product_id'], $serviceCatalogItemId)) {
            return back()
                ->withErrors(['service_catalog_item_id' => 'Produk 1 dan jasa ini sudah punya paket aktif.'])
                ->withInput();
        }

        $servicePrice = $this->serviceDefaultPriceRupiah($serviceCatalogItemId);
        $packageTotal = $this->lineInput->total($lines) + $servicePrice;

        DB::transaction(function () use ($data, $lines, $packageTotal, $servicePrice, $serviceCatalogItemId): void {
            $templateId = (string) Str::uuid();

            DB::table('service_product_templates')->insert([
                'id' => $templateId,
                'product_id' => $lines[0]['product_id'],
                'service_catalog_item_id' => $serviceCatalogItemId,
                'default_service_price_rupiah' => $servicePrice,
                'default_package_total_rupiah' => $packageTotal,
                'is_active' => true,
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->lineWriter->replace($templateId, $lines);
        });

        return redirect()
            ->route('admin.service-product-templates.index')
            ->with('success', 'Service berhasil dibuat.');
    }
}
