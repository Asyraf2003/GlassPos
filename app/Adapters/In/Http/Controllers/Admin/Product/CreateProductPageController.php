<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Admin\Product;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class CreateProductPageController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('admin.products.create', [
            'returnTo' => $this->resolveReturnTo($request->query('return_to')),
            'returnLabel' => $this->resolveNullableString($request->query('return_label')) ?? 'Kembali',
        ]);
    }


    private function resolveReturnTo(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        if ($trimmed === '') {
            return null;
        }

        $allowedAbsolute = route('admin.procurement.supplier-invoices.create');
        $allowedRelative = route('admin.procurement.supplier-invoices.create', [], false);

        if (str_starts_with($trimmed, $allowedAbsolute) || str_starts_with($trimmed, $allowedRelative)) {
            return $trimmed;
        }

        return null;
    }

    private function resolveNullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
