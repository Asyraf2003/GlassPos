<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Admin\Product;

use App\Adapters\In\Http\Presenters\Admin\Product\ProductDetailPagePresenter;
use App\Application\ProductCatalog\Services\LinkedServicePackagesForProduct;
use App\Application\ProductCatalog\UseCases\GetProductDetailHandler;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;

final class ShowProductPageController extends Controller
{
    public function __invoke(
        GetProductDetailHandler $useCase,
        ProductDetailPagePresenter $presenter,
        LinkedServicePackagesForProduct $linkedPackages,
        string $productId,
    ): View|RedirectResponse {
        $result = $useCase->handle($productId);

        if ($result->isFailure()) {
            return redirect()
                ->route('admin.products.index')
                ->with('error', $result->message() ?? 'Product tidak ditemukan.');
        }

        $page = $presenter->present($result->data());
        $page['linked_service_packages'] = $linkedPackages->get($productId);

        return view('admin.products.show', [
            'page' => $page,
        ]);
    }
}
