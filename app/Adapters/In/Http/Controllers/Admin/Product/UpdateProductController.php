<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Admin\Product;

use App\Application\ProductCatalog\Context\ProductChangeContext;
use App\Adapters\In\Http\Requests\ProductCatalog\UpdateProductRequest;
use App\Application\ProductCatalog\UseCases\UpdateProductHandler;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;

final class UpdateProductController extends Controller
{
    public function __invoke(
        UpdateProductRequest $request,
        UpdateProductHandler $useCase,
        ProductChangeContext $changeContext,
        string $productId,
    ): RedirectResponse {
        $data = $request->validated();

        $user = $request->user();
        $actorId = $user?->getAuthIdentifier();

        $changeContext->set(
            $actorId !== null ? (string) $actorId : null,
            null,
            'web_admin',
            isset($data['change_reason']) ? (string) $data['change_reason'] : null,
        );

        $result = $useCase->handle(
            $productId,
            isset($data['kode_barang']) ? (string) $data['kode_barang'] : null,
            (string) $data['nama_barang'],
            (string) $data['merek'],
            isset($data['ukuran']) ? (int) $data['ukuran'] : null,
            (int) $data['harga_jual'],
            isset($data['reorder_point_qty']) ? (int) $data['reorder_point_qty'] : null,
            isset($data['critical_threshold_qty']) ? (int) $data['critical_threshold_qty'] : null,
        );

        if ($result->isFailure()) {
            if (($result->errors()['product'] ?? []) === ['PRODUCT_NOT_FOUND']) {
                return redirect()
                    ->route('admin.products.index')
                    ->with('error', $result->message() ?? 'Product tidak ditemukan.');
            }

            if (($result->errors()['product'] ?? []) === ['PRODUCT_CODE_ALREADY_EXISTS']) {
                return back()
                    ->withErrors([
                        'kode_barang' => $result->message() ?? 'Kode barang sudah dipakai product lain.',
                    ])
                    ->withInput();
            }

            return back()
                ->withErrors([
                    'product' => $result->message() ?? 'Product master gagal diperbarui.',
                ])
                ->withInput();
        }

        return redirect()
            ->route('admin.products.index')
            ->with('success', $result->message() ?? 'Product master berhasil diperbarui.');
    }
}
