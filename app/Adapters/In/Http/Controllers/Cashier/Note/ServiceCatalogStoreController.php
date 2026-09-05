<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Cashier\Note;

use App\Application\ServiceCatalog\UseCases\CreateServiceCatalogItemHandler;
use App\Core\ServiceCatalog\ServiceCatalogItem;
use App\Core\Shared\Exceptions\DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class ServiceCatalogStoreController extends Controller
{
    public function __invoke(Request $request, CreateServiceCatalogItemHandler $services): JsonResponse
    {
        $name = trim((string) $request->input('name', ''));
        $price = $this->price($request->input('default_price_rupiah'));

        if ($name === '' || $price <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Nama dan harga default jasa wajib valid.',
            ], 422);
        }

        $actorId = $request->user()?->getAuthIdentifier();

        try {
            $item = $services->handle(
                $name,
                $price,
                $actorId !== null ? (string) $actorId : null,
                'web_cashier',
            );
        } catch (DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data' => ['row' => $this->row($item)],
        ]);
    }

    private function price(mixed $value): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (! is_string($value)) {
            return 0;
        }

        $digits = preg_replace('/\D+/', '', $value);

        return is_string($digits) && $digits !== '' ? (int) $digits : 0;
    }

    /** @return array<string, mixed> */
    private function row(ServiceCatalogItem $item): array
    {
        return [
            'id' => $item->id(),
            'label' => $item->name(),
            'normalized_name' => $item->normalizedName(),
            'default_price_rupiah' => $item->defaultPriceRupiah(),
        ];
    }
}
