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
                ->with('error', 'Service tidak ditemukan.');
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
            ->with('success', 'Service diaktifkan.');
    }
}
