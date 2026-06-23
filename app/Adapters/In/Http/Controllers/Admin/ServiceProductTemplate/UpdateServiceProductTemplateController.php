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

final class UpdateServiceProductTemplateController extends Controller
{
    use ValidatesServiceProductTemplateForm;

    public function __construct(
        private readonly ServiceProductTemplateAdminLineInput $lineInput,
        private readonly ServiceProductTemplateLineWriter $lineWriter,
    ) {
    }

    public function __invoke(Request $request, string $templateId): RedirectResponse
    {
        $template = DB::table('service_product_templates')
            ->where('id', trim($templateId))
            ->first();

        if ($template === null) {
            return redirect()
                ->route('admin.service-product-templates.index')
                ->with('error', 'Service tidak ditemukan.');
        }

        $data = $this->validated($request);
        $lines = $this->lineInput->fromData($data);
        $serviceCatalogItemId = (string) $data['service_catalog_item_id'];

        if (
            (bool) $template->is_active
            && $this->activeTemplateExists($lines[0]['product_id'], $serviceCatalogItemId, trim($templateId))
        ) {
            return back()
                ->withErrors(['service_catalog_item_id' => 'Produk 1 dan jasa ini sudah punya paket aktif lain.'])
                ->withInput();
        }

        $servicePrice = $this->serviceDefaultPriceRupiah($serviceCatalogItemId);
        $packageTotal = $this->lineInput->total($lines) + $servicePrice;

        DB::transaction(function () use ($lines, $packageTotal, $servicePrice, $serviceCatalogItemId, $templateId): void {
            DB::table('service_product_templates')
                ->where('id', trim($templateId))
                ->update([
                    'product_id' => $lines[0]['product_id'],
                    'service_catalog_item_id' => $serviceCatalogItemId,
                    'default_service_price_rupiah' => $servicePrice,
                    'default_package_total_rupiah' => $packageTotal,
                    'sort_order' => 0,
                    'updated_at' => now(),
                ]);

            $this->lineWriter->replace(trim($templateId), $lines);
        });

        return redirect()
            ->route('admin.service-product-templates.index')
            ->with('success', 'Service berhasil diperbarui.');
    }
}
