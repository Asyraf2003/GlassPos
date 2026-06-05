<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Cashier\Note;

use App\Application\Note\Services\CashierNoteProductLookupData;
use App\Application\ProductCatalog\DTO\ProductLookupRow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class ProductLookupController extends Controller
{
    public function __invoke(
        Request $request,
        CashierNoteProductLookupData $lookupData,
    ): JsonResponse {
        $query = trim((string) $request->query('q', ''));

        if (mb_strlen($query) < 2) {
            return response()->json([
                'success' => true,
                'data' => [
                    'rows' => [],
                ],
            ]);
        }

        $rows = [];

        foreach ($lookupData->searchAvailableProducts($query) as $product) {
            $rows[] = $this->toRow($product);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'rows' => $rows,
            ],
        ]);
    }

    /**
     * @return array{id:string,label:string,available_stock:int,default_unit_price_rupiah:int,minimum_unit_price_rupiah:int}
     */
    private function toRow(ProductLookupRow $product): array
    {
        return [
            'id' => $product->id,
            'label' => $product->label(),
            'available_stock' => $product->availableStock,
            'default_unit_price_rupiah' => $product->defaultUnitPriceRupiah,
            'minimum_unit_price_rupiah' => $product->minimumUnitPriceRupiah,
        ];
    }
}
