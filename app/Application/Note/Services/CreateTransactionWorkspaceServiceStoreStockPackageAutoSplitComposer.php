<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Core\Shared\Exceptions\DomainException;
use App\Ports\Out\ProductCatalog\ProductReaderPort;

final class CreateTransactionWorkspaceServiceStoreStockPackageAutoSplitComposer
{
    use CreateTransactionWorkspaceServiceStoreStockPackageAutoSplitPayload;
    public function __construct(
        private readonly ProductReaderPort $products,
        private readonly CreateTransactionWorkspaceServiceStoreStockPackageTemplateRules $rules,
    ) {
    }

    /** @param array<string, mixed> $item */
    public function compose(array $item): array
    {
        $packageTotal = $this->requiredInt($item['package_total_rupiah'] ?? null, 'Harga paket wajib diisi.');
        $pricedLines = (new CreateTransactionWorkspaceServiceStoreStockPackageProductLinesComposer($this->products))
            ->compose($item['product_lines'] ?? []);
        $sparepartTotal = $pricedLines['sparepart_total_rupiah'];

        if ($packageTotal < $sparepartTotal) {
            throw new DomainException('Harga paket tidak boleh lebih kecil dari total harga sparepart.');
        }

        if ($this->rules->requiresTemplate($item)) {
            return $this->composeWithTemplate($item, $pricedLines, $packageTotal, $sparepartTotal);
        }

        return $this->composeWithoutTemplate($item, $pricedLines, $packageTotal, $sparepartTotal);
    }

    /** @param array<string, mixed> $item @param array<string, mixed> $pricedLines */
    private function composeWithTemplate(array $item, array $pricedLines, int $packageTotal, int $sparepartTotal): array
    {
        if (count($pricedLines['product_lines']) !== 1) {
            throw new DomainException('Paket servis + produk hanya boleh memakai 1 produk template aktif.');
        }

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

        $item['service'] = $service;
        $item['product_lines'] = $pricedLines['product_lines'];

        return $item;
    }

    /** @param array<string, mixed> $item @param array<string, mixed> $pricedLines */
    private function composeWithoutTemplate(array $item, array $pricedLines, int $packageTotal, int $sparepartTotal): array
    {
        $servicePrice = $packageTotal - $sparepartTotal;
        $minimumTemplateServicePrice = $this->rules->minimumTemplateServicePrice($pricedLines['product_lines'], false);

        if ($minimumTemplateServicePrice > 0 && $servicePrice < $minimumTemplateServicePrice) {
            throw new DomainException('Harga paket tidak boleh membuat harga jasa di bawah default template.');
        }

        $service = $this->service($item);
        $service['price_rupiah'] = $servicePrice;
        $service['package_profit_rupiah'] = 0;
        $service['package_base_service_price_rupiah'] = null;
        $service['package_service_extra_rupiah'] = 0;

        $item['service'] = $service;
        $item['product_lines'] = $pricedLines['product_lines'];

        return $item;
    }


        return $value;
    }
}
