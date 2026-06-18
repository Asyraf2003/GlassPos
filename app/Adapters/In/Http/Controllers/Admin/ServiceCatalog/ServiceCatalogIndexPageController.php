<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Admin\ServiceCatalog;

use App\Application\ServiceCatalog\Services\ServiceCatalogAdminPageData;
use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;

final class ServiceCatalogIndexPageController extends Controller
{
    public function __invoke(ServiceCatalogAdminPageData $pageData): View
    {
        return view('admin.service_catalog.index', [
            'services' => $pageData->services(),
        ]);
    }
}
