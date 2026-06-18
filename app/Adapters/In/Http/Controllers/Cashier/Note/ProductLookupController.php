<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Cashier\Note;

use App\Application\Note\Services\CashierNoteProductLookupData;
use App\Application\ServiceProductTemplate\DTO\ServiceProductTemplateLookupRow;
use App\Application\ProductCatalog\DTO\ProductLookupRow;
use App\Ports\Out\ServiceProductTemplate\ServiceProductTemplateLookupReaderPort;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class ProductLookupController extends Controller
{
    public function __invoke(
        Request $request,
        CashierNoteProductLookupData $lookupData,
        ServiceProductTemplateLookupReaderPort $serviceProductTemplates,
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

        $includeServiceProductTemplate = (string) $request->query('context', '') === 'service_product';
        $rows = [];

        foreach ($lookupData->searchAvailableProducts($query) as $product) {
            $template = $includeServiceProductTemplate
                ? $serviceProductTemplates->findActiveByProductId($product->id)
                : null;

            $rows[] = $this->toRow($product, $template, $includeServiceProductTemplate);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'rows' => $rows,
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function toRow(
        ProductLookupRow $product,
        ?ServiceProductTemplateLookupRow $template,
        bool $includeServiceProductTemplate,
    ): array {
        $row = [
            'id' => $product->id,
            'label' => $product->label(),
            'available_stock' => $product->availableStock,
            'default_unit_price_rupiah' => $product->defaultUnitPriceRupiah,
            'minimum_unit_price_rupiah' => $product->minimumUnitPriceRupiah,
        ];

        if ($includeServiceProductTemplate) {
            $row['service_product_template'] = $template === null
                ? null
                : $this->toServiceProductTemplateRow($template);
        }

        return $row;
    }

    /**
     * @return array{id:string,service_catalog_item_id:string,service_name:string,default_service_price_rupiah:int,default_package_total_rupiah:int|null}
     */
    private function toServiceProductTemplateRow(ServiceProductTemplateLookupRow $template): array
    {
        return [
            'id' => $template->id,
            'service_catalog_item_id' => $template->serviceCatalogItemId,
            'service_name' => $template->serviceName,
            'default_service_price_rupiah' => $template->defaultServicePriceRupiah,
            'default_package_total_rupiah' => $template->defaultPackageTotalRupiah,
        ];
    }
}
