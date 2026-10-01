<?php

declare(strict_types=1);

namespace App\Application\Procurement\UseCases;

use App\Application\Procurement\Services\SupplierListProjectionService;
use App\Application\Shared\DTO\Result;
use App\Core\Shared\Exceptions\DomainException;
use App\Ports\Out\Procurement\SupplierReaderPort;
use App\Ports\Out\Procurement\SupplierWriterPort;

final class UpdateSupplierHandler
{
    public function __construct(
        private SupplierReaderPort $readers,
        private SupplierWriterPort $writers,
        private SupplierListProjectionService $projection,
    ) {
    }

    /** @param array{bank_name?: ?string, bank_account_number?: ?string} $bankDetails */
    public function handle(string $supplierId, string $namaPtPengirim, array $bankDetails = []): Result
    {
        $supplier = $this->readers->getById(trim($supplierId));

        if ($supplier === null) {
            return Result::failure(
                'Supplier tidak ditemukan.',
                ['supplier' => ['SUPPLIER_NOT_FOUND']]
            );
        }

        $normalized = $this->normalize($namaPtPengirim);
        $duplicate = $this->readers->getByNormalizedNamaPtPengirim($normalized);

        if ($duplicate !== null && $duplicate->id() !== $supplier->id()) {
            return Result::failure(
                'Nama supplier sudah digunakan.',
                ['supplier' => ['SUPPLIER_DUPLICATE']]
            );
        }

        try {
            $supplier->rename($namaPtPengirim);
            $supplier->updateBankDetails(
                array_key_exists('bank_name', $bankDetails) ? $bankDetails['bank_name'] : $supplier->bankName(),
                array_key_exists('bank_account_number', $bankDetails) ? $bankDetails['bank_account_number'] : $supplier->bankAccountNumber(),
            );
        } catch (DomainException $e) {
            return Result::failure(
                $e->getMessage(),
                ['supplier' => ['INVALID_SUPPLIER']]
            );
        }

        $this->writers->update($supplier);
        $this->projection->syncSupplier($supplier->id());

        return Result::success(
            [
                'id' => $supplier->id(),
                'nama_pt_pengirim' => $supplier->namaPtPengirim(),
                'nama_pt_pengirim_normalized' => $supplier->namaPtPengirimNormalized(),
            ],
            'Supplier berhasil diperbarui.'
        );
    }

    private function normalize(string $name): string
    {
        $val = trim($name);

        if ($val === '') {
            throw new DomainException('Nama PT pengirim wajib ada.');
        }

        $val = preg_replace('/\\s+/', ' ', $val) ?? $val;

        return mb_strtolower($val);
    }
}
