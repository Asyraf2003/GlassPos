<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Admin\ServiceCatalog;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

final class ActivateServiceCatalogItemController extends Controller
{
    public function __invoke(string $serviceId): RedirectResponse
    {
        $affected = DB::table('service_catalog_items')
            ->where('id', trim($serviceId))
            ->update([
                'is_active' => true,
                'updated_at' => now(),
            ]);

        if ($affected < 1) {
            return redirect()
                ->route('admin.services.index')
                ->with('error', 'Master jasa tidak ditemukan.');
        }

        return redirect()
            ->route('admin.services.index')
            ->with('success', 'Master jasa diaktifkan.');
    }
}
