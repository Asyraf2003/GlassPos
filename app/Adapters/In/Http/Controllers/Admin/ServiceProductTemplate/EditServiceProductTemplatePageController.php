<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Admin\ServiceProductTemplate;

use App\Adapters\Out\ServiceProductTemplate\DatabaseServiceProductTemplateAdminPageData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;

final class EditServiceProductTemplatePageController extends Controller
{
    public function __invoke(DatabaseServiceProductTemplateAdminPageData $pageData, string $templateId): View|RedirectResponse
    {
        $template = $pageData->template($templateId);

        if ($template === null) {
            return redirect()
                ->route('admin.service-product-templates.index')
                ->with('error', 'Service tidak ditemukan.');
        }

        return view('admin.service_product_templates.edit', [
            'template' => $template,
            'productOptions' => $pageData->productOptions(),
            'serviceOptions' => $pageData->serviceOptions((string) $template['service_catalog_item_id']),
        ]);
    }
}
