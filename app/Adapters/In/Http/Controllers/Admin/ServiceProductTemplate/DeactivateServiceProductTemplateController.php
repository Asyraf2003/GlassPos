<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Admin\ServiceProductTemplate;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

final class DeactivateServiceProductTemplateController extends Controller
{
    public function __invoke(string $templateId): RedirectResponse
    {
        $affected = DB::table('service_product_templates')
            ->where('id', trim($templateId))
            ->update([
                'is_active' => false,
                'updated_at' => now(),
            ]);

        if ($affected < 1) {
            return redirect()
                ->route('admin.service-product-templates.index')
                ->with('error', 'Paket Service tidak ditemukan.');
        }

        return redirect()
            ->route('admin.service-product-templates.index')
            ->with('success', 'Paket Service dinonaktifkan.');
    }
}
