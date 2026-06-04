<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Cashier\Note;

use App\Core\ServiceCatalog\ServiceCatalogItem;
use App\Ports\Out\ServiceCatalog\ServiceCatalogReaderPort;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class ServiceCatalogLookupController extends Controller
{
    public function __invoke(Request $request, ServiceCatalogReaderPort $services): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));

        return $this->rows($services->search($query));
    }

    /**
     * @param list<ServiceCatalogItem> $items
     */
    private function rows(array $items): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'rows' => array_map(
                    fn (ServiceCatalogItem $item): array => $this->row($item),
                    $items
                ),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
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
