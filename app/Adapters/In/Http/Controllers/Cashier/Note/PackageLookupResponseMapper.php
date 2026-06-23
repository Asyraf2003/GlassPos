<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Cashier\Note;

use App\Application\ServiceProductTemplate\DTO\ServiceProductTemplatePackageLookupRow;
use App\Application\ServiceProductTemplate\DTO\ServiceProductTemplatePackageProductLineRow;

final class PackageLookupResponseMapper
{
    public function __construct(private readonly PackageLookupProductLineResponseMapper $productLines) {}

    /**
     * @param list<ServiceProductTemplatePackageLookupRow> $packages
     * @return list<array<string, mixed>>
     */
    public function mapMany(array $packages): array
    {
        return array_map(fn (ServiceProductTemplatePackageLookupRow $package): array => $this->map($package), $packages);
    }

    /** @return array<string, mixed> */
    private function map(ServiceProductTemplatePackageLookupRow $package): array
    {
        $stockLabel = $package->hasSufficientStock() ? 'stok aman' : 'stok kurang';

        return [
            'id' => $package->id,
            'label' => $this->label($package, $stockLabel),
            'description' => $this->description($package, $stockLabel),
            'stock_status' => $package->hasSufficientStock() ? 'safe' : 'insufficient',
            'stock_label' => $stockLabel,
            'service_product_template' => $this->templatePayload($package),
            'service' => $this->servicePayload($package),
            'product_lines' => array_map(
                fn (ServiceProductTemplatePackageProductLineRow $line): array => $this->productLines->map($line),
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
        return implode(' + ', array_map(
            static fn (ServiceProductTemplatePackageProductLineRow $line): string => $line->productName,
            $package->productLines,
        ));
    }

    /** @return array<string, mixed> */
    private function templatePayload(ServiceProductTemplatePackageLookupRow $package): array
    {
        return [
            'id' => $package->id,
            'legacy_product_id' => $package->legacyProductId,
            'service_catalog_item_id' => $package->serviceCatalogItemId,
            'service_name' => $package->serviceName,
            'default_service_price_rupiah' => $package->defaultServicePriceRupiah,
            'default_package_total_rupiah' => $package->defaultPackageTotalRupiah,
        ];
    }

    /** @return array<string, mixed> */
    private function servicePayload(ServiceProductTemplatePackageLookupRow $package): array
    {
        return [
            'catalog_item_id' => $package->serviceCatalogItemId,
            'name' => $package->serviceName,
            'price_rupiah' => $package->defaultServicePriceRupiah,
        ];
    }

    private function rupiah(int $amount): string
    {
        return number_format($amount, 0, ',', '.');
    }
}
