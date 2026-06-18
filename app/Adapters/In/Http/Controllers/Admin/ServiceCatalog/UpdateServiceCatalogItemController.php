<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Admin\ServiceCatalog;

use App\Core\ServiceCatalog\ServiceNameNormalizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

final class UpdateServiceCatalogItemController extends Controller
{
    public function __invoke(Request $request, ServiceNameNormalizer $normalizer, string $serviceId): RedirectResponse
    {
        $service = DB::table('service_catalog_items')
            ->where('id', trim($serviceId))
            ->first();

        if ($service === null) {
            return redirect()
                ->route('admin.services.index')
                ->with('error', 'Master jasa tidak ditemukan.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'default_price_rupiah' => ['required', 'integer', 'min:1'],
        ]);

        $name = trim((string) $data['name']);
        $normalized = $normalizer->normalize($name);

        if ($normalized === '') {
            return back()
                ->withErrors(['name' => 'Nama jasa wajib valid.'])
                ->withInput();
        }

        $duplicateExists = DB::table('service_catalog_items')
            ->where('normalized_name', $normalized)
            ->where('id', '!=', trim($serviceId))
            ->exists();

        if ($duplicateExists) {
            return back()
                ->withErrors(['name' => 'Master jasa dengan nama ini sudah ada.'])
                ->withInput();
        }

        DB::table('service_catalog_items')
            ->where('id', trim($serviceId))
            ->update([
                'name' => $name,
                'normalized_name' => $normalized,
                'default_price_rupiah' => (int) $data['default_price_rupiah'],
                'updated_at' => now(),
            ]);

        return redirect()
            ->route('admin.services.index')
            ->with('success', 'Master jasa berhasil diperbarui.');
    }
}
