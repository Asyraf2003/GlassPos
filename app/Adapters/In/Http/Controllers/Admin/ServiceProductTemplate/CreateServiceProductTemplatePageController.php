<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Admin\ServiceProductTemplate;

use App\Adapters\Out\ServiceProductTemplate\DatabaseServiceProductTemplateAdminPageData;
use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;

final class CreateServiceProductTemplatePageController extends Controller
{
    public function __invoke(DatabaseServiceProductTemplateAdminPageData $pageData): View
    {
        return view('admin.service_product_templates.create', [
            'template' => null,
            'productOptions' => $pageData->productOptions(),
            'serviceOptions' => $pageData->serviceOptions(),
        ]);
    }
}
