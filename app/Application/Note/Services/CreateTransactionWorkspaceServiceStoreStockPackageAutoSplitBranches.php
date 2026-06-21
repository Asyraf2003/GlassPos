<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Core\Shared\Exceptions\DomainException;

trait CreateTransactionWorkspaceServiceStoreStockPackageAutoSplitBranches
{
    /**
     * @param array<string, mixed> $item
     * @param array<string, mixed> $pricedLines
     * @return array<string, mixed>
     */
    private function composeWithTemplate(
        array $item,
        array $pricedLines,
        int $packageTotal,
        int $sparepartTotal
    ): array {
	        $template = $this->rules->activeTemplateForSingleProductLine($pricedLines['product_lines']);
        $baseServicePrice = $template->defaultServicePriceRupiah;
        $minimumPackageTotal = $sparepartTotal + $baseServicePrice;

        if ($packageTotal < $minimumPackageTotal) {
            throw new DomainException('Harga paket tidak boleh membuat harga jasa di bawah default template.');
        }

        $extra = $packageTotal - $minimumPackageTotal;
        $serviceExtra = intdiv($extra, 5);
        $service = $this->service($item);

        $service['price_rupiah'] = $baseServicePrice + $serviceExtra;
        $service['package_profit_rupiah'] = $extra - $serviceExtra;
        $service['package_base_service_price_rupiah'] = $baseServicePrice;
        $service['package_service_extra_rupiah'] = $serviceExtra;

        return $this->withServiceAndLines($item, $service, $pricedLines);
    }

    /**
     * @param array<string, mixed> $item
     * @param array<string, mixed> $pricedLines
     * @return array<string, mixed>
     */
    private function composeWithoutTemplate(
        array $item,
        array $pricedLines,
        int $packageTotal,
        int $sparepartTotal
    ): array {
        $servicePrice = $packageTotal - $sparepartTotal;
        $minimumTemplateServicePrice = $this->rules->minimumTemplateServicePrice(
            $pricedLines['product_lines'],
            false
        );

        if ($minimumTemplateServicePrice > 0 && $servicePrice < $minimumTemplateServicePrice) {
            throw new DomainException('Harga paket tidak boleh membuat harga jasa di bawah default template.');
        }

        $service = $this->service($item);
        $service['price_rupiah'] = $servicePrice;
        $service['package_profit_rupiah'] = 0;
        $service['package_base_service_price_rupiah'] = null;
        $service['package_service_extra_rupiah'] = 0;

        return $this->withServiceAndLines($item, $service, $pricedLines);
    }
}
