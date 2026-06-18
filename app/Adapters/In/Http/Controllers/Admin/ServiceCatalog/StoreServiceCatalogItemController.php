<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Admin\ServiceCatalog;

use App\Core\ServiceCatalog\ServiceNameNormalizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class StoreServiceCatalogItemController extends Controller
{
    public function __invoke(Request $request, ServiceNameNormalizer $normalizer): RedirectResponse
    {
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

        if (DB::table('service_catalog_items')->where('normalized_name', $normalized)->exists()) {
            return back()
                ->withErrors(['name' => 'Master jasa dengan nama ini sudah ada.'])
                ->withInput();
        }

        DB::table('service_catalog_items')->insert([
            'id' => (string) Str::uuid(),
            'name' => $name,
            'normalized_name' => $normalized,
            'default_price_rupiah' => (int) $data['default_price_rupiah'],
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()
            ->route('admin.services.index')
            ->with('success', 'Master jasa berhasil dibuat.');
    }
}
