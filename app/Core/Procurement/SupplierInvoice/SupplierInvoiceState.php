<?php

declare(strict_types=1);

namespace App\Core\Procurement\SupplierInvoice;

use App\Core\Shared\ValueObjects\Money;
use DateTimeImmutable;

trait SupplierInvoiceState
{
    /** @param list<SupplierInvoiceLine> $lines */
    private function __construct(
        private string $id,
        private string $supplierId,
        private string $supplierNamaPtPengirimSnapshot,
        private string $nomorFaktur,
        private string $documentKind,
        private string $lifecycleStatus,
        private ?string $originSupplierInvoiceId,
        private ?string $supersededBySupplierInvoiceId,
        private DateTimeImmutable $tanggalPengiriman,
        private DateTimeImmutable $jatuhTempo,
        private array $lines,
        private Money $grandTotalRupiah,
        private SupplierInvoiceTaxSummary $taxSummary,
    ) {
    }

    public function id(): string { return $this->id; }
    public function supplierId(): string { return $this->supplierId; }
    public function supplierNamaPtPengirimSnapshot(): string { return $this->supplierNamaPtPengirimSnapshot; }
    public function nomorFaktur(): string { return $this->nomorFaktur; }
    public function nomorFakturNormalized(): string { return self::normalizeInvoiceNumber($this->nomorFaktur); }
    public function documentKind(): string { return $this->documentKind; }
    public function lifecycleStatus(): string { return $this->lifecycleStatus; }
    public function originSupplierInvoiceId(): ?string { return $this->originSupplierInvoiceId; }
    public function supersededBySupplierInvoiceId(): ?string { return $this->supersededBySupplierInvoiceId; }
    public function tanggalPengiriman(): DateTimeImmutable { return $this->tanggalPengiriman; }
    public function jatuhTempo(): DateTimeImmutable { return $this->jatuhTempo; }

    /** @return list<SupplierInvoiceLine> */
    public function lines(): array { return $this->lines; }

    public function grandTotalRupiah(): Money { return $this->grandTotalRupiah; }
    public function subtotalBeforeTaxRupiah(): Money { return $this->taxSummary->subtotalBeforeTaxRupiah(); }
    public function taxInput(): ?string { return $this->taxSummary->taxInput(); }
    public function taxMode(): string { return $this->taxSummary->taxMode(); }
    public function taxRateBasisPoints(): ?int { return $this->taxSummary->taxRateBasisPoints(); }
    public function taxAmountRupiah(): Money { return $this->taxSummary->taxAmountRupiah(); }
    public function taxSummary(): SupplierInvoiceTaxSummary { return $this->taxSummary; }

    private static function normalizeNullableString(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private static function normalizeInvoiceNumber(string $value): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/', '', $value) ?? ''), 'UTF-8');
    }
}
