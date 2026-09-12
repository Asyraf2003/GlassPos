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
            'productOptions' => $pageData->productOptions(array_values(array_filter([
                (string) old('product_id', ''),
                (string) old('product_lines.1.product_id', ''),
                (string) old('product_lines.2.product_id', ''),
            ]))),
            'serviceOptions' => $pageData->serviceOptions(),
        ]);
    }
}
