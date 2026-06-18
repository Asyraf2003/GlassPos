<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Application\ServiceProductTemplate\DTO\ServiceProductTemplateLookupRow;
use App\Core\Shared\Exceptions\DomainException;
use App\Ports\Out\ProductCatalog\ProductReaderPort;
use App\Ports\Out\ServiceProductTemplate\ServiceProductTemplateLookupReaderPort;

final class CreateTransactionWorkspaceServiceStoreStockPackagePricingComposer
{
    public function __construct(
        private readonly ProductReaderPort $products,
        private readonly ServiceProductTemplateLookupReaderPort $templates,
    ) {
    }

    /**
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     */
    public function compose(array $item): array
    {
        if (! $this->hasProductLine($item['product_lines'] ?? [])) {
            return $item;
        }

        $requiresServiceProductTemplate = $this->requiresServiceProductTemplate($item);

        if (($item['pricing_mode'] ?? null) !== 'package_auto_split') {
            if ($requiresServiceProductTemplate) {
                throw new DomainException('Paket servis + produk wajib memakai template aktif.');
            }

            return $item;
        }

        $packageTotal = $this->requiredInt($item['package_total_rupiah'] ?? null, 'Harga paket wajib diisi.');
        $pricedLines = (new CreateTransactionWorkspaceServiceStoreStockPackageProductLinesComposer($this->products))
            ->compose($item['product_lines'] ?? []);
        $sparepartTotal = $pricedLines['sparepart_total_rupiah'];

        if ($requiresServiceProductTemplate && count($pricedLines['product_lines']) !== 1) {
            throw new DomainException('Paket servis + produk hanya boleh memakai 1 produk template aktif.');
        }

        if ($packageTotal < $sparepartTotal) {
            throw new DomainException('Harga paket tidak boleh lebih kecil dari total harga sparepart.');
        }

        $service = is_array($item['service'] ?? null) ? $item['service'] : [];

        if ($requiresServiceProductTemplate) {
            $template = $this->activeTemplateForSingleProductLine($pricedLines['product_lines']);
            $baseServicePrice = $template->defaultServicePriceRupiah;
            $minimumPackageTotal = $sparepartTotal + $baseServicePrice;

            if ($packageTotal < $minimumPackageTotal) {
                throw new DomainException('Harga paket tidak boleh membuat harga jasa di bawah default template.');
            }

            $extra = $packageTotal - $minimumPackageTotal;
            $serviceExtra = intdiv($extra, 5);
            $packageProfit = $extra - $serviceExtra;

            $service['price_rupiah'] = $baseServicePrice + $serviceExtra;
            $service['package_profit_rupiah'] = $packageProfit;
            $service['package_base_service_price_rupiah'] = $baseServicePrice;
            $service['package_service_extra_rupiah'] = $serviceExtra;
        } else {
            $servicePrice = $packageTotal - $sparepartTotal;
            $minimumTemplateServicePrice = $this->minimumTemplateServicePrice(
                $pricedLines['product_lines'],
                false,
            );

            if ($minimumTemplateServicePrice > 0 && $servicePrice < $minimumTemplateServicePrice) {
                throw new DomainException('Harga paket tidak boleh membuat harga jasa di bawah default template.');
            }

            $service['price_rupiah'] = $servicePrice;
            $service['package_profit_rupiah'] = 0;
            $service['package_base_service_price_rupiah'] = null;
            $service['package_service_extra_rupiah'] = 0;
        }

        $item['service'] = $service;
        $item['product_lines'] = $pricedLines['product_lines'];

        return $item;
    }

    /**
     * @param list<array<string, mixed>> $productLines
     */
    private function activeTemplateForSingleProductLine(array $productLines): ServiceProductTemplateLookupRow
    {
        $line = $productLines[0] ?? [];
        $productId = trim((string) ($line['product_id'] ?? ''));

        if ($productId === '') {
            throw new DomainException('Paket servis + produk wajib memakai template aktif.');
        }

        $template = $this->templates->findActiveByProductId($productId);

        if ($template === null) {
            throw new DomainException('Paket servis + produk wajib memakai template aktif.');
        }

        return $template;
    }

    /**
     * @param mixed $productLines
     */
    private function minimumTemplateServicePrice(mixed $productLines, bool $requireTemplate = false): int
    {
        if (! is_array($productLines)) {
            return 0;
        }

        $minimum = 0;

        foreach ($productLines as $line) {
            if (! is_array($line)) {
                continue;
            }

            $productId = trim((string) ($line['product_id'] ?? ''));

            if ($productId === '') {
                continue;
            }

            $template = $this->templates->findActiveByProductId($productId);

            if ($template === null) {
                if ($requireTemplate) {
                    throw new DomainException('Paket servis + produk wajib memakai template aktif.');
                }

                continue;
            }

            $minimum = max($minimum, $template->defaultServicePriceRupiah);
        }

        return $minimum;
    }

    /**
     * @param array<string, mixed> $item
     */
    private function requiresServiceProductTemplate(array $item): bool
    {
        return filter_var($item['requires_service_product_template'] ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    private function hasProductLine(mixed $value): bool
    {
        if (! is_array($value)) {
            return false;
        }

        $first = array_values($value)[0] ?? [];

        return is_array($first)
            && is_string($first['product_id'] ?? null)
            && trim((string) $first['product_id']) !== '';
    }

    private function requiredInt(mixed $value, string $message): int
    {
        if (! is_int($value) || $value <= 0) {
            throw new DomainException($message);
        }

        return $value;
    }
}
