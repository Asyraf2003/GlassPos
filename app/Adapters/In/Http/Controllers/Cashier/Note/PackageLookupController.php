<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Cashier\Note;

use App\Application\ServiceProductTemplate\DTO\ServiceProductTemplatePackageLookupRow;
use App\Application\ServiceProductTemplate\DTO\ServiceProductTemplatePackageProductLineRow;
use App\Ports\Out\ServiceProductTemplate\ServiceProductTemplateLookupReaderPort;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

final class PackageLookupController extends Controller
{
    public function __invoke(Request $request, ServiceProductTemplateLookupReaderPort $packages): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));

        return response()->json([
            'success' => true,
            'data' => [
                'rows' => array_map(
                    fn (ServiceProductTemplatePackageLookupRow $package): array => $this->toRow($package),
                    $packages->searchActivePackages($query),
                ),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function toRow(ServiceProductTemplatePackageLookupRow $package): array
    {
        $stockIsSafe = $package->hasSufficientStock();
        $stockLabel = $stockIsSafe ? 'stok aman' : 'stok kurang';

        return [
            'id' => $package->id,
            'label' => $this->label($package, $stockLabel),
            'description' => $this->description($package, $stockLabel),
            'stock_status' => $stockIsSafe ? 'safe' : 'insufficient',
            'stock_label' => $stockLabel,
            'service_product_template' => [
                'id' => $package->id,
                'legacy_product_id' => $package->legacyProductId,
                'service_catalog_item_id' => $package->serviceCatalogItemId,
                'service_name' => $package->serviceName,
                'default_service_price_rupiah' => $package->defaultServicePriceRupiah,
                'default_package_total_rupiah' => $package->defaultPackageTotalRupiah,
            ],
            'service' => [
                'catalog_item_id' => $package->serviceCatalogItemId,
                'name' => $package->serviceName,
                'price_rupiah' => $package->defaultServicePriceRupiah,
            ],
            'product_lines' => array_map(
                fn (ServiceProductTemplatePackageProductLineRow $line): array => $this->productLine($line),
                $package->productLines,
            ),
        ];
    }

    private function label(ServiceProductTemplatePackageLookupRow $package, string $stockLabel): string
    {
        return implode(' · ', [
            'Paket ' . $package->serviceName,
            'Service: ' . $package->serviceName,
            'Produk: ' . $this->productNames($package),
            'Harga Servis ' . $this->rupiah($package->defaultServicePriceRupiah),
            $stockLabel,
        ]);
    }

    private function description(ServiceProductTemplatePackageLookupRow $package, string $stockLabel): string
    {
        return implode(' · ', [
            'Service: ' . $package->serviceName,
            'Produk: ' . $this->productNames($package),
            'Harga Servis ' . $this->rupiah($package->defaultServicePriceRupiah),
            $stockLabel,
        ]);
    }

    private function productNames(ServiceProductTemplatePackageLookupRow $package): string
    {
        return implode(
            ' + ',
            array_map(
                static fn (ServiceProductTemplatePackageProductLineRow $line): string => $line->productName,
                $package->productLines,
            ),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function productLine(ServiceProductTemplatePackageProductLineRow $line): array
    {
        return [
            'product_id' => $line->productId,
            'label' => $line->label(),
            'product_name' => $line->productName,
            'kode_barang' => $line->kodeBarang,
            'qty' => $line->qty,
            'sort_order' => $line->sortOrder,
            'available_stock' => $line->availableStock,
            'unit_price_rupiah' => $line->defaultUnitPriceRupiah,
            'minimum_unit_price_rupiah' => $line->minimumUnitPriceRupiah,
            'stock_status' => $line->availableStock >= $line->qty ? 'safe' : 'insufficient',
        ];
    }

    private function rupiah(int $amount): string
    {
        return number_format($amount, 0, ',', '.');
    }
}
