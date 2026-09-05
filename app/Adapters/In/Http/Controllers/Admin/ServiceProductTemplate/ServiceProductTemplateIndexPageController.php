<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Admin\ServiceProductTemplate;

use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;

final class ServiceProductTemplateIndexPageController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.service_product_templates.index');
    }
}
