<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Admin\ServiceProductTemplate;

use App\Adapters\In\Http\Controllers\Admin\ServiceProductTemplate\Concerns\ValidatesServiceProductTemplateForm;
use App\Adapters\Out\ServiceProductTemplate\DatabaseServiceProductTemplateAdminLineInput;
use App\Application\ServiceProductTemplate\UseCases\CreateServiceProductTemplateHandler;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class StoreServiceProductTemplateController extends Controller
{
    use ValidatesServiceProductTemplateForm;

    public function __construct(
        private readonly DatabaseServiceProductTemplateAdminLineInput $lineInput,
        private readonly CreateServiceProductTemplateHandler $useCase,
    ) {}

    public function __invoke(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $lines = $this->lineInput->fromData($data);
        $actorId = $request->user()?->getAuthIdentifier();

        $result = $this->useCase->handle(
            $lines,
            (string) $data['service_catalog_item_id'],
            $actorId !== null ? (string) $actorId : null,
            'web_admin',
        );

        if ($result->isFailure()) {
            $errors = $result->errors();
            $field = array_key_first($errors) ?? 'service_catalog_item_id';

            return back()
                ->withErrors([$field => $result->message() ?? 'Service gagal dibuat.'])
                ->withInput();
        }

        return redirect()
            ->route('admin.service-product-templates.index')
            ->with('success', $result->message() ?? 'Service berhasil dibuat.');
    }
}
