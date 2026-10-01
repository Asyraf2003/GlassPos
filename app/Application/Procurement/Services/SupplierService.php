<?php

declare(strict_types=1);

namespace App\Application\Procurement\Services;

use App\Core\Procurement\Supplier\Supplier;
use App\Core\Shared\Exceptions\DomainException;
use App\Ports\Out\Procurement\{SupplierReaderPort, SupplierWriterPort};
use App\Ports\Out\UuidPort;

final class SupplierService
{
    public function __construct(
        private SupplierReaderPort $readers,
        private SupplierWriterPort $writers,
        private UuidPort $uuid
    ) {}

    public function resolve(string $ptName, ?string $bankName = null, ?string $bankAccountNumber = null): Supplier
    {
        $normalized = $this->normalize($ptName);
        $existing = $this->readers->getByNormalizedNamaPtPengirim($normalized);
        if ($existing) return $existing;

        $supplier = Supplier::create($this->uuid->generate(), trim($ptName), $bankName, $bankAccountNumber);
        $this->writers->create($supplier);
        return $supplier;
    }

    private function normalize(string $name): string
    {
        $val = trim($name);
        if ($val === '') throw new DomainException('Nama PT pengirim wajib ada.');
        $val = preg_replace('/\s+/', ' ', $val) ?? $val;
        return mb_strtolower($val);
    }
}
