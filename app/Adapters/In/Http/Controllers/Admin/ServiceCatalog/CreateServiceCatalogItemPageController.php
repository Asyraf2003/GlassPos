<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Admin\ServiceCatalog;

use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;

final class CreateServiceCatalogItemPageController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.service_catalog.create', [
            'service' => null,
        ]);
    }
}
