<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Ports\Out\ProductCatalog\ProductReaderPort;

final class CreateTransactionWorkspaceServiceStoreStockPackageAutoSplitComposer
{
    use CreateTransactionWorkspaceServiceStoreStockPackageAutoSplitPayload;
    use CreateTransactionWorkspaceServiceStoreStockPackageAutoSplitBranches;

    public function __construct(
        private readonly ProductReaderPort $products,
        private readonly CreateTransactionWorkspaceServiceStoreStockPackageTemplateRules $rules,
    ) {
    }

    /**
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     */
    public function compose(array $item): array
    {
        $packageTotal = $this->requiredInt(
            $item['package_total_rupiah'] ?? null,
            'Harga paket wajib diisi.'
        );

        $pricedLines = (new CreateTransactionWorkspaceServiceStoreStockPackageProductLinesComposer($this->products))
            ->compose($item['product_lines'] ?? []);

        $sparepartTotal = $pricedLines['sparepart_total_rupiah'];

        $this->assertPackageCoversSparepart($packageTotal, $sparepartTotal);

        if ($this->rules->requiresTemplate($item)) {
            return $this->composeWithTemplate($item, $pricedLines, $packageTotal, $sparepartTotal);
        }

        return $this->composeWithoutTemplate($item, $pricedLines, $packageTotal, $sparepartTotal);
    }
}
