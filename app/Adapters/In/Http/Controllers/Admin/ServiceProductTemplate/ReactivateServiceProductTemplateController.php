<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Admin\ServiceProductTemplate;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

final class ReactivateServiceProductTemplateController extends Controller
{
    public function __invoke(string $templateId): RedirectResponse
    {
        $template = DB::table('service_product_templates')
            ->where('id', trim($templateId))
            ->first();

        if ($template === null) {
            return redirect()
                ->route('admin.service-product-templates.index')
                ->with('error', 'Paket Service tidak ditemukan.');
        }

        $productExists = DB::table('products')
            ->where('id', (string) $template->product_id)
            ->whereNull('deleted_at')
            ->exists();

        if (! $productExists) {
            return back()
                ->withErrors(['product_id' => 'Produk 1 template sudah tidak tersedia.']);
        }

        $serviceIsActive = DB::table('service_catalog_items')
            ->where('id', (string) $template->service_catalog_item_id)
            ->where('is_active', true)
            ->exists();

        if (! $serviceIsActive) {
            return back()
                ->withErrors(['service_catalog_item_id' => 'Jasa template sudah tidak aktif.']);
        }

        $activeTemplateExists = DB::table('service_product_templates')
            ->where('product_id', (string) $template->product_id)
            ->where('service_catalog_item_id', (string) $template->service_catalog_item_id)
            ->where('is_active', true)
            ->where('id', '!=', trim($templateId))
            ->exists();

        if ($activeTemplateExists) {
            return back()
                ->withErrors(['service_catalog_item_id' => 'Produk 1 dan jasa ini sudah punya template aktif lain.']);
        }

        DB::table('service_product_templates')
            ->where('id', trim($templateId))
            ->update([
                'is_active' => true,
                'updated_at' => now(),
            ]);

        return redirect()
            ->route('admin.service-product-templates.index')
            ->with('success', 'Paket Service diaktifkan.');
    }
}
