<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Application\ServiceProductTemplate\DTO\ServiceProductTemplateLookupRow;
use App\Core\Shared\Exceptions\DomainException;
use App\Ports\Out\ServiceProductTemplate\ServiceProductTemplateLookupReaderPort;

final class CreateTransactionWorkspaceServiceStoreStockPackageTemplateRules
{
    public function __construct(private readonly ServiceProductTemplateLookupReaderPort $templates)
    {
    }

    /** @param array<string, mixed> $item */
    public function requiresTemplate(array $item): bool
    {
        return filter_var($item['requires_service_product_template'] ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    /** @param mixed $value */
    public function hasProductLine(mixed $value): bool
    {
        if (! is_array($value)) {
            return false;
        }

        $first = array_values($value)[0] ?? [];

        return is_array($first)
            && is_string($first['product_id'] ?? null)
            && trim((string) $first['product_id']) !== '';
    }

    /** @param list<array<string, mixed>> $productLines */
    public function activeTemplateForSingleProductLine(array $productLines): ServiceProductTemplateLookupRow
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

}
