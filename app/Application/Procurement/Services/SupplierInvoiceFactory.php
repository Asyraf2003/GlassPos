<?php

declare(strict_types=1);

namespace App\Application\Procurement\Services;

use App\Core\Procurement\SupplierInvoice\SupplierInvoiceLine;
use App\Core\Procurement\SupplierInvoice\SupplierInvoiceTaxSummary;
use App\Core\Shared\Exceptions\DomainException;
use App\Core\Shared\ValueObjects\Money;
use App\Ports\Out\ProductCatalog\ProductReaderPort;
use App\Ports\Out\UuidPort;

final class SupplierInvoiceFactory
{
    public function __construct(
        private ProductReaderPort $products,
        private UuidPort $uuid
    ) {
    }

    public function makeLines(array $lines): array
    {
        if ($lines === []) {
            throw new DomainException('Invoice minimal 1 line.');
        }

        $this->assertNoDuplicateProducts($lines);

        return array_map(function ($line, int $index) {
            $productId = trim((string) ($line['product_id'] ?? ''));
            $product = $productId !== '' ? $this->products->getById($productId) : null;

            if ($product === null) {
                throw new DomainException('Product tidak ditemukan.');
            }

            $lineNo = isset($line['line_no']) ? (int) $line['line_no'] : ($index + 1);

            return SupplierInvoiceLine::create(
                $this->uuid->generate(),
                $lineNo,
                $productId,
                $product->kodeBarang(),
                $product->namaBarang(),
                $product->merek(),
                $product->ukuran(),
                (int) ($line['qty_pcs'] ?? 0),
                Money::fromInt((int) ($line['line_total_rupiah'] ?? 0)),
                Money::fromInt((int) ($line['line_subtotal_before_tax_rupiah'] ?? ($line['line_total_rupiah'] ?? 0))),
                isset($line['tax_input']) ? (string) $line['tax_input'] : null,
                (string) ($line['tax_mode'] ?? SupplierInvoiceTaxSummary::MODE_NONE),
                array_key_exists('tax_rate_basis_points', $line) && $line['tax_rate_basis_points'] !== null ? (int) $line['tax_rate_basis_points'] : null,
                Money::fromInt((int) ($line['tax_amount_rupiah'] ?? 0))
            );
        }, $lines, array_keys($lines));
    }

    private function assertNoDuplicateProducts(array $lines): void
    {
        $seen = [];

        foreach ($lines as $index => $line) {
            if (! is_array($line)) {
                continue;
            }

            $productId = trim((string) ($line['product_id'] ?? ''));
            if ($productId === '') {
                continue;
            }

            $lineNo = isset($line['line_no']) ? (int) $line['line_no'] : ($index + 1);

            if (array_key_exists($productId, $seen)) {
                throw new DomainException(
                    'Baris ' . $lineNo . ': produk yang sama sudah dipakai di baris ' . $seen[$productId] . '.'
                );
            }

            $seen[$productId] = $lineNo;
        }
    }
}
