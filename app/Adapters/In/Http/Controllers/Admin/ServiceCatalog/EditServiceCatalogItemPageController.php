<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Admin\ServiceCatalog;

use App\Adapters\Out\ServiceCatalog\DatabaseServiceCatalogAdminPageData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;

final class EditServiceCatalogItemPageController extends Controller
{
    public function __invoke(DatabaseServiceCatalogAdminPageData $pageData, string $serviceId): View|RedirectResponse
    {
        $service = $pageData->service($serviceId);

        if ($service === null) {
            return redirect()
                ->route('admin.services.index')
                ->with('error', 'Master jasa tidak ditemukan.');
        }

        return view('admin.service_catalog.edit', [
            'service' => $service,
        ]);
    }
}
